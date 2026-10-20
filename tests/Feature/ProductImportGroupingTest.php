<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Support\SpreadsheetImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductImportGroupingTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_groups_models_under_a_single_product_family(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'products');
        file_put_contents($file, <<<'CSV'
product name,Models,Designs,Shades,Sizes
Embossed Wood Finish Doors,Interio Basic,E01,Sun Teak,"900X2050, 900X2100"
Embossed Wood Finish Doors,Classic,E01,Sun Teak,"900X2050, 900X2100"
CSV
        );

        $result = (new SpreadsheetImporter())->importFromFile($file);

        unlink($file);

        $this->assertSame(1, $result['products']);
        $this->assertSame(1, Product::query()->count());
        $this->assertSame('Embossed Wood Finish Doors', Product::query()->first()->name);
        $this->assertSame(2, Product::query()->first()->models()->count());
        $this->assertEqualsCanonicalizing(['Interio Basic', 'Classic'], Product::query()->first()->models()->pluck('name')->all());
    }
}
