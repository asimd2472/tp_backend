<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WindowProduct;
use App\Models\WindowShade;
use App\Models\WindowSize;
use App\Support\WindowSpreadsheetImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class WindowCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_the_window_sample_into_separate_window_tables(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.windows.import.store'), [
            'spreadsheet' => new UploadedFile(
                base_path('public/excel/window.xlsx'),
                'window.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true,
            ),
        ])->assertRedirect(route('admin.windows.index'));

        $this->assertDatabaseHas('window_products', [
            'name' => 'Swing and Slide Window',
            'slug' => 'swing-and-slide-window',
        ]);
        $this->assertDatabaseHas('window_models', ['name' => 'Vista']);
        $this->assertDatabaseHas('window_shades', [
            'name' => 'Sun Teak',
            'color_hex' => '#C58A45',
        ]);
        $sunTeak = WindowShade::query()->where('name', 'Sun Teak')->firstOrFail();
        $this->assertSame(11, $sunTeak->sizes()->count());
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('product_models', 0);
        $this->assertDatabaseCount('product_designs', 0);

        $productCount = WindowProduct::query()->count();
        $sizeCount = WindowSize::query()->count();
        app(WindowSpreadsheetImporter::class)->importFromFile(base_path('public/excel/window.xlsx'));
        $this->assertDatabaseCount('window_products', $productCount);
        $this->assertDatabaseCount('window_sizes', $sizeCount);
    }

    public function test_window_admin_screens_render_with_the_window_navigation_link(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.windows.index'))
            ->assertOk()
            ->assertSee(route('admin.windows.index'))
            ->assertSee('Windows');

        $this->get(route('admin.windows.create'))
            ->assertOk()
            ->assertSee('Models, shades, and sizes')
            ->assertSee('there is no design level for windows');

        $this->get(route('admin.windows.import.index'))
            ->assertOk()
            ->assertSee(asset('excel/window.xlsx'));
    }

    public function test_admin_can_create_window_models_shades_and_sizes_without_creating_door_records(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.windows.store'), [
            'name' => 'Awning Window',
            'category' => 'Windows',
            'models' => [
                [
                    'name' => 'Vista',
                    'is_default' => '1',
                    'is_active' => '1',
                    'shades' => [
                        [
                            'name' => 'Sun Teak',
                            'color_hex' => '#C58A45',
                            'sizes' => '914x1219, 914x1524',
                        ],
                    ],
                ],
            ],
        ])->assertSessionHasNoErrors();

        $window = WindowProduct::query()->where('slug', 'awning-window')->firstOrFail();
        $this->assertDatabaseHas('window_models', [
            'window_product_id' => $window->id,
            'name' => 'Vista',
            'is_default' => true,
        ]);
        $this->assertDatabaseHas('window_sizes', [
            'width_mm' => 914,
            'height_mm' => 1524,
        ]);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('product_models', 0);
    }

    public function test_admin_can_update_window_product_models_shades_and_sizes(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $window = WindowProduct::create([
            'name' => 'Awning Window',
            'slug' => 'awning-window-update',
        ]);
        $model = $window->models()->create([
            'name' => 'Vista',
            'slug' => 'awning-window-vista-update',
            'is_active' => true,
        ]);
        $shade = $model->shades()->create([
            'name' => 'Sun Teak',
            'color_hex' => '#C58A45',
        ]);
        $shade->sizes()->create(['width_mm' => 914, 'height_mm' => 1219]);

        $this->put(route('admin.windows.update', $window), [
            'name' => 'Updated Awning Window',
            'models' => [
                [
                    'id' => $model->id,
                    'name' => 'Vista Plus',
                    'is_active' => '1',
                    'shades' => [
                        [
                            'id' => $shade->id,
                            'name' => 'Lava Teak',
                            'color_hex' => '#C58A45',
                            'sizes' => '1219x1830',
                        ],
                    ],
                ],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('window_products', [
            'id' => $window->id,
            'name' => 'Updated Awning Window',
            'slug' => 'updated-awning-window',
        ]);
        $this->assertDatabaseHas('window_models', [
            'id' => $model->id,
            'name' => 'Vista Plus',
        ]);
        $this->assertDatabaseHas('window_shades', [
            'id' => $shade->id,
            'name' => 'Lava Teak',
        ]);
        $this->assertDatabaseMissing('window_sizes', [
            'window_shade_id' => $shade->id,
            'width_mm' => 914,
            'height_mm' => 1219,
        ]);
        $this->assertDatabaseHas('window_sizes', [
            'window_shade_id' => $shade->id,
            'width_mm' => 1219,
            'height_mm' => 1830,
        ]);
    }

    public function test_window_api_returns_shades_without_designs_and_uses_window_records_only(): void
    {
        $window = WindowProduct::create([
            'name' => 'Awning Window',
            'slug' => 'awning-window-api',
            'category' => 'Windows',
            'is_active' => true,
        ]);
        $model = $window->models()->create([
            'name' => 'Vista',
            'slug' => 'awning-window-vista',
            'is_active' => true,
            'is_default' => true,
        ]);
        $shade = $model->shades()->create([
            'name' => 'Sun Teak',
            'color_hex' => '#C58A45',
        ]);
        $shade->sizes()->create(['width_mm' => 914, 'height_mm' => 1219]);

        $this->getJson(route('api.windows.index'))
            ->assertOk()
            ->assertJsonPath('windows.0.slug', 'awning-window-api');
        $this->getJson(route('api.windows.models', $window->slug))
            ->assertOk()
            ->assertJsonPath('models.0.name', 'Vista');
        $this->getJson(route('api.window-details', ['window_slug' => $window->slug]))
            ->assertOk()
            ->assertJsonPath('window.slug', $window->slug)
            ->assertJsonPath('model.id', $model->id)
            ->assertJsonPath('shades.0.id', $shade->id)
            ->assertJsonPath('shades.0.sizes.0.width_mm', 914)
            ->assertJsonPath('defaults.shade_id', $shade->id)
            ->assertJsonMissingPath('designs');
        $this->getJson(route('api.window-details-by-id', [
            'model_id' => $model->id,
            'window_slug' => $window->slug,
        ]))->assertOk()->assertJsonPath('model.id', $model->id);
    }

    public function test_invalid_window_size_does_not_partially_import_the_spreadsheet(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'windows');
        file_put_contents($file, <<<'CSV'
Product Name,Models,Shades,Sizes
Invalid Window,Vista,Sun Teak,not-a-size
CSV
        );

        try {
            $result = app(WindowSpreadsheetImporter::class)->importFromFile($file);
        } finally {
            unlink($file);
        }

        $this->assertNotEmpty($result['errors']);
        $this->assertDatabaseCount('window_products', 0);
        $this->assertDatabaseCount('window_models', 0);
    }
}
