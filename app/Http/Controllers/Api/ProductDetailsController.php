<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\WindowProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductDetailsController extends Controller
{
    public function __construct(
        private readonly ProductCatalogController $products,
        private readonly WindowCatalogController $windows,
    ) {}

    public function __invoke(Request $request, ?int $modelId = null): JsonResponse
    {
        if ($request->filled('product_slug') && $request->filled('window_slug')) {
            return response()->json([
                'success' => false,
                'message' => 'The provided data is invalid.',
                'errors' => [
                    'product_slug' => ['Provide either product_slug or window_slug, not both.'],
                    'window_slug' => ['Provide either product_slug or window_slug, not both.'],
                ],
            ], 422);
        }

        if ($request->filled('window_slug')) {
            return $this->windows->windowDetails($request, $modelId, sharedProductDetails: true);
        }

        if ($request->filled('product_slug')
            && ! Product::query()->where('slug', $request->query('product_slug'))->exists()
            && WindowProduct::query()->where('slug', $request->query('product_slug'))->exists()) {
            return $this->windows->windowDetails($request, $modelId, sharedProductDetails: true);
        }

        return $this->products->productDetails($request, $modelId);
    }
}
