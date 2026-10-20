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
    public function importFromFile(string $path): array
    {
        $rows = $this->readRows($path);
        if ($rows === []) {
            return ['products' => 0, 'designs' => 0, 'shades' => 0, 'errors' => ['No data was found in the uploaded spreadsheet.']];
        }

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

            DB::transaction(function () use ($batch, &$products, &$designs, &$shades, &$errors, &$productCache, &$designCache, &$shadeCache, $offset) {
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
                        $product = Product::query()->firstOrCreate([
                            'slug' => $this->slugify($productName),
                        ], [
                            'name' => $productName,
                            'category' => $normalized['category'] ?? null,
                            'model' => null,
                            'starting_price' => 0,
                            'description' => null,
                        ]);

                        $productCache[$productKey] = $product;
                        $products++;
                    }

                    $product = $productCache[$productKey];
                    $modelKey = $product->getKey().':'.$this->cacheKey($modelName !== '' ? $modelName : 'Default');

                    if (! isset($productCache[$modelKey])) {
                        $productCache[$modelKey] = $product->models()->firstOrCreate([
                            'name' => $modelName !== '' ? $modelName : 'Default',
                        ], [
                            'slug' => $this->buildModelSlug($productName, $modelName),
                            'starting_price' => 0,
                            'description' => null,
                            'is_active' => true,
                        ]);
                    }

                    $model = $productCache[$modelKey];

                    $designName = trim((string) ($normalized['design'] ?? ''));
                    if ($designName !== '') {
                        $designKey = $model->getKey().':'.$this->cacheKey($designName);
                        if (! isset($designCache[$designKey])) {
                            $designCache[$designKey] = $model->designs()->firstOrCreate([
                                'name' => $designName,
                            ], [
                                'product_id' => $product->id,
                                'product_model_id' => $model->id,
                                'code' => null,
                                'sort_order' => 0,
                            ]);
                        }
                        $design = $designCache[$designKey];
                        $designs++;

                        $shadeName = trim((string) ($normalized['shade'] ?? ''));
                        if ($shadeName !== '') {
                            $shadeKey = $design->getKey().':'.$this->cacheKey($shadeName);
                            if (! isset($shadeCache[$shadeKey])) {
                                $shadeCache[$shadeKey] = $design->shades()->firstOrCreate([
                                    'name' => $shadeName,
                                ], [
                                    'color_hex' => $normalized['color_hex'] ?? null,
                                    'sort_order' => 0,
                                ]);
                            }
                            $shade = $shadeCache[$shadeKey];
                            $shades++;

                            $sizeString = trim((string) ($normalized['size'] ?? ''));
                            $sizeValues = preg_split('/[;,|\\n]+/', $sizeString) ?: [];

                            foreach ($sizeValues as $value) {
                                $clean = trim((string) $value);
                                if ($clean === '') {
                                    continue;
                                }

                                $pair = preg_split('/[xX]/', $clean, 2);
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

                                $shade->sizes()->firstOrCreate([
                                    'width_mm' => $width,
                                    'height_mm' => $height,
                                ]);
                            }
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
            'color_hex' => $lookup['color'] ?? $lookup['colour'] ?? $lookup['colorhex'] ?? $lookup['color_hex'] ?? null,
            'size' => $lookup['sizes'] ?? $lookup['size'] ?? null,
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
