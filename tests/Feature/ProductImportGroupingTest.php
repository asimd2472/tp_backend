<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductShade;
use App\Support\SpreadsheetImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductImportGroupingTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_groups_models_under_a_single_product_family(): void
    {
        $file = $this->temporaryCsvPath();
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

    public function test_import_matches_shade_colors_and_uses_the_configured_default_when_unavailable(): void
    {
        config(['app.default_color_code' => '#112233']);

        $file = $this->temporaryCsvPath();
        file_put_contents($file, <<<'CSV'
product name,Models,Designs,Shades,Sizes,Color
Embossed Wood Finish Doors,Interio Basic,E01,sun teak,900X2050,#000000
Embossed Wood Finish Doors,Interio Basic,E01,Unknown Shade,900X2050,#ffffff
CSV
        );

        try {
            $importer = new SpreadsheetImporter();
            $importer->importFromFile($file);
            ProductShade::query()->where('name', 'sun teak')->update(['color_hex' => '#000000']);
            $importer->importFromFile($file);
        } finally {
            unlink($file);
        }

        $this->assertDatabaseHas('product_shades', [
            'name' => 'sun teak',
            'color_hex' => '#C58A45',
        ]);
        $this->assertDatabaseHas('product_shades', [
            'name' => 'Unknown Shade',
            'color_hex' => '#112233',
        ]);
    }

    private function temporaryCsvPath(): string
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'products');
        $csvPath = $temporaryFile.'.csv';
        rename($temporaryFile, $csvPath);

        return $csvPath;
    }
}
