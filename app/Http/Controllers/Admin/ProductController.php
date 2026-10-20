<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductDesign;
use App\Models\ProductImage;
use App\Models\ProductModel;
use App\Models\ProductShade;
use App\Models\ProductSize;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::query()->latest()->paginate(15);

        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product,
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'alpha_dash', 'unique:products,slug'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'starting_price' => ['nullable', 'numeric', 'min:0'],
            'is_premium' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'main_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'models' => ['nullable', 'array'],
            'designs' => ['nullable', 'array'],
        ]);

        $product = DB::transaction(function () use ($request, $validated) {
            $product = Product::create([
                'name' => $validated['name'],
                'category' => $validated['category'] ?? null,
                'model' => $validated['model'] ?? null,
                'slug' => $this->resolveSlug($validated['slug'] ?? null, $validated['name']),
                'short_description' => $validated['short_description'] ?? null,
                'description' => $validated['description'] ?? null,
                'starting_price' => (float) ($validated['starting_price'] ?? 0),
                'is_premium' => (bool) ($validated['is_premium'] ?? false),
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);

            if ($request->hasFile('main_image')) {
                $product->update(['main_image' => $request->file('main_image')->store('products', 'public')]);
            }

            $this->syncModels($product, $request->input('models', $request->input('designs', [])));

            return $product;
        });

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    public function edit(Product $product): View
    {
        $product->load([
            'models.designs.shades.images' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'models.designs.shades.sizes' => fn ($query) => $query->orderBy('width_mm')->orderBy('height_mm'),
        ]);

        return view('admin.products.form', [
            'product' => $product,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'alpha_dash', Rule::unique('products', 'slug')->ignore($product->id)],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'starting_price' => ['nullable', 'numeric', 'min:0'],
            'is_premium' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'main_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'models' => ['nullable', 'array'],
            'designs' => ['nullable', 'array'],
        ]);

        DB::transaction(function () use ($request, $product, $validated) {
            $data = [
                'name' => $validated['name'],
                'category' => $validated['category'] ?? null,
                'model' => $validated['model'] ?? null,
                'slug' => $this->resolveSlug($validated['slug'] ?? null, $validated['name'], $product),
                'short_description' => $validated['short_description'] ?? null,
                'description' => $validated['description'] ?? null,
                'starting_price' => (float) ($validated['starting_price'] ?? 0),
                'is_premium' => (bool) ($validated['is_premium'] ?? false),
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ];

            if ($request->hasFile('main_image')) {
                if ($product->main_image) {
                    Storage::disk('public')->delete($product->main_image);
                }

                $data['main_image'] = $request->file('main_image')->store('products', 'public');
            }

            $product->update($data);
            $this->syncModels($product, $request->input('models', $request->input('designs', [])));
        });

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        DB::transaction(function () use ($product) {
            foreach ($product->designs as $design) {
                foreach ($design->shades as $shade) {
                    foreach ($shade->images as $image) {
                        if ($image->image_path) {
                            Storage::disk('public')->delete($image->image_path);
                        }
                    }
                }
            }

            $product->delete();
        });

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }

    protected function resolveSlug(?string $slug, string $name, ?Product $product = null): string
    {
        $base = trim((string) ($slug ?: $name));
        $normalized = preg_replace('/[^A-Za-z0-9-]+/', '-', strtolower($base));
        $normalized = trim((string) preg_replace('/-+/', '-', $normalized), '-');
        $normalized = $normalized !== '' ? $normalized : 'product';

        if ($product) {
            $existing = Product::query()->where('slug', $normalized)->whereKeyNot($product->id)->exists();
            if ($existing) {
                $normalized .= '-'.time();
            }

            return $normalized;
        }

        if (Product::query()->where('slug', $normalized)->exists()) {
            $normalized .= '-'.time();
        }

        return $normalized;
    }

    protected function resolveModelSlug(?string $slug, string $name, ?ProductModel $model = null): string
    {
        $base = trim((string) ($slug ?: $name));
        $normalized = preg_replace('/[^A-Za-z0-9-]+/', '-', strtolower($base));
        $normalized = trim((string) preg_replace('/-+/', '-', $normalized), '-');
        $normalized = $normalized !== '' ? $normalized : 'model';

        if ($model) {
            $existing = ProductModel::query()->where('slug', $normalized)->whereKeyNot($model->id)->exists();
            if ($existing) {
                $normalized .= '-'.time();
            }

            return $normalized;
        }

        if (ProductModel::query()->where('slug', $normalized)->exists()) {
            $normalized .= '-'.time();
        }

        return $normalized;
    }

    protected function syncModels(Product $product, array $models): void
    {
        $modelIds = [];

        foreach ($models as $index => $modelData) {
            if (! is_array($modelData)) {
                continue;
            }

            $name = trim((string) ($modelData['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $model = $product->models()->firstOrNew(['id' => $modelData['id'] ?? null]);
            $model->fill([
                'name' => $name,
                'slug' => $this->resolveModelSlug($modelData['slug'] ?? null, $name, $model),
                'short_description' => $modelData['short_description'] ?? null,
                'description' => $modelData['description'] ?? null,
                'starting_price' => (float) ($modelData['starting_price'] ?? 0),
                'is_premium' => (bool) ($modelData['is_premium'] ?? false),
                'is_active' => (bool) ($modelData['is_active'] ?? true),
            ]);

            if (isset($modelData['main_image']) && $modelData['main_image'] instanceof \Illuminate\Http\UploadedFile) {
                $model->main_image = $modelData['main_image']->store('products/models', 'public');
            }

            $model->save();
            $modelIds[] = $model->id;

            $this->syncDesigns($model, $modelData['designs'] ?? []);
        }

        $product->models()->whereNotIn('id', $modelIds)->delete();
    }

    protected function syncDesigns(ProductModel $model, array $designs): void
    {
        $designIds = [];

        foreach ($designs as $index => $designData) {
            if (! is_array($designData)) {
                continue;
            }

            $name = trim((string) ($designData['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $design = $model->designs()->firstOrNew(['id' => $designData['id'] ?? null]);
            $design->fill([
                'product_id' => $model->product_id,
                'product_model_id' => $model->id,
                'name' => $name,
                'code' => $designData['code'] ?? null,
                'sort_order' => $index,
            ]);

            if (isset($designData['thumbnail']) && is_uploaded_file($designData['thumbnail'])) {
                $design->thumbnail_image = $designData['thumbnail']->store('products/designs', 'public');
            }

            $design->save();
            $designIds[] = $design->id;

            $this->syncShades($design, $designData['shades'] ?? []);
        }

        $model->designs()->whereNotIn('id', $designIds)->delete();
    }

    protected function syncShades(ProductDesign $design, array $shades): void
    {
        $shadeIds = [];

        foreach ($shades as $index => $shadeData) {
            if (! is_array($shadeData)) {
                continue;
            }

            $name = trim((string) ($shadeData['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $shade = $design->shades()->firstOrNew(['id' => $shadeData['id'] ?? null]);
            $shade->fill([
                'name' => $name,
                'color_hex' => $shadeData['color_hex'] ?? null,
                'sort_order' => $index,
            ]);

            if (isset($shadeData['swatch']) && is_uploaded_file($shadeData['swatch'])) {
                $shade->swatch_image = $shadeData['swatch']->store('products/shades', 'public');
            }

            $shade->save();
            $shadeIds[] = $shade->id;

            $this->syncShadeImages($shade, $shadeData['images'] ?? []);
            $this->syncSizes($shade, $shadeData['sizes'] ?? []);
        }

        $design->shades()->whereNotIn('id', $shadeIds)->delete();
    }

    protected function syncShadeImages(ProductShade $shade, array $images): void
    {
        $allowed = [];

        foreach ($images as $index => $imageData) {
            if (! is_array($imageData)) {
                continue;
            }

            if (($imageData['remove'] ?? false) === true) {
                $existing = ProductImage::query()->find($imageData['id'] ?? null);
                if ($existing) {
                    Storage::disk('public')->delete($existing->image_path);
                    $existing->delete();
                }
                continue;
            }

            $file = $imageData['file'] ?? null;
            if ($file && $file instanceof \Illuminate\Http\UploadedFile) {
                $path = $file->store('products/gallery', 'public');
                $allowed[] = $shade->images()->create([
                    'image_path' => $path,
                    'is_primary' => (bool) ($imageData['is_primary'] ?? false),
                    'sort_order' => $index,
                ]);
            }
        }

        if (empty($allowed)) {
            return;
        }

        $primary = $shade->images()->where('is_primary', true)->first();
        if (! $primary) {
            $shade->images()->first()?->update(['is_primary' => true]);
        }
    }

    protected function syncSizes(ProductShade $shade, array $sizes): void
    {
        $sizeIds = [];

        foreach ($sizes as $index => $sizeData) {
            if (! is_array($sizeData)) {
                continue;
            }

            $width = (int) ($sizeData['width_mm'] ?? 0);
            $height = (int) ($sizeData['height_mm'] ?? 0);

            if ($width <= 0 || $height <= 0) {
                continue;
            }

            $size = $shade->sizes()->firstOrCreate([
                'width_mm' => $width,
                'height_mm' => $height,
            ], [
                'sort_order' => $index,
            ]);

            $size->sort_order = $index;
            $size->save();
            $sizeIds[] = $size->id;
        }

        $shade->sizes()->whereNotIn('id', $sizeIds)->delete();
    }
}
