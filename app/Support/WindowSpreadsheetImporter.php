<?php

namespace App\Support;

use App\Models\WindowModel;
use App\Models\WindowProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use UnexpectedValueException;

class WindowSpreadsheetImporter
{
    public function __construct(private readonly SpreadsheetImporter $spreadsheetReader) {}

    /**
     * @return array{products:int,models:int,shades:int,sizes:int,errors:array<int,string>}
     */
    public function importFromFile(string $path): array
    {
        $rows = $this->spreadsheetReader->rowsFromFile($path);
        if ($rows === []) {
            return ['products' => 0, 'models' => 0, 'shades' => 0, 'sizes' => 0, 'errors' => ['No data was found in the uploaded spreadsheet.']];
        }

        $normalizedRows = [];
        $errors = [];
        foreach ($rows as $index => $row) {
            $values = [];
            foreach ($row as $header => $value) {
                $values[$this->normalizeHeader((string) $header)] = trim((string) $value);
            }

            $product = $values['productname'] ?? $values['products'] ?? $values['product'] ?? '';
            if ($product === '') {
                continue;
            }

            $model = $values['models'] ?? $values['model'] ?? '';
            $shade = $values['shades'] ?? $values['shade'] ?? '';
            $sizes = $values['sizes'] ?? $values['size'] ?? '';
            $parsedSizes = [];

            foreach (preg_split('/[,;|\\r\\n]+/', $sizes) ?: [] as $size) {
                $size = trim($size);
                if ($size === '') {
                    continue;
                }

                if (preg_match('/^(\\d+)\\s*[xX]\\s*(\\d+)$/', $size, $matches) !== 1) {
                    $errors[] = 'Invalid size value on row '.($index + 2).': '.$size;

                    continue;
                }

                $width = (int) $matches[1];
                $height = (int) $matches[2];
                if ($width < 1 || $height < 1 || $width > 65535 || $height > 65535) {
                    $errors[] = 'Invalid size value on row '.($index + 2).': '.$size;

                    continue;
                }

                $parsedSizes[] = [$width, $height];
            }

            $normalizedRows[] = [
                'product' => $product,
                'category' => $values['category'] ?? $values['categories'] ?? $values['productcategory'] ?? null,
                'model' => $model !== '' ? $model : 'Default',
                'shade' => $shade,
                'sizes' => $parsedSizes,
                'row' => $index + 2,
            ];
        }

        if ($errors !== []) {
            return ['products' => 0, 'models' => 0, 'shades' => 0, 'sizes' => 0, 'errors' => $errors];
        }

        if ($normalizedRows === []) {
            return ['products' => 0, 'models' => 0, 'shades' => 0, 'sizes' => 0, 'errors' => ['No window products were found in the uploaded spreadsheet.']];
        }

        $shadeColors = $this->loadShadeColors();
        $counts = ['products' => 0, 'models' => 0, 'shades' => 0, 'sizes' => 0];

        DB::transaction(function () use ($normalizedRows, $shadeColors, &$counts): void {
            $productCache = [];
            $modelCache = [];
            $shadeCache = [];

            foreach ($normalizedRows as $row) {
                $productKey = mb_strtolower($row['product']);
                if (! isset($productCache[$productKey])) {
                    $slug = $this->productSlug($row['product']);
                    $product = WindowProduct::query()->firstOrCreate(['slug' => $slug], [
                        'name' => $row['product'],
                        'category' => $row['category'] ?: null,
                        'starting_price' => 0,
                    ]);
                    $productCache[$productKey] = $product;
                    $counts['products'] += (int) $product->wasRecentlyCreated;
                }

                $product = $productCache[$productKey];
                $modelKey = $product->getKey().':'.mb_strtolower($row['model']);
                if (! isset($modelCache[$modelKey])) {
                    $model = $product->models()->firstOrCreate(['name' => $row['model']], [
                        'slug' => $this->uniqueSlug($row['product'].' '.$row['model'], WindowModel::class),
                        'starting_price' => 0,
                        'is_active' => true,
                    ]);
                    $modelCache[$modelKey] = $model;
                    $counts['models'] += (int) $model->wasRecentlyCreated;
                }

                if ($row['shade'] === '') {
                    continue;
                }

                $model = $modelCache[$modelKey];
                $shadeKey = $model->getKey().':'.mb_strtolower($row['shade']);
                if (! isset($shadeCache[$shadeKey])) {
                    $color = $this->resolveShadeColor($row['shade'], $shadeColors);
                    $shade = $model->shades()->firstOrCreate(['name' => $row['shade']], [
                        'color_hex' => $color,
                        'sort_order' => 0,
                    ]);
                    if ($shade->color_hex !== $color) {
                        $shade->update(['color_hex' => $color]);
                    }
                    $shadeCache[$shadeKey] = $shade;
                    $counts['shades'] += (int) $shade->wasRecentlyCreated;
                }

                $shade = $shadeCache[$shadeKey];
                foreach ($row['sizes'] as [$width, $height]) {
                    $size = $shade->sizes()->firstOrCreate([
                        'width_mm' => $width,
                        'height_mm' => $height,
                    ]);
                    $counts['sizes'] += (int) $size->wasRecentlyCreated;
                }
            }
        });

        return [...$counts, 'errors' => []];
    }

    private function loadShadeColors(): array
    {
        $path = base_path('public/excel/tata_pravesh_colors.json');
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('Unable to read the product shade color map at '.$path);
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('Unable to read the product shade color map at '.$path);
        }

        $entries = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($entries)) {
            throw new UnexpectedValueException('The product shade color map must contain a JSON array.');
        }

        $colors = [];
        foreach ($entries as $entry) {
            if (! is_array($entry) || ! is_string($entry['name'] ?? null) || trim($entry['name']) === '') {
                throw new UnexpectedValueException('Each product shade color entry must have a non-empty name.');
            }

            $color = $entry['hex_code'] ?? null;
            if ($color !== null && (! is_string($color) || preg_match('/^#[0-9a-fA-F]{6}$/', $color) !== 1)) {
                throw new UnexpectedValueException('Invalid hex_code for product shade '.$entry['name'].'.');
            }

            $colors[mb_strtolower(trim($entry['name']))] = $color;
        }

        return $colors;
    }

    private function resolveShadeColor(string $name, array $colors): string
    {
        $color = $colors[mb_strtolower(trim($name))] ?? config('app.default_color_code');
        if (! is_string($color) || preg_match('/^#[0-9a-fA-F]{6}$/', $color) !== 1) {
            throw new RuntimeException('DEFAULT_COLOR_CODE must be configured as a six-digit hex color.');
        }

        return $color;
    }

    private function uniqueSlug(string $value, string $modelClass): string
    {
        $base = Str::slug($value) ?: 'window';
        $slug = $base;
        $suffix = 2;
        while ($modelClass::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function productSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'window';
        $existing = WindowProduct::query()->where('slug', $base)->first();
        if (! $existing || mb_strtolower($existing->name) === mb_strtolower($name)) {
            return $base;
        }

        return $this->uniqueSlug($name, WindowProduct::class);
    }

    private function normalizeHeader(string $header): string
    {
        return (string) preg_replace('/[^a-z0-9]+/', '', strtolower(trim($header)));
    }
}
