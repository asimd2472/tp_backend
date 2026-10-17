<?php

namespace App\Http\Controllers;

use App\Models\ProductOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class FrenchDoorOptionsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $designs = ProductOption::query()
            ->where('product_slug', ProductOption::PRODUCT_SLUG)
            ->where('type', 'design')
            ->with(['children' => fn ($query) => $query->where('type', 'shade')->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->map(fn (ProductOption $design) => [
                'id' => $design->id,
                'name' => $design->name,
                'is_default' => $design->is_default,
                'shades' => $design->children->map(fn (ProductOption $shade) => [
                    'id' => $shade->id,
                    'name' => $shade->name,
                    'color' => $shade->color_hex,
                    'image_url' => $shade->image_path ? asset('uploads/' . ltrim($shade->image_path, '/')) : null,
                    'is_default' => $shade->is_default,
                ])->values(),
            ])->values();

        $sizes = ProductOption::query()
            ->where('product_slug', ProductOption::PRODUCT_SLUG)
            ->where('type', 'size')
            ->orderBy('width_mm')
            ->orderBy('height_mm')
            ->get()
            ->map(fn (ProductOption $size) => [
                'id' => $size->id,
                'width_mm' => $size->width_mm,
                'height_mm' => $size->height_mm,
                'label' => $size->width_mm.' × '.$size->height_mm,
                'is_default' => $size->is_default,
            ])->values();

        $defaultDesign = $designs->firstWhere('is_default', true);
        $defaultShade = $designs
            ->flatMap(fn (array $design) => $design['shades'])
            ->firstWhere('is_default', true);

        return response()->json([
            'product' => ProductOption::PRODUCT_SLUG,
            'designs' => $designs,
            'sizes' => $sizes,
            'defaults' => [
                'design_id' => $defaultDesign['id'] ?? null,
                'shade_id' => $defaultShade['id'] ?? null,
                'size_id' => $sizes->firstWhere('is_default', true)['id'] ?? null,
            ],
        ]);
    }
}
