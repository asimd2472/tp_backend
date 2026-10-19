<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_the_sample_product_excel(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.products.import.store'), [
            'spreadsheet' => new UploadedFile(
                base_path('public/excel/TP product.xlsx'),
                'TP product.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true,
            ),
        ])
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Embossed Wood Finish Doors',
            'slug' => 'embossed-wood-finish-doors',
        ]);

        $this->assertDatabaseHas('product_designs', [
            'name' => 'E01',
        ]);

        $this->assertDatabaseHas('product_shades', [
            'name' => 'Sun Teak',
        ]);
    }
}
