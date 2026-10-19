<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductCatalogController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $product = Product::query()->where('slug', $slug)->with([
            'designs.shades.images' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'designs.shades.sizes' => fn ($query) => $query->orderBy('width_mm')->orderBy('height_mm'),
        ])->firstOrFail();

        $designs = $product->designs->map(function ($design) {
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
                'model' => $product->model,
                'slug' => $product->slug,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'starting_price' => (float) $product->starting_price,
                'is_premium' => (bool) $product->is_premium,
                'is_active' => (bool) $product->is_active,
                'main_image_url' => $product->thumbnail_url,
            ],
            'designs' => $designs,
            'defaults' => [
                'design_id' => $defaultDesign['id'] ?? null,
                'shade_id' => $defaultShade['id'] ?? null,
                'size_id' => $defaultSize['id'] ?? null,
            ],
        ]);
    }
}
