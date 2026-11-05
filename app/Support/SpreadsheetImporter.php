<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductDesign;
use App\Models\ProductModel;
use App\Models\ProductShade;
use App\Models\ProductSize;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SpreadsheetImporter
{
    public function rowsFromFile(string $path): array
    {
        return $this->readRows($path);
    }

    public function importFromFile(string $path): array
    {
        $rows = $this->readRows($path);
        if ($rows === []) {
            return ['products' => 0, 'designs' => 0, 'shades' => 0, 'errors' => ['No data was found in the uploaded spreadsheet.']];
        }

        $shadeColors = $this->loadShadeColors();
        $products = 0;
        $designs = 0;
        $shades = 0;
        $errors = [];

        $productCache = [];
        $designCache = [];
        $shadeCache = [];
        $batchSize = 100;

        for ($offset = 0; $offset < count($rows); $offset += $batchSize) {
            $batch = array_slice($rows, $offset, $batchSize);

            DB::transaction(function () use ($batch, &$products, &$designs, &$shades, &$errors, &$productCache, &$designCache, &$shadeCache, $shadeColors, $offset) {
                foreach ($batch as $index => $row) {
                    $globalIndex = $offset + $index;
                    $normalized = $this->normalizeRow($row);
                    if ($normalized['product'] === null || trim((string) $normalized['product']) === '') {
                        continue;
                    }

                    $productName = trim((string) $normalized['product']);
                    $modelName = trim((string) ($normalized['model'] ?? ''));
                    $productKey = $this->cacheKey($productName);

                    if (! isset($productCache[$productKey])) {
                        $product = Product::query()->firstOrNew([
                            'slug' => $this->slugify($productName),
                        ]);
                        $product->name = $productName;
                        $category = trim((string) ($normalized['category'] ?? ''));
                        if ($category !== '') {
                            $product->category = $category;
                        }
                        if (! $product->exists) {
                            $product->model ??= null;
                            $product->starting_price ??= 0;
                            $product->description ??= null;
                        }
                        $product->save();

                        $productCache[$productKey] = $product;
                        $products++;
                    }

                    $product = $productCache[$productKey];
                    $productChanged = false;
                    if ($product->name !== $productName) {
                        $product->name = $productName;
                        $productChanged = true;
                    }
                    $category = trim((string) ($normalized['category'] ?? ''));
                    if ($category !== '' && $product->category !== $category) {
                        $product->category = $category;
                        $productChanged = true;
                    }
                    if ($productChanged) {
                        $product->save();
                    }

                    $modelKey = $product->getKey().':'.$this->cacheKey($modelName !== '' ? $modelName : 'Default');

                    if (! isset($productCache[$modelKey])) {
                        $resolvedModelName = $modelName !== '' ? $modelName : 'Default';
                        $model = $product->models()
                            ->whereRaw('LOWER(TRIM(name)) = ?', [$this->cacheKey($resolvedModelName)])
                            ->first();
                        if (! $model) {
                            $model = $product->models()->make([
                                'name' => $resolvedModelName,
                                'slug' => $this->buildModelSlug($productName, $modelName),
                                'starting_price' => 0,
                                'description' => null,
                                'accessories' => null,
                                'is_active' => true,
                            ]);
                            $model->save();
                        }
                        $productCache[$modelKey] = $model;
                    }

                    $model = $productCache[$modelKey];
                    $modelChanged = $model->name !== ($modelName !== '' ? $modelName : 'Default');
                    if ($modelChanged) {
                        $model->name = $modelName !== '' ? $modelName : 'Default';
                    }
                    foreach (['description', 'accessories'] as $attribute) {
                        $value = trim((string) ($normalized[$attribute] ?? ''));
                        if ($value !== '' && $model->{$attribute} !== $value) {
                            $model->{$attribute} = $value;
                            $modelChanged = true;
                        }
                    }
                    if ($modelChanged) {
                        $model->save();
                    }

                    $priceValue = trim((string) ($normalized['starting_price'] ?? ''));
                    $startingPrice = null;
                    if ($priceValue !== '') {
                        if (! is_numeric($priceValue) || (float) $priceValue < 0) {
                            $errors[] = 'Invalid starting price on row '.($globalIndex + 2).': '.$priceValue;
                        } else {
                            $startingPrice = (float) $priceValue;
                        }
                    }

                    $designName = trim((string) ($normalized['design'] ?? ''));
                    if ($designName !== '') {
                        $designKey = $model->getKey().':'.$this->cacheKey($designName);
                        if (! isset($designCache[$designKey])) {
                            $design = $model->designs()
                                ->whereRaw('LOWER(TRIM(name)) = ?', [$this->cacheKey($designName)])
                                ->first();
                            if (! $design) {
                                $design = $model->designs()->make([
                                    'product_id' => $product->id,
                                    'name' => $designName,
                                    'code' => null,
                                    'starting_price' => null,
                                    'sort_order' => 0,
                                ]);
                                $design->save();
                            }
                            $designCache[$designKey] = $design;
                        }
                        $design = $designCache[$designKey];
                        $design->name = $designName;
                        $designCode = trim((string) ($normalized['code'] ?? ''));
                        if ($designCode !== '') {
                            $design->code = $designCode;
                        }
                        $design->save();
                        $designs++;

                        $shadeName = trim((string) ($normalized['shade'] ?? ''));
                        $sizeOwner = $design;
                        if ($shadeName !== '') {
                            $shadeKey = $design->getKey().':'.$this->cacheKey($shadeName);
                            if (! isset($shadeCache[$shadeKey])) {
                                $colorHex = $this->resolveShadeColor($shadeName, $shadeColors);
                                $shade = $design->shades()
                                    ->whereRaw('LOWER(TRIM(name)) = ?', [$this->cacheKey($shadeName)])
                                    ->first();
                                if (! $shade) {
                                    $shade = $design->shades()->make([
                                        'name' => $shadeName,
                                        'color_hex' => $colorHex,
                                        'starting_price' => null,
                                        'sort_order' => 0,
                                    ]);
                                }
                                $shade->name = $shadeName;
                                if ($shade->color_hex !== $colorHex) {
                                    $shade->color_hex = $colorHex;
                                }
                                $shade->save();
                                $shadeCache[$shadeKey] = $shade;
                            }
                            $shade = $shadeCache[$shadeKey];
                            if ($shade->name !== $shadeName) {
                                $shade->name = $shadeName;
                                $shade->save();
                            }
                            if ($startingPrice !== null
                                && ($shade->starting_price === null || (float) $shade->starting_price !== $startingPrice)) {
                                $shade->starting_price = $startingPrice;
                                $shade->save();
                            }
                            $shades++;
                            $sizeOwner = $shade;
                        } elseif ($startingPrice !== null
                            && ($design->starting_price === null || (float) $design->starting_price !== $startingPrice)) {
                            $design->starting_price = $startingPrice;
                            $design->save();
                        }

                        $sizeString = trim((string) ($normalized['size'] ?? ''));
                        $sizeValues = preg_split('/[;,|\\n]+/', $sizeString) ?: [];

                        foreach ($sizeValues as $value) {
                            $clean = trim((string) $value);
                            if ($clean === '') {
                                continue;
                            }

                            $pair = preg_split('/[xX×]/u', $clean, 2);
                            if (count($pair) !== 2) {
                                $errors[] = 'Invalid size value on row '.($globalIndex + 2).': '.$clean;
                                continue;
                            }

                            $width = (int) trim($pair[0]);
                            $height = (int) trim($pair[1]);
                            if ($width <= 0 || $height <= 0) {
                                $errors[] = 'Invalid size value on row '.($globalIndex + 2).': '.$clean;
                                continue;
                            }

                            $sizeOwner->sizes()->firstOrCreate([
                                'width_mm' => $width,
                                'height_mm' => $height,
                            ]);
                        }
                    }
                }
            });
        }

        return [
            'products' => $products,
            'designs' => $designs,
            'shades' => $shades,
            'errors' => $errors,
        ];
    }

    protected function loadShadeColors(): array
    {
        $path = base_path('public/excel/tata_pravesh_colors.json');
        if (! is_file($path) || ! is_readable($path)) {
            throw new \RuntimeException('Unable to read the product shade color map at '.$path);
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the product shade color map at '.$path);
        }

        $entries = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($entries)) {
            throw new \UnexpectedValueException('The product shade color map must contain a JSON array.');
        }

        $colors = [];
        foreach ($entries as $entry) {
            if (! is_array($entry) || ! is_string($entry['name'] ?? null) || trim($entry['name']) === '') {
                throw new \UnexpectedValueException('Each product shade color entry must have a non-empty name.');
            }

            $hexCode = $entry['hex_code'] ?? null;
            if ($hexCode !== null && (! is_string($hexCode) || preg_match('/^#[0-9a-fA-F]{6}$/', $hexCode) !== 1)) {
                throw new \UnexpectedValueException('Invalid hex_code for product shade '.$entry['name'].'.');
            }

            $colors[$this->cacheKey($entry['name'])] = $hexCode;
        }

        return $colors;
    }

    protected function resolveShadeColor(string $shadeName, array $shadeColors): string
    {
        $colorHex = $shadeColors[$this->cacheKey($shadeName)] ?? null;
        if ($colorHex !== null) {
            return $colorHex;
        }

        $defaultColor = config('app.default_color_code');
        if (! is_string($defaultColor) || preg_match('/^#[0-9a-fA-F]{6}$/', $defaultColor) !== 1) {
            throw new \RuntimeException('DEFAULT_COLOR_CODE must be configured as a six-digit hex color.');
        }

        return $defaultColor;
    }

    protected function readRows(string $path): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext === '' || $ext === 'csv') {
            if (! is_file($path)) {
                return [];
            }

            $rows = array_map('str_getcsv', file($path));
            return $this->mapHeaderRows($rows);
        }

        if ($ext === 'xlsx') {
            return $this->mapHeaderRows($this->parseXlsx($path));
        }

        if ($ext === 'ods') {
            return $this->mapHeaderRows($this->parseOds($path));
        }

        return [];
    }

    protected function parseOds(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $content = $zip->getFromName('content.xml');
        $zip->close();

        if (! is_string($content) || $content === '') {
            return [];
        }

        $xml = simplexml_load_string($content);
        if ($xml === false) {
            return [];
        }

        $ns = $xml->getNamespaces(true);
        $tableNs = $ns['table'] ?? null;
        $officeNs = $ns['office'] ?? null;

        $rows = [];
        foreach ($xml->xpath('//table:table-row') as $row) {
            $values = [];
            foreach ($row->xpath('table:table-cell') as $cell) {
                $value = '';
                foreach ($cell->attributes() ?? [] as $attrName => $attrValue) {
                    if ((string) $attrName === 'value-type' && (string) $attrValue === 'string') {
                        $value = (string) ($cell->children($officeNs)->p ?? '');
                    }
                }

                if ($value === '') {
                    $value = trim((string) ($cell->children($officeNs)->p ?? ''));
                }

                $values[] = $value;
            }

            $rows[] = $values;
        }

        return $rows;
    }

    protected function parseXlsx(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = [];
        $sharedFile = $zip->getFromName('xl/sharedStrings.xml');
        if (is_string($sharedFile)) {
            $xml = simplexml_load_string($sharedFile);
            foreach ($xml->si ?? [] as $item) {
                $sharedStrings[] = trim((string) $item->t);
            }
        }

        $sheetFile = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (! is_string($sheetFile) || $sheetFile === '') {
            return [];
        }

        $xml = simplexml_load_string($sheetFile);
        $rows = [];
        foreach ($xml->sheetData->row ?? [] as $row) {
            $values = [];
            foreach ($row->c as $cell) {
                $type = (string) $cell['t'];
                $value = (string) $cell->v;
                if ($type === 's' && isset($sharedStrings[(int) $value])) {
                    $value = $sharedStrings[(int) $value];
                }
                $values[] = trim($value);
            }
            $rows[] = $values;
        }

        return $rows;
    }

    protected function mapHeaderRows(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $header = array_map(fn ($cell) => strtolower(trim((string) $cell)), $rows[0]);
        $mapped = [];

        foreach (array_slice($rows, 1) as $row) {
            $entry = [];
            foreach ($header as $index => $name) {
                $entry[$name] = $row[$index] ?? '';
            }
            $mapped[] = $entry;
        }

        return $mapped;
    }

    protected function normalizeRow(array $row): array
    {
        $lookup = [];
        foreach ($row as $key => $value) {
            $lookup[$this->normalizeHeader((string) $key)] = $value;
        }

        return [
            'product' => $lookup['productname'] ?? $lookup['products'] ?? $lookup['product'] ?? null,
            'category' => $lookup['category'] ?? $lookup['categories'] ?? $lookup['productcategory'] ?? null,
            'model' => $lookup['models'] ?? $lookup['model'] ?? null,
            'design' => $lookup['designs'] ?? $lookup['design'] ?? null,
            'shade' => $lookup['shades'] ?? $lookup['shade'] ?? null,
            'size' => $lookup['sizes'] ?? $lookup['size'] ?? null,
            'description' => $lookup['description'] ?? null,
            'accessories' => $lookup['accessories'] ?? null,
            'starting_price' => $lookup['startingprice'] ?? null,
            'code' => $lookup['designcode'] ?? $lookup['code'] ?? null,
        ];
    }

    protected function normalizeHeader(string $header): string
    {
        $normalized = strtolower(trim($header));
        $normalized = preg_replace('/[^a-z0-9]+/', '', $normalized);

        return (string) $normalized;
    }

    protected function slugify(string $value): string
    {
        $slug = Str::slug($value);

        return $slug !== '' ? $slug : 'product';
    }

    protected function buildModelSlug(string $productName, string $modelName = ''): string
    {
        $base = trim($productName);
        if ($modelName !== '') {
            $base .= ' '.$modelName;
        }

        $slug = $this->slugify($base);
        if (! ProductModel::query()->where('slug', $slug)->exists()) {
            return $slug;
        }

        $suffix = 2;
        do {
            $candidate = $slug.'-'.$suffix;
            $suffix++;
        } while (ProductModel::query()->where('slug', $candidate)->exists());

        return $candidate;
    }

    protected function cacheKey(string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }
}
