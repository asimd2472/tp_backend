<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_toggle_size_combinations_on_product_form(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $product = Product::create([
            'name' => 'Test product',
            'slug' => 'test-product',
            'starting_price' => 10.00,
        ]);

        $model = $product->models()->create([
            'name' => 'Model A',
            'slug' => 'model-a',
        ]);

        $design = $model->designs()->create([
            'product_id' => $product->id,
            'name' => 'Design A',
            'code' => 'D-1',
        ]);

        $shade = $design->shades()->create([
            'name' => 'Oak',
            'color_hex' => '#ffffff',
        ]);

        $shade->sizes()->create([
            'width_mm' => 1200,
            'height_mm' => 1800,
        ]);

        $this->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Size combinations', false)
            ->assertSee('Show / hide', false)
            ->assertSee('<details class="mt-3 border rounded p-3 size-combinations">', false)
            ->assertSee('models[0][designs][0][id]', false)
            ->assertSee('Design gallery images')
            ->assertSee('models[0][designs][0][shades][0][id]', false)
            ->assertSee('multiple', false)
            ->assertSee('Default model', false)
            ->assertSee('up to 100 images per product save', false)
            ->assertSee('Existing models are preserved on update', false);
    }

    public function test_admin_can_create_and_update_product_faqs_specifications_rating_and_review(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $payload = [
            'name' => 'Product content test',
            'faqs' => [[
                'question' => 'Is this door waterproof?',
                'answer' => 'It is suitable for indoor use.',
            ]],
            'specifications' => [[
                'question' => 'What is the thickness?',
                'answer' => '35 mm',
            ]],
            'rating' => '4.5',
            'review' => 'Strong quality and finish.',
        ];

        $this->post(route('admin.products.store'), $payload)
            ->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('name', $payload['name'])->firstOrFail();
        $this->assertSame($payload['faqs'], $product->faqs);
        $this->assertSame($payload['specifications'], $product->specifications);
        $this->assertSame('4.5', $product->rating);
        $this->assertSame($payload['review'], $product->review);

        $this->put(route('admin.products.update', $product), [
            'name' => $payload['name'],
            'faqs' => [[
                'question' => 'Can it be customized?',
                'answer' => 'Yes, customization is available.',
            ]],
            'specifications' => [],
            'rating' => '4.8',
            'review' => 'Updated customer feedback.',
        ])->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertSame([[
            'question' => 'Can it be customized?',
            'answer' => 'Yes, customization is available.',
        ]], $product->faqs);
        $this->assertSame([], $product->specifications);
        $this->assertSame('4.8', $product->rating);
        $this->assertSame('Updated customer feedback.', $product->review);
    }

    public function test_product_form_displays_existing_faqs_specifications_rating_and_review(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $product = Product::create([
            'name' => 'Product content edit',
            'slug' => 'product-content-edit',
            'faqs' => [['question' => 'FAQ question', 'answer' => 'FAQ answer']],
            'specifications' => [['question' => 'Specification question', 'answer' => 'Specification answer']],
            'rating' => 4.5,
            'review' => 'Product review text',
        ]);

        $this->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('FAQ question')
            ->assertSee('FAQ answer')
            ->assertSee('Specification question')
            ->assertSee('Specification answer')
            ->assertSee('value="4.5"', false)
            ->assertSee('Product review text');
    }

    public function test_edit_form_uses_correct_upload_names_for_each_model_design_and_shade(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $product = Product::create([
            'name' => 'Nested image field test',
            'slug' => 'nested-image-field-test',
            'starting_price' => 0,
        ]);

        foreach (['A', 'B'] as $letter) {
            $model = $product->models()->create([
                'name' => 'Model '.$letter,
                'slug' => 'nested-field-model-'.strtolower($letter),
            ]);
            $design = $model->designs()->create([
                'product_id' => $product->id,
                'name' => 'Design '.$letter,
            ]);
            $design->shades()->create(['name' => 'Shade '.$letter, 'color_hex' => '#112233']);
        }

        $this->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('name="models[0][main_image]"', false)
            ->assertSee('name="models[0][designs][0][thumbnail]"', false)
            ->assertSee('name="models[0][designs][0][images][0][file][]"', false)
            ->assertSee('name="models[0][designs][0][shades][0][swatch]"', false)
            ->assertSee('name="models[0][designs][0][shades][0][images][0][file][]"', false)
            ->assertSee('name="models[1][main_image]"', false)
            ->assertSee('name="models[1][designs][0][thumbnail]"', false)
            ->assertSee('name="models[1][designs][0][images][0][file][]"', false)
            ->assertSee('name="models[1][designs][0][shades][0][swatch]"', false)
            ->assertSee('name="models[1][designs][0][shades][0][images][0][file][]"', false);
    }

    public function test_admin_can_choose_one_default_model_per_product(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $product = Product::create([
            'name' => 'Default model test',
            'slug' => 'default-model-test',
            'starting_price' => 0,
        ]);
        $modelA = $product->models()->create(['name' => 'Model A', 'slug' => 'default-model-a', 'is_default' => true]);
        $modelB = $product->models()->create(['name' => 'Model B', 'slug' => 'default-model-b']);

        $this->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'models' => [
                [
                    'id' => $modelA->id,
                    'name' => $modelA->name,
                    'is_default' => '0',
                ],
                [
                    'id' => $modelB->id,
                    'name' => $modelB->name,
                    'is_default' => '1',
                ],
            ],
        ])->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('product_models', ['id' => $modelA->id, 'is_default' => false]);
        $this->assertDatabaseHas('product_models', ['id' => $modelB->id, 'is_default' => true]);
        $this->assertSame(1, $product->models()->where('is_default', true)->count());
    }

    public function test_product_main_image_is_uploaded_directly_to_public_uploads(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.products.store'), [
            'name' => 'Product main image test',
            'main_image' => UploadedFile::fake()->image('product-main.jpg'),
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('name', 'Product main image test')->firstOrFail();
        $imagePath = public_path('uploads/'.$product->main_image);

        try {
            $this->assertStringStartsWith('products/', $product->main_image);
            $this->assertFileExists($imagePath);
            $this->assertSame(asset('uploads/'.$product->main_image), $product->thumbnail_url);
        } finally {
            File::delete($imagePath);
        }
    }

    public function test_product_update_preserves_models_designs_shades_and_sizes_not_submitted(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        $product = Product::create([
            'name' => 'Test product',
            'slug' => 'test-product-preserve',
            'starting_price' => 10.00,
        ]);

        $model = $product->models()->create(['name' => 'Model A', 'slug' => 'model-a-preserve']);
        $design = $model->designs()->create([
            'product_id' => $product->id,
            'name' => 'Design A',
        ]);
        $shade = $design->shades()->create(['name' => 'Oak', 'color_hex' => '#ffffff']);
        Storage::disk('public')->put('existing-gallery.jpg', 'existing image');
        $existingImage = $shade->images()->create([
            'image_path' => 'existing-gallery.jpg',
            'is_primary' => true,
        ]);
        $shade->sizes()->create(['width_mm' => 1200, 'height_mm' => 1800]);

        $otherModel = $product->models()->create(['name' => 'Model B', 'slug' => 'model-b-preserve']);
        $otherDesign = $otherModel->designs()->create([
            'product_id' => $product->id,
            'name' => 'Design B',
        ]);
        $otherShade = $otherDesign->shades()->create(['name' => 'Walnut', 'color_hex' => '#654321']);
        $otherShade->sizes()->create(['width_mm' => 900, 'height_mm' => 2100]);

        $this->put(route('admin.products.update', $product), [
            'name' => 'Updated product',
            'models' => [[
                'id' => $model->id,
                'name' => 'Model A Updated',
                'designs' => [[
                    'id' => $design->id,
                    'name' => 'Design A Updated',
                    'shades' => [[
                        'id' => $shade->id,
                        'name' => 'Oak Updated',
                        'color_hex' => '#ffffff',
                    ]],
                ]],
            ]],
        ])->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('product_models', ['id' => $model->id, 'name' => 'Model A Updated']);
        $this->assertDatabaseHas('product_models', ['id' => $otherModel->id, 'name' => 'Model B']);
        $this->assertDatabaseHas('product_designs', ['id' => $design->id, 'name' => 'Design A Updated']);
        $this->assertDatabaseHas('product_designs', ['id' => $otherDesign->id, 'name' => 'Design B']);
        $this->assertDatabaseHas('product_shades', ['id' => $shade->id, 'name' => 'Oak Updated']);
        $this->assertDatabaseHas('product_shades', ['id' => $otherShade->id, 'name' => 'Walnut']);
        $this->assertDatabaseHas('product_images', ['id' => $existingImage->id, 'image_path' => 'existing-gallery.jpg']);
        $this->assertDatabaseHas('product_sizes', [
            'product_shade_id' => $shade->id,
            'width_mm' => 1200,
            'height_mm' => 1800,
        ]);
        $this->assertDatabaseHas('product_sizes', [
            'product_shade_id' => $otherShade->id,
            'width_mm' => 900,
            'height_mm' => 2100,
        ]);
    }

    public function test_product_update_uploads_multiple_gallery_images(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $product = Product::create([
            'name' => 'Gallery product',
            'slug' => 'gallery-product',
            'starting_price' => 10.00,
        ]);
        $model = $product->models()->create(['name' => 'Model A', 'slug' => 'gallery-model-a']);
        $design = $model->designs()->create([
            'product_id' => $product->id,
            'name' => 'Design A',
        ]);
        $shade = $design->shades()->create(['name' => 'Oak', 'color_hex' => '#ffffff']);
        File::ensureDirectoryExists(public_path('uploads/products/gallery'));
        File::put(public_path('uploads/products/gallery/existing-gallery.jpg'), 'existing image');
        $shade->images()->create([
            'image_path' => 'products/gallery/existing-gallery.jpg',
            'is_primary' => true,
        ]);

        try {
            $payload = [
                'name' => $product->name,
                'models' => [[
                    'id' => $model->id,
                    'name' => $model->name,
                    'designs' => [[
                        'id' => $design->id,
                        'name' => $design->name,
                        'shades' => [[
                            'id' => $shade->id,
                            'name' => $shade->name,
                            'color_hex' => '#ffffff',
                            'swatch' => UploadedFile::fake()->image('swatch.jpg'),
                            'images' => [[
                                'file' => [
                                    UploadedFile::fake()->image('gallery-one.jpg'),
                                    UploadedFile::fake()->image('gallery-two.jpg'),
                                ],
                            ]],
                        ]],
                    ]],
                ]],
            ];

            $this->put(route('admin.products.update', $product), $payload)
                ->assertRedirect(route('admin.products.index'));

            $shade->refresh();
            $this->assertSame(3, $shade->images()->count());
            foreach ($shade->images as $image) {
                $this->assertFileExists(public_path('uploads/'.$image->image_path));
                $this->assertSame(asset('uploads/'.$image->image_path), $image->url);
            }
            $this->assertFileExists(public_path('uploads/'.$shade->swatch_image));
            $this->assertSame(asset('uploads/'.$shade->swatch_image), $shade->swatch_url);
        } finally {
            foreach ($shade->images()->get() as $image) {
                File::delete(public_path('uploads/'.$image->image_path));
            }
            File::delete(public_path('uploads/'.$shade->swatch_image));
            File::delete(public_path('uploads/products/gallery/existing-gallery.jpg'));
        }
    }

    public function test_model_image_upload_is_saved_to_the_model_uploads_folder(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.products.store'), [
            'name' => 'Model upload test',
            'category' => 'Doors',
            'models' => [[
                'name' => 'Model A',
                'main_image' => UploadedFile::fake()->image('test-model-image.png'),
                'designs' => [],
            ]],
        ]);

        $product = Product::query()->where('name', 'Model upload test')->firstOrFail();
        $model = $product->models()->first();

        $this->assertNotNull($model);
        $this->assertStringContainsString('model/', $model->main_image ?? '');
        $imagePath = public_path('uploads/'.$model->main_image);
        try {
            $this->assertFileExists($imagePath);
        } finally {
            File::delete($imagePath);
        }
    }

    public function test_admin_can_upload_gallery_images_directly_to_a_design(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $product = Product::create([
            'name' => 'Design gallery upload test',
            'slug' => 'design-gallery-upload-test',
        ]);
        $model = $product->models()->create(['name' => 'Model A', 'slug' => 'design-gallery-model-a']);
        $design = $model->designs()->create([
            'product_id' => $product->id,
            'name' => 'Shade-free design',
        ]);

        $response = $this->postJson(route('admin.products.designs.images', [$product, $model, $design]), [
            'image' => UploadedFile::fake()->image('design-gallery.jpg'),
        ])->assertOk()->assertJsonPath('message', 'Design gallery image uploaded successfully.');

        $image = $design->images()->firstOrFail();
        try {
            $this->assertTrue($image->is_primary);
            $this->assertFileExists(public_path('uploads/'.$image->image_path));
            $this->assertSame(asset('uploads/'.$image->image_path), $response->json('image_url'));
        } finally {
            File::delete(public_path('uploads/'.$image->image_path));
        }
    }

    public function test_admin_can_create_a_shade_free_design_with_gallery_images(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.products.store'), [
            'name' => 'Shade-free gallery product',
            'models' => [[
                'name' => 'Model A',
                'is_default' => '1',
                'designs' => [[
                    'name' => 'Design A',
                    'sizes' => '900x2050, 900x2100',
                    'images' => [[
                        'file' => [UploadedFile::fake()->image('design-gallery-create.jpg')],
                    ]],
                ]],
            ]],
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('name', 'Shade-free gallery product')->firstOrFail();
        $design = $product->models()->firstOrFail()->designs()->firstOrFail();
        $image = $design->images()->firstOrFail();

        try {
            $this->assertSame(0, $design->shades()->count());
            $this->assertSame(2, $design->sizes()->count());
            $this->assertFileExists(public_path('uploads/'.$image->image_path));
            $this->get(route('admin.products.edit', $product))
                ->assertOk()
                ->assertSee('Design sizes (width x height in mm)')
                ->assertSee('900x2050');

            $this->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'models' => [[
                    'id' => $product->models()->firstOrFail()->id,
                    'name' => 'Model A',
                    'is_default' => '1',
                    'designs' => [[
                        'id' => $design->id,
                        'name' => 'Design A',
                        'sizes' => '1000x2100',
                    ]],
                ]],
            ])->assertRedirect(route('admin.products.index'));
            $this->assertDatabaseHas('product_design_sizes', [
                'product_design_id' => $design->id,
                'width_mm' => 1000,
                'height_mm' => 2100,
            ]);

            $this->getJson(route('api.products.show', $product->models()->firstOrFail()->slug))
                ->assertOk()
                ->assertJsonPath('designs.0.gallery.0.url', asset('uploads/'.$image->image_path))
                ->assertJsonPath('designs.0.sizes.0.width_mm', 1000)
                ->assertJsonPath('designs.0.shades', []);
        } finally {
            File::delete(public_path('uploads/'.$image->image_path));
        }
    }

    public function test_design_sizes_reject_invalid_measurements(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'name' => 'Invalid design size product',
                'models' => [[
                    'name' => 'Model A',
                    'designs' => [[
                        'name' => 'Design A',
                        'sizes' => '0x2050',
                    ]],
                ]],
            ])
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors('sizes');

        $this->assertDatabaseMissing('products', ['name' => 'Invalid design size product']);
    }

    public function test_create_uploads_model_and_design_images_for_multiple_models(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $payload = [
            'name' => 'Multi model upload create',
            'models' => [
                [
                    'name' => 'Model A',
                    'main_image' => UploadedFile::fake()->image('model-a.jpg'),
                    'designs' => [[
                        'name' => 'Design A',
                        'thumbnail' => UploadedFile::fake()->image('design-a.jpg'),
                        'shades' => [[
                            'name' => 'Shade A',
                            'color_hex' => '#112233',
                            'swatch' => UploadedFile::fake()->image('swatch-a.jpg'),
                            'images' => [[
                                'file' => [
                                    UploadedFile::fake()->image('gallery-a1.jpg'),
                                    UploadedFile::fake()->image('gallery-a2.jpg'),
                                ],
                            ]],
                        ]],
                    ]],
                ],
                [
                    'name' => 'Model B',
                    'main_image' => UploadedFile::fake()->image('model-b.jpg'),
                    'designs' => [[
                        'name' => 'Design B',
                        'thumbnail' => UploadedFile::fake()->image('design-b.jpg'),
                        'shades' => [[
                            'name' => 'Shade B',
                            'color_hex' => '#445566',
                            'swatch' => UploadedFile::fake()->image('swatch-b.jpg'),
                            'images' => [[
                                'file' => [
                                    UploadedFile::fake()->image('gallery-b1.jpg'),
                                    UploadedFile::fake()->image('gallery-b2.jpg'),
                                ],
                            ]],
                        ]],
                    ]],
                ],
            ],
        ];

        $this->post(route('admin.products.store'), $payload)
            ->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('name', $payload['name'])->firstOrFail();
        try {
            $this->assertUploadedProductImagesExist($product, 2, 2, 2, 4);
        } finally {
            $this->deleteUploadedProductImages($product);
        }
    }

    public function test_update_uploads_model_and_design_images_for_multiple_models(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $product = Product::create([
            'name' => 'Multi model upload update',
            'slug' => 'multi-model-upload-update',
            'starting_price' => 0,
        ]);
        $models = [];
        foreach (['A', 'B'] as $letter) {
            $model = $product->models()->create([
                'name' => 'Model '.$letter,
                'slug' => 'multi-update-model-'.strtolower($letter),
            ]);
            $design = $model->designs()->create([
                'product_id' => $product->id,
                'name' => 'Design '.$letter,
            ]);
            $design->shades()->create(['name' => 'Shade '.$letter, 'color_hex' => '#112233']);
            $models[] = [
                'id' => $model->id,
                'name' => $model->name,
                'main_image' => UploadedFile::fake()->image('model-'.$letter.'.jpg'),
                'designs' => [[
                    'id' => $design->id,
                    'name' => $design->name,
                    'thumbnail' => UploadedFile::fake()->image('design-'.$letter.'.jpg'),
                    'shades' => [[
                        'id' => $design->shades()->first()->id,
                        'name' => 'Shade '.$letter,
                        'color_hex' => '#112233',
                        'swatch' => UploadedFile::fake()->image('swatch-'.$letter.'.jpg'),
                        'images' => [[
                            'file' => [
                                UploadedFile::fake()->image('gallery-'.$letter.'-1.jpg'),
                                UploadedFile::fake()->image('gallery-'.$letter.'-2.jpg'),
                            ],
                        ]],
                    ]],
                ]],
            ];
        }

        try {
            $this->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'main_image' => UploadedFile::fake()->image('product-updated.jpg'),
                'models' => $models,
            ])->assertRedirect(route('admin.products.index'));

            $product->refresh()->load('models.designs');
            $this->assertFileExists(public_path('uploads/'.$product->main_image));
            $this->assertUploadedProductImagesExist($product, 2, 2, 2, 4);
        } finally {
            $this->deleteUploadedProductImages($product);
        }
    }

    public function test_create_accepts_more_than_twenty_images_across_models_and_designs(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $models = [];

        for ($modelIndex = 0; $modelIndex < 4; $modelIndex++) {
            $designs = [];
            for ($designIndex = 0; $designIndex < 2; $designIndex++) {
                $designs[] = [
                    'name' => "Design {$modelIndex}-{$designIndex}",
                    'thumbnail' => UploadedFile::fake()->image("design-{$modelIndex}-{$designIndex}.jpg"),
                    'shades' => [[
                        'name' => "Shade {$modelIndex}-{$designIndex}",
                        'color_hex' => '#112233',
                        'swatch' => UploadedFile::fake()->image("swatch-{$modelIndex}-{$designIndex}.jpg"),
                        'images' => [[
                            'file' => [
                                UploadedFile::fake()->image("gallery-{$modelIndex}-{$designIndex}-1.jpg"),
                                UploadedFile::fake()->image("gallery-{$modelIndex}-{$designIndex}-2.jpg"),
                            ],
                        ]],
                    ]],
                ];
            }

            $models[] = [
                'name' => "Model {$modelIndex}",
                'main_image' => UploadedFile::fake()->image("model-{$modelIndex}.jpg"),
                'designs' => $designs,
            ];
        }

        $productName = 'More than twenty images';

        $this->post(route('admin.products.store'), [
            'name' => $productName,
            'main_image' => UploadedFile::fake()->image('product-main.jpg'),
            'models' => $models,
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('name', $productName)->firstOrFail();
        try {
            $this->assertUploadedProductImagesExist($product, 4, 8, 8, 16);
            $this->assertFileExists(public_path('uploads/'.$product->main_image));
        } finally {
            $this->deleteUploadedProductImages($product);
            File::delete(public_path('uploads/'.$product->main_image));
        }
    }

    private function assertUploadedProductImagesExist(
        Product $product,
        int $expectedModelImages,
        int $expectedDesignImages,
        int $expectedSwatches,
        int $expectedGalleryImages,
    ): void {
        $product->load('models.designs.shades.images');
        $modelImageCount = 0;
        $designImageCount = 0;
        $swatchCount = 0;
        $galleryImageCount = 0;

        foreach ($product->models as $model) {
            if ($model->main_image) {
                $modelImageCount++;
                $this->assertFileExists(public_path('uploads/'.$model->main_image));
            }
            foreach ($model->designs as $design) {
                if ($design->thumbnail_image) {
                    $designImageCount++;
                    $this->assertFileExists(public_path('uploads/'.$design->thumbnail_image));
                    $this->assertSame(asset('uploads/'.$design->thumbnail_image), $design->thumbnail_url);
                }
                foreach ($design->shades as $shade) {
                    if ($shade->swatch_image) {
                        $swatchCount++;
                        $this->assertFileExists(public_path('uploads/'.$shade->swatch_image));
                        $this->assertSame(asset('uploads/'.$shade->swatch_image), $shade->swatch_url);
                    }
                    foreach ($shade->images as $image) {
                        $galleryImageCount++;
                        $this->assertFileExists(public_path('uploads/'.$image->image_path));
                        $this->assertSame(asset('uploads/'.$image->image_path), $image->url);
                    }
                }
            }
        }

        $this->assertSame($expectedModelImages, $modelImageCount);
        $this->assertSame($expectedDesignImages, $designImageCount);
        $this->assertSame($expectedSwatches, $swatchCount);
        $this->assertSame($expectedGalleryImages, $galleryImageCount);
    }

    private function deleteUploadedProductImages(Product $product): void
    {
        if ($product->main_image) {
            File::delete(public_path('uploads/'.$product->main_image));
        }

        $product->load('models.designs.shades.images');
        foreach ($product->models as $model) {
            if ($model->main_image) {
                File::delete(public_path('uploads/'.$model->main_image));
            }
            foreach ($model->designs as $design) {
                if ($design->thumbnail_image) {
                    File::delete(public_path('uploads/'.$design->thumbnail_image));
                }
                foreach ($design->shades as $shade) {
                    if ($shade->swatch_image) {
                        File::delete(public_path('uploads/'.$shade->swatch_image));
                    }
                    foreach ($shade->images as $image) {
                        File::delete(public_path('uploads/'.$image->image_path));
                    }
                }
            }
        }
    }
}
