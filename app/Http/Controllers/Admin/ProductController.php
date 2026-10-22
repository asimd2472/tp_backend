<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductDesign;
use App\Models\ProductModel;
use App\Models\ProductShade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
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
            'models.*.is_default' => ['nullable', 'boolean'],
            'models.*.main_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'models.*.designs.*.thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'models.*.designs.*.shades.*.swatch' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'models.*.designs.*.shades.*.images.*.file' => ['nullable', 'array'],
            'models.*.designs.*.shades.*.images.*.file.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
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
                $product->update([
                    'main_image' => $this->storePublicUpload($request->file('main_image'), 'products'),
                ]);
            }

            $models = $request->all()['models'] ?? $request->input('models', []);
            $this->syncModels($product, $models);

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
            'models.*.is_default' => ['nullable', 'boolean'],
            'models.*.main_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'models.*.designs.*.thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'models.*.designs.*.shades.*.swatch' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'models.*.designs.*.shades.*.images.*.file' => ['nullable', 'array'],
            'models.*.designs.*.shades.*.images.*.file.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
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
                    File::delete(public_path('uploads/'.$product->main_image));
                }

                $data['main_image'] = $this->storePublicUpload($request->file('main_image'), 'products');
            }

            $product->update($data);
            $models = $request->all()['models'] ?? $request->input('models', []);
            $this->syncModels($product, $models);
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
                            File::delete(public_path('uploads/'.$image->image_path));
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
            ]);
            foreach ([
                'short_description' => null,
                'description' => null,
                'starting_price' => 0,
                'is_premium' => false,
                'is_active' => true,
            ] as $attribute => $default) {
                if (array_key_exists($attribute, $modelData) || ! $model->exists) {
                    $model->{$attribute} = $modelData[$attribute] ?? $default;
                }
            }
            if (array_key_exists('is_default', $modelData) || ! $model->exists) {
                $model->is_default = (bool) ($modelData['is_default'] ?? false);
            }

            if (isset($modelData['main_image']) && $modelData['main_image'] instanceof UploadedFile) {
                if ($model->main_image) {
                    File::delete(public_path('uploads/'.$model->main_image));
                }

                $model->main_image = $this->storePublicUpload($modelData['main_image'], 'model');
            }

            $model->save();
            if ($model->is_default) {
                $product->models()->whereKeyNot($model->getKey())->update(['is_default' => false]);
            }

            if (isset($modelData['designs']) && is_array($modelData['designs'])) {
                $this->syncDesigns($model, $modelData['designs']);
            }
        }
    }

    protected function syncDesigns(ProductModel $model, array $designs): void
    {
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
                'sort_order' => $index,
            ]);
            if (array_key_exists('code', $designData) || ! $design->exists) {
                $design->code = $designData['code'] ?? null;
            }

            if (($designData['thumbnail'] ?? null) instanceof UploadedFile) {
                if ($design->thumbnail_image) {
                    File::delete(public_path('uploads/'.$design->thumbnail_image));
                }

                $design->thumbnail_image = $this->storePublicUpload($designData['thumbnail'], 'products/designs');
            }

            $design->save();

            if (isset($designData['shades']) && is_array($designData['shades'])) {
                $this->syncShades($design, $designData['shades']);
            }
        }
    }

    protected function syncShades(ProductDesign $design, array $shades): void
    {
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
                'sort_order' => $index,
            ]);
            if (array_key_exists('color_hex', $shadeData) || ! $shade->exists) {
                $shade->color_hex = $shadeData['color_hex'] ?? null;
            }

            if (($shadeData['swatch'] ?? null) instanceof UploadedFile) {
                $shade->swatch_image = $this->storePublicUpload($shadeData['swatch'], 'products/shades');
            }

            $shade->save();

            if (isset($shadeData['images']) && is_array($shadeData['images'])) {
                $this->syncShadeImages($shade, $shadeData['images']);
            }
            if (isset($shadeData['sizes']) && is_array($shadeData['sizes'])) {
                $this->syncSizes($shade, $shadeData['sizes']);
            }
        }
    }

    protected function syncShadeImages(ProductShade $shade, array $images): void
    {
        foreach ($images as $index => $imageData) {
            if (! is_array($imageData)) {
                continue;
            }

            $file = $imageData['file'] ?? null;
            $files = is_array($file) ? $file : [$file];
            foreach ($files as $fileIndex => $uploadedFile) {
                if (! $uploadedFile instanceof UploadedFile) {
                    continue;
                }

                $path = $this->storePublicUpload($uploadedFile, 'products/gallery');
                $hasPrimary = $shade->images()->where('is_primary', true)->exists();
                $shade->images()->create([
                    'image_path' => $path,
                    'is_primary' => (bool) ($imageData['is_primary'] ?? false) || ! $hasPrimary,
                    'sort_order' => $index + $fileIndex,
                ]);
            }
        }
    }

    protected function storePublicUpload(UploadedFile $file, string $directory): string
    {
        $relativeDirectory = trim($directory, '/\\');
        $destination = public_path('uploads/'.$relativeDirectory);
        File::ensureDirectoryExists($destination);

        $filename = Str::uuid().'.'.$file->extension();
        $file->move($destination, $filename);

        return $relativeDirectory.'/'.$filename;
    }

    protected function syncSizes(ProductShade $shade, array $sizes): void
    {
        foreach ($sizes as $index => $sizeData) {
            if (! is_array($sizeData)) {
                continue;
            }

            $width = (int) ($sizeData['width_mm'] ?? 0);
            $height = (int) ($sizeData['height_mm'] ?? 0);

            if ($width <= 0 || $height <= 0) {
                continue;
            }

            $size = $shade->sizes()->firstOrNew(['id' => $sizeData['id'] ?? null]);
            $size->fill([
                'width_mm' => $width,
                'height_mm' => $height,
                'sort_order' => $index,
            ]);
            $size->save();
        }
    }
}
