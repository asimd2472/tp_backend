<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductCatalogController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::query()
            ->with('models')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $payload = $products->map(function (Product $product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'category' => $product->category,
                'main_image_url' => $product->thumbnail_url,
                'models' => $product->models->map(function (ProductModel $model) {
                    return [
                        'id' => $model->id,
                        'name' => $model->name,
                        'slug' => $model->slug,
                        'starting_price' => (float) $model->starting_price,
                        'main_image_url' => $model->thumbnail_url,
                        'is_default' => (bool) $model->is_default,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'products' => $payload,
        ]);
    }

    public function modelsByProduct(string $slug): JsonResponse
    {
        $product = Product::query()
            ->select(['id', 'name', 'slug', 'category', 'main_image'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $models = ProductModel::query()
            ->select(['id', 'product_id', 'name', 'slug', 'main_image', 'is_default'])
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(function (ProductModel $model) {
                return [
                    'id' => $model->id,
                    'name' => $model->name,
                    'slug' => $model->slug,
                    'main_image_url' => $model->thumbnail_url,
                    'is_default' => (bool) $model->is_default,
                ];
            })->values();

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'category' => $product->category,
                'main_image_url' => $product->thumbnail_url,
            ],
            'models' => $models,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $model = ProductModel::query()->where('slug', $slug)->with([
            'designs.shades.images' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'designs.shades.sizes' => fn ($query) => $query->orderBy('width_mm')->orderBy('height_mm'),
            'product',
        ])->first();

        if (! $model) {
            $product = Product::query()->where('slug', $slug)->with([
                'models.designs.shades.images' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'models.designs.shades.sizes' => fn ($query) => $query->orderBy('width_mm')->orderBy('height_mm'),
            ])->firstOrFail();

            return response()->json([
                'success' => true,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category,
                    'slug' => $product->slug,
                    'main_image_url' => $product->thumbnail_url,
                ],
                'models' => $product->models->map(function (ProductModel $item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'slug' => $item->slug,
                        'is_default' => (bool) $item->is_default,
                    ];
                })->values(),
            ]);
        }

        $product = $model->product;

        $designs = $model->designs->map(function ($design) {
            $shades = $design->shades->map(function ($shade) {
                $images = $shade->images->map(fn ($image) => [
                    'id' => $image->id,
                    'url' => $image->url,
                    'is_primary' => (bool) $image->is_primary,
                ])->values();

                $primary = $images->firstWhere('is_primary', true)['url'] ?? ($images->first()['url'] ?? null);

                return [
                    'id' => $shade->id,
                    'name' => $shade->name,
                    'color' => $shade->color_hex,
                    'swatch_url' => $shade->swatch_url,
                    'primary_image_url' => $primary,
                    'gallery' => $images,
                    'sizes' => $shade->sizes->map(fn ($size) => [
                        'id' => $size->id,
                        'width_mm' => (int) $size->width_mm,
                        'height_mm' => (int) $size->height_mm,
                        'label' => $size->width_mm.' × '.$size->height_mm.' mm',
                    ])->values(),
                ];
            })->values();

            return [
                'id' => $design->id,
                'name' => $design->name,
                'code' => $design->code,
                'thumbnail_url' => $design->thumbnail_url,
                'shades' => $shades,
            ];
        })->values();

        $defaultDesign = $designs->first();
        $defaultShade = $defaultDesign['shades']->first() ?? null;
        $defaultSize = $defaultShade['sizes']->first() ?? null;

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'slug' => $product->slug,
                'main_image_url' => $product->thumbnail_url,
            ],
            'model' => [
                'id' => $model->id,
                'name' => $model->name,
                'slug' => $model->slug,
                'short_description' => $model->short_description,
                'description' => $model->description,
                'starting_price' => (float) $model->starting_price,
                'is_premium' => (bool) $model->is_premium,
                'is_active' => (bool) $model->is_active,
                'main_image_url' => $model->thumbnail_url,
            ],
            'designs' => $designs,
            'defaults' => [
                'design_id' => $defaultDesign['id'] ?? null,
                'shade_id' => $defaultShade['id'] ?? null,
                'size_id' => $defaultSize['id'] ?? null,
            ],
        ]);
    }

    public function productDetails(Request $request, ?int $modelId = null): JsonResponse
    {
        $modelId = $modelId ?? $request->integer('model_id');

        abort_unless($modelId, 404, 'Model ID is required.');

        $model = ProductModel::query()
            ->with([
                'product',
                'designs.shades.images' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'designs.shades.sizes' => fn ($query) => $query->orderBy('width_mm')->orderBy('height_mm'),
            ])
            ->findOrFail($modelId);

        $product = $model->product;

        $designs = $model->designs->map(function ($design) {
            $shades = $design->shades->map(function ($shade) {
                $gallery = $shade->images->map(fn ($image) => [
                    'id' => $image->id,
                    'url' => $this->resolveImageUrl($image->image_path),
                    'is_primary' => (bool) $image->is_primary,
                ])->values();

                $firstPrimary = $gallery->firstWhere('is_primary', true);
                $firstImage = $gallery->first();
                $defaultImage = ($firstPrimary['url'] ?? null) ?? ($firstImage['url'] ?? null) ?? $this->resolveImageUrl(null);

                return [
                    'id' => $shade->id,
                    'name' => $shade->name,
                    'color' => $shade->color_hex,
                    'swatch_url' => $this->resolveImageUrl($shade->swatch_image, 'uploads/default/model.webp'),
                    'primary_image_url' => $defaultImage,
                    'gallery' => $gallery,
                    'sizes' => $shade->sizes->map(fn ($size) => [
                        'id' => $size->id,
                        'width_mm' => (int) $size->width_mm,
                        'height_mm' => (int) $size->height_mm,
                        'label' => $size->width_mm.' × '.$size->height_mm.' mm',
                    ])->values(),
                ];
            })->values();

            return [
                'id' => $design->id,
                'name' => $design->name,
                'code' => $design->code,
                'thumbnail_url' => $this->resolveImageUrl($design->thumbnail_image, 'uploads/default/model.webp'),
                'shades' => $shades,
            ];
        })->values();

        $defaultDesign = $designs->first();
        $defaultShade = null;
        $defaultSize = null;

        if ($defaultDesign) {
            $defaultShade = $defaultDesign['shades']->first() ?? null;
            if ($defaultShade) {
                $defaultSize = $defaultShade['sizes']->first() ?? null;
            }
        }

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'slug' => $product->slug,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'main_image_url' => $this->resolveImageUrl($product->main_image, 'uploads/default/model.webp'),
            ],
            'model' => [
                'id' => $model->id,
                'name' => $model->name,
                'slug' => $model->slug,
                'short_description' => $model->short_description,
                'description' => $model->description,
                'starting_price' => (float) $model->starting_price,
                'is_premium' => (bool) $model->is_premium,
                'is_active' => (bool) $model->is_active,
                'is_default' => (bool) $model->is_default,
                'main_image_url' => $this->resolveImageUrl($model->main_image, 'uploads/default/model.webp'),
            ],
            'designs' => $designs,
            'defaults' => [
                'design_id' => $defaultDesign['id'] ?? null,
                'shade_id' => $defaultShade['id'] ?? null,
                'size_id' => $defaultSize['id'] ?? null,
            ],
        ]);
    }

    protected function resolveImageUrl(?string $path, string $default = 'uploads/default/model.webp'): string
    {
        if (blank($path)) {
            return asset($default);
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $normalized = ltrim($path, '/');

        if (str_starts_with($normalized, 'uploads/') || str_starts_with($normalized, 'storage/')) {
            return asset('/'.$normalized);
        }

        return asset('uploads/'.$normalized);
    }
}
