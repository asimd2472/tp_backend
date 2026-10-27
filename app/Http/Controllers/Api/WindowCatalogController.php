<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WindowModel;
use App\Models\WindowProduct;
use App\Models\WindowShade;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class WindowCatalogController extends Controller
{
    public function index(): JsonResponse
    {
        $windows = WindowProduct::query()
            ->with('models')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (WindowProduct $window) => [
                'id' => $window->id,
                'name' => $window->name,
                'slug' => $window->slug,
                'category' => $window->category,
                'main_image_url' => $window->thumbnail_url,
                'models' => $window->models->map(fn (WindowModel $model) => [
                    'id' => $model->id,
                    'name' => $model->name,
                    'slug' => $model->slug,
                    'starting_price' => (float) $model->starting_price,
                    'main_image_url' => $model->main_image_url,
                    'is_default' => (bool) $model->is_default,
                ])->values(),
            ])->values();

        return response()->json(['success' => true, 'windows' => $windows]);
    }

    public function modelsByWindow(string $slug): JsonResponse
    {
        $window = WindowProduct::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $models = $window->models()
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(fn (WindowModel $model) => [
                'id' => $model->id,
                'name' => $model->name,
                'slug' => $model->slug,
                'main_image_url' => $model->main_image_url,
                'is_default' => (bool) $model->is_default,
            ])->values();

        return response()->json([
            'success' => true,
            'window' => [
                'id' => $window->id,
                'name' => $window->name,
                'slug' => $window->slug,
                'category' => $window->category,
                'main_image_url' => $window->thumbnail_url,
            ],
            'models' => $models,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $model = WindowModel::query()
            ->where('slug', $slug)
            ->with(['product', 'shades.images', 'shades.sizes'])
            ->first();

        if ($model) {
            return $this->modelResponse($model);
        }

        $window = WindowProduct::query()
            ->where('slug', $slug)
            ->with('models')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'window' => $this->windowPayload($window),
            'models' => $window->models->map(fn (WindowModel $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
                'is_default' => (bool) $item->is_default,
            ])->values(),
        ]);
    }

    public function windowDetails(Request $request, ?int $modelId = null, bool $sharedProductDetails = false): JsonResponse
    {
        try {
            $inputModelId = $modelId ?? $request->query('model_id');
            $validated = validator([
                'window_slug' => $request->query('window_slug', $request->query('product_slug')),
                'model_id' => blank($inputModelId) ? null : $inputModelId,
            ], [
                'window_slug' => ['required', 'string'],
                'model_id' => ['nullable', 'integer', 'min:1'],
            ])->validate();

            $window = WindowProduct::query()->where('slug', $validated['window_slug'])->firstOrFail();
            $query = $window->models()->with(['shades.images', 'shades.sizes']);
            if ($validated['model_id'] !== null) {
                $query->whereKey($validated['model_id']);
            } else {
                $query->where('is_default', true);
            }

            return $this->detailsResponse($window, $query->firstOrFail(), $sharedProductDetails);
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'The provided data is invalid.',
                'errors' => $exception->errors(),
            ], 422);
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Window or model not found.'], 404);
        } catch (Throwable $exception) {
            Log::error('Failed to retrieve window details.', [
                'window_slug' => $request->query('window_slug', $request->query('product_slug')),
                'model_id' => $modelId ?? $request->query('model_id'),
                'exception' => $exception,
            ]);

            return response()->json(['success' => false, 'message' => 'Unable to retrieve window details.'], 500);
        }
    }

    private function modelResponse(WindowModel $model): JsonResponse
    {
        return $this->detailsResponse($model->product, $model);
    }

    private function detailsResponse(WindowProduct $window, WindowModel $model, bool $sharedProductDetails = false): JsonResponse
    {
        $shades = $model->shades->map(fn (WindowShade $shade) => $this->shadePayload($shade))->values();
        $defaultShade = $shades->first();
        $defaultSize = $defaultShade['sizes']->first() ?? null;

        $response = [
            'success' => true,
            'type' => 'window',
            $sharedProductDetails
                ? 'product'
                : 'window' => $this->windowPayload($window),
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
                'main_image_url' => $model->main_image_url,
            ],
            'defaults' => [
                'shade_id' => $defaultShade['id'] ?? null,
                'size_id' => $defaultSize['id'] ?? null,
            ],
        ];

        if ($sharedProductDetails) {
            $response['designs'] = [];
            $response['shades'] = $shades;
            $response['defaults'] = ['design_id' => null, ...$response['defaults']];
        } else {
            $response['shades'] = $shades;
        }

        return response()->json($response);
    }

    private function shadePayload(WindowShade $shade): array
    {
        $gallery = $shade->images->map(fn ($image) => [
            'id' => $image->id,
            'url' => $image->url,
            'is_primary' => (bool) $image->is_primary,
        ])->values();
        $primary = $gallery->firstWhere('is_primary', true);
        $firstImage = $gallery->first();

        return [
            'id' => $shade->id,
            'name' => $shade->name,
            'color' => $shade->color_hex,
            'swatch_url' => $shade->swatch_url ?? asset('uploads/default/model.webp'),
            'primary_image_url' => $primary['url'] ?? $firstImage['url'] ?? asset('uploads/default/model.webp'),
            'gallery' => $gallery,
            'sizes' => $shade->sizes->map(fn ($size) => [
                'id' => $size->id,
                'width_mm' => (int) $size->width_mm,
                'height_mm' => (int) $size->height_mm,
                'label' => $size->width_mm.' × '.$size->height_mm.' mm',
            ])->values(),
        ];
    }

    private function windowPayload(WindowProduct $window): array
    {
        return [
            'id' => $window->id,
            'name' => $window->name,
            'category' => $window->category,
            'slug' => $window->slug,
            'short_description' => $window->short_description,
            'description' => $window->description,
            'main_image_url' => $window->thumbnail_url,
            'faqs' => $window->faqs ?? [],
            'specifications' => $window->specifications ?? [],
            'rating' => $window->rating === null ? null : (float) $window->rating,
            'review' => $window->review,
        ];
    }
}
