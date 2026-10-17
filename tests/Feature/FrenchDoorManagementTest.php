<?php

namespace Tests\Feature;

use App\Models\ProductOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FrenchDoorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_seeded_french_door_options(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.products.french-door.index'))
            ->assertOk()
            ->assertSee('Products')
            ->assertSee('French-door')
            ->assertSee('Lumière Pro')
            ->assertSee('Forest Brown')
            ->assertSee('2134')
            ->assertSee('Sizes are product-wide')
            ->assertSee('Default');

        $this->assertDatabaseCount('french_door_option', 18);
        $this->assertSame(6, ProductOption::query()->where('type', 'size')->count());
        $this->assertSame('french_door_option', (new ProductOption)->getTable());
    }

    public function test_an_admin_can_add_design_shade_and_unlinked_size(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $design = ProductOption::query()->where('type', 'design')->firstOrFail();

        $this->post(route('admin.products.french-door.options.store'), [
            'type' => 'design',
            'name' => 'Lumière Modern',
        ])->assertRedirect(route('admin.products.french-door.index'));

        Storage::fake('public');

        $this->post(route('admin.products.french-door.options.store'), [
            'type' => 'shade',
            'name' => 'Midnight Black',
            'parent_id' => $design->id,
            'color_hex' => '#111111',
            'image' => UploadedFile::fake()->image('midnight.png'),
            'is_default' => '1',
        ])->assertRedirect(route('admin.products.french-door.index'));

        $this->post(route('admin.products.french-door.options.store'), [
            'type' => 'size',
            'width_mm' => 2500,
            'height_mm' => 2300,
        ])->assertRedirect(route('admin.products.french-door.index'));

        $this->assertDatabaseHas('french_door_option', [
            'product_slug' => ProductOption::PRODUCT_SLUG,
            'type' => 'shade',
            'parent_id' => $design->id,
            'name' => 'Midnight Black',
            'color_hex' => '#111111',
            'is_default' => true,
        ]);
        $this->assertDatabaseHas('french_door_option', [
            'product_slug' => ProductOption::PRODUCT_SLUG,
            'type' => 'size',
            'parent_id' => null,
            'width_mm' => 2500,
            'height_mm' => 2300,
        ]);
        $shade = ProductOption::query()->where('name', 'Midnight Black')->firstOrFail();
        Storage::disk('public')->assertExists($shade->image_path);
        $this->assertDatabaseHas('french_door_option', [
            'id' => $design->id,
            'is_default' => true,
        ]);
    }

    public function test_public_options_endpoint_returns_images_and_frontend_defaults(): void
    {
        $response = $this->getJson(route('api.products.french-door.options'));

        $response->assertOk()
            ->assertJsonPath('product', ProductOption::PRODUCT_SLUG);

        $payload = $response->json();
        $design = collect($payload['designs'])->firstWhere('is_default', true);
        $shade = collect($design['shades'])->firstWhere('is_default', true);
        $size = collect($payload['sizes'])->firstWhere('is_default', true);

        $this->assertSame($design['id'], $payload['defaults']['design_id']);
        $this->assertSame($shade['id'], $payload['defaults']['shade_id']);
        $this->assertSame($size['id'], $payload['defaults']['size_id']);
        $this->assertArrayHasKey('image_url', $shade);
    }

    public function test_public_uploads_use_direct_urls_without_storage_symlink(): void
    {
        $url = Storage::disk('public')->url('french-door/shades/sample.png');

        $this->assertStringContainsString('/uploads/', $url);
        $this->assertStringNotContainsString('/storage/', $url);
    }

    public function test_product_api_returns_a_frontend_friendly_response_without_authentication(): void
    {
        $response = $this->getJson(route('api.product.french-door'));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'product' => ['name', 'slug'],
                'designs' => [
                    '*' => [
                        'id',
                        'name',
                        'is_default',
                        'shades' => [
                            '*' => ['id', 'name', 'color', 'image_url', 'is_default'],
                        ],
                    ],
                ],
                'sizes' => [
                    '*' => ['id', 'width_mm', 'height_mm', 'label', 'is_default'],
                ],
                'defaults' => ['design_id', 'shade_id', 'size_id'],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('product.slug', ProductOption::PRODUCT_SLUG);

        $payload = $response->json();
        $design = collect($payload['designs'])->firstWhere('id', $payload['defaults']['design_id']);
        $shade = collect($design['shades'])->firstWhere('id', $payload['defaults']['shade_id']);

        $this->assertTrue($design['is_default']);
        $this->assertTrue($shade['is_default']);
        $this->assertNotNull($payload['defaults']['size_id']);
    }

    public function test_admin_can_replace_and_remove_a_shade_image(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
        $shade = ProductOption::query()->where('type', 'shade')->firstOrFail();

        $this->put(route('admin.products.french-door.options.update', $shade), [
            'name' => $shade->name,
            'parent_id' => $shade->parent_id,
            'color_hex' => $shade->color_hex,
            'image' => UploadedFile::fake()->image('first.png'),
        ])->assertRedirect(route('admin.products.french-door.index'));

        $shade->refresh();
        $firstImage = $shade->image_path;
        Storage::disk('public')->assertExists($firstImage);

        $this->put(route('admin.products.french-door.options.update', $shade), [
            'name' => $shade->name,
            'parent_id' => $shade->parent_id,
            'color_hex' => $shade->color_hex,
            'image' => UploadedFile::fake()->image('replacement.png'),
        ])->assertRedirect(route('admin.products.french-door.index'));

        $shade->refresh();
        Storage::disk('public')->assertMissing($firstImage);
        Storage::disk('public')->assertExists($shade->image_path);

        $this->delete(route('admin.products.french-door.options.destroy', $shade))
            ->assertRedirect(route('admin.products.french-door.index'));

        Storage::disk('public')->assertMissing($shade->image_path);
    }

    public function test_shades_require_a_french_door_design_and_sizes_cannot_be_duplicated(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Storage::fake('public');

        $this->from(route('admin.products.french-door.index'))
            ->post(route('admin.products.french-door.options.store'), [
                'type' => 'shade',
                'name' => 'Invalid Shade',
                'parent_id' => 99999,
                'color_hex' => '#112233',
                'image' => UploadedFile::fake()->image('invalid.png'),
            ])
            ->assertRedirect(route('admin.products.french-door.index'))
            ->assertSessionHasErrors('parent_id');

        $this->from(route('admin.products.french-door.index'))
            ->post(route('admin.products.french-door.options.store'), [
                'type' => 'size',
                'width_mm' => 2134,
                'height_mm' => 2134,
            ])
            ->assertRedirect(route('admin.products.french-door.index'))
            ->assertSessionHasErrors('width_mm');
    }

    public function test_admin_can_edit_design_shade_and_shared_size_options(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $designs = ProductOption::query()->where('type', 'design')->orderBy('id')->get();
        $shade = ProductOption::query()->where('type', 'shade')->where('parent_id', $designs[0]->id)->firstOrFail();
        $size = ProductOption::query()->where('type', 'size')->firstOrFail();

        $this->put(route('admin.products.french-door.options.update', $designs[0]), [
            'name' => 'Lumière Pro Updated',
        ])->assertRedirect(route('admin.products.french-door.index'));

        $this->put(route('admin.products.french-door.options.update', $shade), [
            'name' => 'Forest Brown Updated',
            'parent_id' => $designs[1]->id,
            'color_hex' => '#553322',
        ])->assertRedirect(route('admin.products.french-door.index'));

        $this->put(route('admin.products.french-door.options.update', $size), [
            'width_mm' => 2200,
            'height_mm' => 2300,
        ])->assertRedirect(route('admin.products.french-door.index'));

        $this->assertDatabaseHas('french_door_option', [
            'id' => $designs[0]->id,
            'name' => 'Lumière Pro Updated',
        ]);
        $this->assertDatabaseHas('french_door_option', [
            'id' => $shade->id,
            'parent_id' => $designs[1]->id,
            'name' => 'Forest Brown Updated',
            'color_hex' => '#553322',
        ]);
        $this->assertDatabaseHas('french_door_option', [
            'id' => $size->id,
            'parent_id' => null,
            'width_mm' => 2200,
            'height_mm' => 2300,
        ]);
    }

    public function test_deleting_a_design_deletes_its_shades_but_not_shared_sizes(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $design = ProductOption::query()->where('type', 'design')->with('children')->firstOrFail();
        $shadeIds = $design->children->pluck('id');
        $sizeId = ProductOption::query()->where('type', 'size')->value('id');

        $this->delete(route('admin.products.french-door.options.destroy', $design))
            ->assertRedirect(route('admin.products.french-door.index'));

        $this->assertDatabaseMissing('french_door_option', ['id' => $design->id]);

        foreach ($shadeIds as $shadeId) {
            $this->assertDatabaseMissing('french_door_option', ['id' => $shadeId]);
        }

        $this->assertDatabaseHas('french_door_option', ['id' => $sizeId, 'type' => 'size']);
    }

    public function test_only_admins_can_manage_french_door_options(): void
    {
        $this->get(route('admin.products.french-door.index'))
            ->assertRedirect(route('admin.login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.products.french-door.index'))
            ->assertForbidden();
    }
}
