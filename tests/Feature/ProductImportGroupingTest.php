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

    public function test_import_keeps_a_design_when_the_shade_cell_is_blank(): void
    {
        $file = $this->temporaryCsvPath();
        file_put_contents($file, <<<'CSV'
product name,Models,Designs,Shades,Sizes
Plain Doors,Classic,No Shade Design,,"900X2050, 900X2100"
CSV
        );

        try {
            $result = (new SpreadsheetImporter())->importFromFile($file);
        } finally {
            unlink($file);
        }

        $design = Product::query()->firstOrFail()->models()->firstOrFail()->designs()->firstOrFail();

        $this->assertSame([], $result['errors']);
        $this->assertSame('No Shade Design', $design->name);
        $this->assertSame(0, $design->shades()->count());
        $this->assertEqualsCanonicalizing(
            ['900x2050', '900x2100'],
            $design->sizes()->get()->map(fn ($size) => $size->width_mm.'x'.$size->height_mm)->all(),
        );
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

    public function test_import_adds_and_updates_model_content_and_places_price_on_shade_or_shade_free_design(): void
    {
        $file = $this->temporaryCsvPath();
        $spreadsheet = <<<'CSV'
product name,Models,Designs,Shades,Sizes,Description,Accessories,Starting Price
Import Doors,Classic,E01,Oak,"900x2000",Door description,Door accessories,10000
Import Doors,Classic,E02,,"900x2100",Door description,Door accessories,15000
CSV;
        file_put_contents($file, $spreadsheet);

        try {
            $importer = new SpreadsheetImporter;
            $importer->importFromFile($file);

            $model = Product::query()->where('name', 'Import Doors')->firstOrFail()->models()->firstOrFail();
            $shade = $model->designs()->where('name', 'E01')->firstOrFail()->shades()->firstOrFail();
            $design = $model->designs()->where('name', 'E02')->firstOrFail();

            $this->assertSame('Door description', $model->description);
            $this->assertSame('Door accessories', $model->accessories);
            $this->assertSame('10000.00', $shade->starting_price);
            $this->assertSame('15000.00', $design->starting_price);

            file_put_contents($file, str_replace(
                ['Door description', 'Door accessories', ',10000', ',15000'],
                ['Updated door description', 'Updated accessories', ',11000', ',16000'],
                $spreadsheet,
            ));
            $importer->importFromFile($file);

            $this->assertSame('Updated door description', $model->fresh()->description);
            $this->assertSame('Updated accessories', $model->fresh()->accessories);
            $this->assertSame('11000.00', $shade->fresh()->starting_price);
            $this->assertSame('16000.00', $design->fresh()->starting_price);
        } finally {
            unlink($file);
        }
    }

    public function test_reimport_updates_existing_product_variants_and_adds_new_sizes_without_duplicates(): void
    {
        $file = $this->temporaryCsvPath();
        $spreadsheet = <<<'CSV'
Product Name,Category,Models,Designs,Design Code,Shades,Sizes,Description,Accessories,Starting Price
Update Doors,Doors,Classic,E01,E-1,Oak,900x2000,Door description,Door accessories,10000
CSV;
        file_put_contents($file, $spreadsheet);

        try {
            $importer = new SpreadsheetImporter;
            $importer->importFromFile($file);
            file_put_contents($file, <<<'CSV'
Product Name,Category,Models,Designs,Design Code,Shades,Sizes,Description,Accessories,Starting Price
UPDATE DOORS,Interior doors,CLASSIC,e01,E-2,OAK,"900x2000;910x2000",Updated description,Updated accessories,12000
CSV
            );
            $result = $importer->importFromFile($file);
        } finally {
            unlink($file);
        }

        $product = Product::query()->where('slug', 'update-doors')->firstOrFail();
        $model = $product->models()->firstOrFail();
        $design = $model->designs()->firstOrFail();
        $shade = $design->shades()->firstOrFail();

        $this->assertSame([], $result['errors']);
        $this->assertSame(1, Product::query()->count());
        $this->assertSame('UPDATE DOORS', $product->name);
        $this->assertSame('Interior doors', $product->category);
        $this->assertSame(1, $product->models()->count());
        $this->assertSame('CLASSIC', $model->name);
        $this->assertSame('Updated description', $model->description);
        $this->assertSame('Updated accessories', $model->accessories);
        $this->assertSame('e01', $design->name);
        $this->assertSame('E-2', $design->code);
        $this->assertSame('OAK', $shade->name);
        $this->assertSame('12000.00', $shade->starting_price);
        $this->assertSame(2, $shade->sizes()->count());
    }

    private function temporaryCsvPath(): string
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'products');
        $csvPath = $temporaryFile.'.csv';
        rename($temporaryFile, $csvPath);

        return $csvPath;
    }
}
