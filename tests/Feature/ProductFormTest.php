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
            ->assertSee('models[0][designs][0][shades][0][id]', false)
            ->assertSee('multiple', false)
            ->assertSee('Existing models are preserved on update', false);
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
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        $fixturePath = __DIR__.'/../fixtures/test-model-image.png';
        if (! file_exists($fixturePath)) {
            $image = imagecreatetruecolor(80, 60);
            $bg = imagecolorallocate($image, 20, 30, 45);
            $fg = imagecolorallocate($image, 255, 255, 255);
            imagefill($image, 0, 0, $bg);
            imagestring($image, 5, 18, 20, 'MODEL', $fg);
            imagepng($image, $fixturePath);
            imagedestroy($image);
        }

        $file = new UploadedFile(
            $fixturePath,
            'test-model-image.png',
            'image/png',
            null,
            true,
        );

        $this->post(route('admin.products.store'), [
            'name' => 'Model upload test',
            'category' => 'Doors',
            'models' => [[
                'name' => 'Model A',
                'main_image' => $file,
                'designs' => [],
            ]],
        ]);

        $product = Product::query()->where('name', 'Model upload test')->firstOrFail();
        $model = $product->models()->first();

        $this->assertNotNull($model);
        $this->assertStringContainsString('model/', $model->main_image ?? '');
        Storage::disk('public')->assertExists($model->main_image);
    }
}
