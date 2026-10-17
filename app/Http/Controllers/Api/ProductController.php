<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function french_door(): JsonResponse
    {
        $options = ProductOption::query()
            ->where('product_slug', ProductOption::PRODUCT_SLUG)
            ->whereIn('type', ['design', 'shade', 'size'])
            ->orderBy('id')
            ->get([
                'id',
                'type',
                'parent_id',
                'name',
                'width_mm',
                'height_mm',
                'color_hex',
                'image_path',
                'is_default',
            ]);

        $shadesByDesign = $options
            ->where('type', 'shade')
            ->groupBy('parent_id');

        $designs = $options
            ->where('type', 'design')
            ->map(fn (ProductOption $design) => [
                'id' => $design->id,
                'name' => $design->name,
                'is_default' => $design->is_default,
                'shades' => $shadesByDesign
                    ->get($design->id, collect())
                    ->map(fn (ProductOption $shade) => [
                        'id' => $shade->id,
                        'name' => $shade->name,
                        'color' => $shade->color_hex,
                        'image_url' => $shade->image_path
                            ? asset('uploads/' . ltrim($shade->image_path, '/'))
                            : null,
                        'is_default' => $shade->is_default,
                    ])
                    ->values(),
            ])
            ->values();

        $sizes = $options
            ->where('type', 'size')
            ->sortBy(fn (ProductOption $size) => [$size->width_mm, $size->height_mm])
            ->map(fn (ProductOption $size) => [
                'id' => $size->id,
                'width_mm' => $size->width_mm,
                'height_mm' => $size->height_mm,
                'label' => $size->width_mm.' × '.$size->height_mm.' mm',
                'is_default' => $size->is_default,
            ])
            ->values();

        $defaultDesign = $designs->firstWhere('is_default', true);
        $defaultShade = $designs
            ->flatMap(fn (array $design) => $design['shades'])
            ->firstWhere('is_default', true);
        $defaultSize = $sizes->firstWhere('is_default', true);

        return response()->json([
            'success' => true,
            'product' => [
                'name' => 'French-door',
                'slug' => ProductOption::PRODUCT_SLUG,
            ],
            'designs' => $designs,
            'sizes' => $sizes,
            'defaults' => [
                'design_id' => $defaultDesign['id'] ?? null,
                'shade_id' => $defaultShade['id'] ?? null,
                'size_id' => $defaultSize['id'] ?? null,
            ],
        ]);
    }
}
