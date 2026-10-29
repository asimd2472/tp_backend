<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductDesign;
use App\Models\ProductModel;
use App\Models\ProductShade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
            'faqs' => ['nullable', 'array', 'max:100'],
            'faqs.*.question' => ['required', 'string', 'max:1000'],
            'faqs.*.answer' => ['required', 'string', 'max:10000'],
            'specifications' => ['nullable', 'array', 'max:100'],
            'specifications.*.question' => ['required', 'string', 'max:1000'],
            'specifications.*.answer' => ['required', 'string', 'max:10000'],
            'rating' => ['nullable', 'numeric', 'between:0,5'],
            'review' => ['nullable', 'string', 'max:10000'],
            'main_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'models' => ['nullable', 'array'],
            'designs' => ['nullable', 'array'],
            'models.*.is_default' => ['nullable', 'boolean'],
            'models.*.main_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'models.*.designs.*.thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'models.*.designs.*.sizes' => ['nullable', 'string', 'max:10000'],
            'models.*.designs.*.images.*.file' => ['nullable', 'array'],
            'models.*.designs.*.images.*.file.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'models.*.designs.*.shades.*.swatch' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'models.*.designs.*.shades.*.images.*.file' => ['nullable', 'array'],
            'models.*.designs.*.shades.*.images.*.file.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
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
                'faqs' => $this->normalizeQuestionAnswers($validated['faqs'] ?? []),
                'specifications' => $this->normalizeQuestionAnswers($validated['specifications'] ?? []),
                'rating' => $validated['rating'] ?? null,
                'review' => $validated['review'] ?? null,
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
            'models.designs.images' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'models.designs.sizes' => fn ($query) => $query->orderBy('width_mm')->orderBy('height_mm'),
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
            'faqs' => ['nullable', 'array', 'max:100'],
            'faqs.*.question' => ['required', 'string', 'max:1000'],
            'faqs.*.answer' => ['required', 'string', 'max:10000'],
            'specifications' => ['nullable', 'array', 'max:100'],
            'specifications.*.question' => ['required', 'string', 'max:1000'],
            'specifications.*.answer' => ['required', 'string', 'max:10000'],
            'rating' => ['nullable', 'numeric', 'between:0,5'],
            'review' => ['nullable', 'string', 'max:10000'],
            'main_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'models' => ['nullable', 'array'],
            'designs' => ['nullable', 'array'],
            'models.*.is_default' => ['nullable', 'boolean'],
            'models.*.main_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'models.*.designs.*.thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'models.*.designs.*.sizes' => ['nullable', 'string', 'max:10000'],
            'models.*.designs.*.images.*.file' => ['nullable', 'array'],
            'models.*.designs.*.images.*.file.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'models.*.designs.*.shades.*.swatch' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'models.*.designs.*.shades.*.images.*.file' => ['nullable', 'array'],
            'models.*.designs.*.shades.*.images.*.file.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
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
                'faqs' => $this->normalizeQuestionAnswers($validated['faqs'] ?? []),
                'specifications' => $this->normalizeQuestionAnswers($validated['specifications'] ?? []),
                'rating' => $validated['rating'] ?? null,
                'review' => $validated['review'] ?? null,
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

    private function normalizeQuestionAnswers(array $items): array
    {
        return collect($items)
            ->map(fn (array $item) => [
                'question' => trim($item['question']),
                'answer' => trim($item['answer']),
            ])
            ->values()
            ->all();
    }

    public function uploadModelImage(Request $request, Product $product, ProductModel $model): JsonResponse
    {
        abort_unless((int) $model->product_id === (int) $product->id, 404);

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ]);

        $oldImage = $model->main_image;
        $path = $this->storePublicUpload($validated['image'], 'model');
        $model->update(['main_image' => $path]);

        if ($oldImage && ! str_starts_with($oldImage, 'http')) {
            File::delete(public_path('uploads/'.$oldImage));
        }

        return response()->json([
            'message' => 'Model image uploaded successfully.',
            'image_url' => asset('uploads/'.$path),
        ]);
    }

    public function uploadShadeImage(
        Request $request,
        Product $product,
        ProductModel $model,
        ProductDesign $design,
        ProductShade $shade,
        string $kind,
    ): JsonResponse {
        abort_unless(
            (int) $model->product_id === (int) $product->id
                && (int) $design->product_id === (int) $product->id
                && (int) $design->product_model_id === (int) $model->id
                && (int) $shade->product_design_id === (int) $design->id,
            404,
        );
        abort_unless(in_array($kind, ['swatch', 'gallery'], true), 404);

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ]);
        $file = $validated['image'];

        if ($kind === 'swatch') {
            $oldImage = $shade->swatch_image;
            $path = $this->storePublicUpload($file, 'products/shades');
            $shade->update(['swatch_image' => $path]);

            if ($oldImage && ! str_starts_with($oldImage, 'http')) {
                File::delete(public_path('uploads/'.$oldImage));
            }

            return response()->json([
                'message' => 'Swatch image uploaded successfully.',
                'image_url' => asset('uploads/'.$path),
            ]);
        }

        $path = $this->storePublicUpload($file, 'products/gallery');
        $hasPrimary = $shade->images()->where('is_primary', true)->exists();
        $image = $shade->images()->create([
            'image_path' => $path,
            'is_primary' => ! $hasPrimary,
            'sort_order' => (int) $shade->images()->max('sort_order') + 1,
        ]);

        return response()->json([
            'message' => 'Gallery image uploaded successfully.',
            'image_url' => $image->url,
        ]);
    }

    public function uploadDesignGalleryImage(Request $request, Product $product, ProductModel $model, ProductDesign $design): JsonResponse
    {
        abort_unless(
            (int) $model->product_id === (int) $product->id
                && (int) $design->product_id === (int) $product->id
                && (int) $design->product_model_id === (int) $model->id,
            404,
        );

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ]);

        $image = $design->images()->create([
            'image_path' => $this->storePublicUpload($validated['image'], 'products/design-gallery'),
            'is_primary' => ! $design->images()->where('is_primary', true)->exists(),
            'sort_order' => (int) $design->images()->max('sort_order') + 1,
        ]);

        return response()->json([
            'message' => 'Design gallery image uploaded successfully.',
            'image_url' => $image->url,
        ]);
    }

    public function destroy(Product $product): RedirectResponse
    {
        DB::transaction(function () use ($product) {
            foreach ($product->designs as $design) {
                foreach ($design->images as $image) {
                    if ($image->image_path) {
                        File::delete(public_path('uploads/'.$image->image_path));
                    }
                }

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

            if (isset($designData['images']) && is_array($designData['images'])) {
                $this->syncDesignImages($design, $designData['images']);
            }

            $shadeData = $designData['shades'] ?? [];
            $shades = is_array($shadeData)
                ? array_filter(
                    $shadeData,
                    fn ($item) => is_array($item) && trim((string) ($item['name'] ?? '')) !== '',
                )
                : [];

            if ($shades !== []) {
                $design->sizes()->delete();
            } elseif (array_key_exists('sizes', $designData)) {
                $this->syncDesignSizes($design, (string) $designData['sizes']);
            }

            if (isset($designData['shades']) && is_array($designData['shades'])) {
                $this->syncShades($design, $designData['shades']);
            }
        }
    }

    protected function syncDesignImages(ProductDesign $design, array $images): void
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

                $design->images()->create([
                    'image_path' => $this->storePublicUpload($uploadedFile, 'products/design-gallery'),
                    'is_primary' => ! $design->images()->where('is_primary', true)->exists(),
                    'sort_order' => (int) $design->images()->max('sort_order') + 1,
                ]);
            }
        }
    }

    protected function syncDesignSizes(ProductDesign $design, string $input): void
    {
        $pairs = [];
        foreach (preg_split('/[,;|\\r\\n]+/', $input) ?: [] as $value) {
            $value = trim($value);
            if ($value === '') {
                continue;
            }

            if (preg_match('/^(\\d+)\\s*[xX]\\s*(\\d+)$/', $value, $matches) !== 1) {
                throw ValidationException::withMessages([
                    'sizes' => 'Enter each design size as width x height, for example 900x2050.',
                ]);
            }

            $width = (int) $matches[1];
            $height = (int) $matches[2];
            if ($width < 1 || $height < 1 || $width > 65535 || $height > 65535) {
                throw ValidationException::withMessages([
                    'sizes' => 'Design sizes must be between 1 and 65535 mm.',
                ]);
            }

            $pairs[$width.'x'.$height] = [$width, $height];
        }

        $design->sizes()->delete();
        foreach (array_values($pairs) as $index => [$width, $height]) {
            $design->sizes()->create([
                'width_mm' => $width,
                'height_mm' => $height,
                'sort_order' => $index,
            ]);
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
