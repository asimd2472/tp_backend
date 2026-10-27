<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WindowModel;
use App\Models\WindowProduct;
use App\Models\WindowShade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class WindowController extends Controller
{
    public function index(): View
    {
        $windows = WindowProduct::query()
            ->withCount(['models'])
            ->with(['models.shades'])
            ->latest()
            ->paginate(15);

        return view('admin.windows.index', compact('windows'));
    }

    public function create(): View
    {
        return view('admin.windows.form', [
            'window' => new WindowProduct,
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $uploads = [];

        try {
            $window = DB::transaction(function () use ($request, $validated, &$uploads): WindowProduct {
                $window = WindowProduct::create($this->productData($validated));
                if ($request->file('main_image')) {
                    $window->update(['main_image' => $this->storePublicUpload($request->file('main_image'), 'windows')]);
                    $uploads[] = $window->main_image;
                }

                $replacedUploads = [];
                $this->syncModels($window, $request->all()['models'] ?? [], $uploads, $replacedUploads);

                return $window;
            });
        } catch (Throwable $exception) {
            $this->deleteUploads($uploads);
            throw $exception;
        }

        return redirect()->route('admin.windows.edit', $window)->with('success', 'Window product created successfully.');
    }

    public function edit(WindowProduct $window): View
    {
        $window->load([
            'models.shades.images' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'models.shades.sizes' => fn ($query) => $query->orderBy('width_mm')->orderBy('height_mm'),
        ]);

        return view('admin.windows.form', [
            'window' => $window,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, WindowProduct $window): RedirectResponse
    {
        $validated = $request->validate($this->rules($window));
        $uploads = [];
        $replacedUploads = [];

        try {
            DB::transaction(function () use ($request, $window, $validated, &$uploads, &$replacedUploads): void {
                $window->update($this->productData($validated, $window));

                if ($request->file('main_image')) {
                    if ($window->main_image) {
                        $replacedUploads[] = $window->main_image;
                    }
                    $path = $this->storePublicUpload($request->file('main_image'), 'windows');
                    $uploads[] = $path;
                    $window->update(['main_image' => $path]);
                }

                $this->syncModels($window, $request->all()['models'] ?? [], $uploads, $replacedUploads);
            });
        } catch (Throwable $exception) {
            $this->deleteUploads($uploads);
            throw $exception;
        }

        $this->deleteUploads($replacedUploads);

        return redirect()->route('admin.windows.edit', $window)->with('success', 'Window product updated successfully.');
    }

    public function destroy(WindowProduct $window): RedirectResponse
    {
        $uploads = [$window->main_image];
        foreach ($window->models as $model) {
            $uploads[] = $model->main_image;
            foreach ($model->shades as $shade) {
                $uploads[] = $shade->swatch_image;
                foreach ($shade->images as $image) {
                    $uploads[] = $image->image_path;
                }
            }
        }

        DB::transaction(fn () => $window->delete());
        $this->deleteUploads($uploads);

        return redirect()->route('admin.windows.index')->with('success', 'Window product deleted successfully.');
    }

    public function uploadModelImage(Request $request, WindowProduct $window, WindowModel $model): JsonResponse
    {
        abort_unless((int) $model->window_product_id === (int) $window->id, 404);

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ]);

        $oldImage = $model->main_image;
        $path = $this->storePublicUpload($validated['image'], 'windows/models');
        $model->update(['main_image' => $path]);
        $this->deleteUploads([$oldImage]);

        return response()->json([
            'message' => 'Model image uploaded successfully.',
            'image_url' => $model->main_image_url,
        ]);
    }

    public function uploadShadeImage(
        Request $request,
        WindowProduct $window,
        WindowModel $model,
        WindowShade $shade,
        string $kind,
    ): JsonResponse {
        abort_unless(
            (int) $model->window_product_id === (int) $window->id
                && (int) $shade->window_model_id === (int) $model->id,
            404,
        );
        abort_unless(in_array($kind, ['swatch', 'gallery'], true), 404);

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ]);

        if ($kind === 'swatch') {
            $oldImage = $shade->swatch_image;
            $path = $this->storePublicUpload($validated['image'], 'windows/shades');
            $shade->update(['swatch_image' => $path]);
            $this->deleteUploads([$oldImage]);

            return response()->json([
                'message' => 'Swatch image uploaded successfully.',
                'image_url' => $shade->swatch_url,
            ]);
        }

        $path = $this->storePublicUpload($validated['image'], 'windows/gallery');
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

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(?WindowProduct $window = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'alpha_dash', Rule::unique('window_products', 'slug')->ignore($window?->id)],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'starting_price' => ['nullable', 'numeric', 'min:0'],
            'is_premium' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'rating' => ['nullable', 'numeric', 'between:0,5'],
            'review' => ['nullable', 'string', 'max:10000'],
            'faqs' => ['nullable', 'array', 'max:100'],
            'faqs.*.question' => ['nullable', 'string', 'max:1000'],
            'faqs.*.answer' => ['nullable', 'string', 'max:10000'],
            'specifications' => ['nullable', 'array', 'max:100'],
            'specifications.*.question' => ['nullable', 'string', 'max:1000'],
            'specifications.*.answer' => ['nullable', 'string', 'max:10000'],
            'main_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'models' => ['nullable', 'array'],
            'models.*.id' => ['nullable', 'integer'],
            'models.*.name' => ['nullable', 'string', 'max:255'],
            'models.*.is_default' => ['nullable', 'boolean'],
            'models.*.is_active' => ['nullable', 'boolean'],
            'models.*.is_premium' => ['nullable', 'boolean'],
            'models.*.starting_price' => ['nullable', 'numeric', 'min:0'],
            'models.*.short_description' => ['nullable', 'string', 'max:500'],
            'models.*.description' => ['nullable', 'string'],
            'models.*.main_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'models.*.shades' => ['nullable', 'array'],
            'models.*.shades.*.id' => ['nullable', 'integer'],
            'models.*.shades.*.name' => ['nullable', 'string', 'max:255'],
            'models.*.shades.*.color_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'models.*.shades.*.swatch' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'models.*.shades.*.images' => ['nullable', 'array'],
            'models.*.shades.*.images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'models.*.shades.*.sizes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    private function productData(array $validated, ?WindowProduct $window = null): array
    {
        return [
            'name' => $validated['name'],
            'category' => $validated['category'] ?? null,
            'model' => $validated['model'] ?? null,
            'slug' => $this->resolveSlug($validated['slug'] ?? null, $validated['name'], $window),
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'starting_price' => (float) ($validated['starting_price'] ?? 0),
            'is_premium' => (bool) ($validated['is_premium'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'faqs' => $this->normalizeQuestionAnswers($validated['faqs'] ?? []),
            'specifications' => $this->normalizeQuestionAnswers($validated['specifications'] ?? []),
            'rating' => $validated['rating'] ?? null,
            'review' => $validated['review'] ?? null,
        ];
    }

    private function syncModels(WindowProduct $window, array $models, array &$uploads, array &$replacedUploads = []): void
    {
        foreach ($models as $index => $modelData) {
            if (! is_array($modelData) || trim((string) ($modelData['name'] ?? '')) === '') {
                continue;
            }

            $model = isset($modelData['id'])
                ? $window->models()->findOrFail($modelData['id'])
                : new WindowModel;
            $model->fill([
                'name' => trim($modelData['name']),
                'slug' => $this->resolveModelSlug($modelData['slug'] ?? null, trim($modelData['name']), $model),
                'short_description' => $modelData['short_description'] ?? null,
                'description' => $modelData['description'] ?? null,
                'starting_price' => (float) ($modelData['starting_price'] ?? 0),
                'is_premium' => (bool) ($modelData['is_premium'] ?? false),
                'is_active' => (bool) ($modelData['is_active'] ?? true),
                'is_default' => (bool) ($modelData['is_default'] ?? false),
            ]);

            if (($modelData['main_image'] ?? null) instanceof UploadedFile) {
                if ($model->main_image) {
                    $replacedUploads[] = $model->main_image;
                }
                $model->main_image = $this->storePublicUpload($modelData['main_image'], 'windows/models');
                $uploads[] = $model->main_image;
            }

            $window->models()->save($model);
            if ($model->is_default) {
                $window->models()->whereKeyNot($model->getKey())->update(['is_default' => false]);
            }

            foreach (($modelData['shades'] ?? []) as $shadeIndex => $shadeData) {
                if (! is_array($shadeData) || trim((string) ($shadeData['name'] ?? '')) === '') {
                    continue;
                }

                $shade = isset($shadeData['id'])
                    ? $model->shades()->findOrFail($shadeData['id'])
                    : new WindowShade;
                $shade->fill([
                    'name' => trim($shadeData['name']),
                    'color_hex' => $shadeData['color_hex'] ?? null,
                    'sort_order' => $shadeIndex,
                ]);

                if (($shadeData['swatch'] ?? null) instanceof UploadedFile) {
                    if ($shade->swatch_image) {
                        $replacedUploads[] = $shade->swatch_image;
                    }
                    $shade->swatch_image = $this->storePublicUpload($shadeData['swatch'], 'windows/shades');
                    $uploads[] = $shade->swatch_image;
                }
                $model->shades()->save($shade);

                foreach (($shadeData['images'] ?? []) as $image) {
                    if (! $image instanceof UploadedFile) {
                        continue;
                    }

                    $path = $this->storePublicUpload($image, 'windows/gallery');
                    $uploads[] = $path;
                    $shade->images()->create([
                        'image_path' => $path,
                        'is_primary' => ! $shade->images()->where('is_primary', true)->exists(),
                        'sort_order' => (int) $shade->images()->max('sort_order') + 1,
                    ]);
                }

                $this->syncSizes($shade, (string) ($shadeData['sizes'] ?? ''));
            }
        }
    }

    private function syncSizes(WindowShade $shade, string $input): void
    {
        $pairs = [];
        foreach (preg_split('/[,;|\\r\\n]+/', $input) ?: [] as $value) {
            $value = trim($value);
            if ($value === '') {
                continue;
            }

            if (preg_match('/^(\\d+)\\s*[xX]\\s*(\\d+)$/', $value, $matches) !== 1) {
                throw ValidationException::withMessages(['sizes' => 'Enter each window size as width x height, for example 914x1219.']);
            }

            $width = (int) $matches[1];
            $height = (int) $matches[2];
            if ($width < 1 || $height < 1 || $width > 65535 || $height > 65535) {
                throw ValidationException::withMessages(['sizes' => 'Window sizes must be between 1 and 65535 mm.']);
            }
            $pairs[$width.'x'.$height] = [$width, $height];
        }

        $shade->sizes()->delete();
        foreach (array_values($pairs) as $index => [$width, $height]) {
            $shade->sizes()->create([
                'width_mm' => $width,
                'height_mm' => $height,
                'sort_order' => $index,
            ]);
        }
    }

    private function normalizeQuestionAnswers(array $items): array
    {
        return collect($items)
            ->filter(fn ($item) => is_array($item) && trim((string) ($item['question'] ?? '')) !== '' && trim((string) ($item['answer'] ?? '')) !== '')
            ->map(fn ($item) => [
                'question' => trim($item['question']),
                'answer' => trim($item['answer']),
            ])
            ->values()
            ->all();
    }

    private function resolveSlug(?string $slug, string $name, ?WindowProduct $window = null): string
    {
        return $this->uniqueSlug($slug ?: $name, WindowProduct::class, $window);
    }

    private function resolveModelSlug(?string $slug, string $name, ?WindowModel $model = null): string
    {
        return $this->uniqueSlug($slug ?: $name, WindowModel::class, $model);
    }

    private function uniqueSlug(string $value, string $modelClass, WindowProduct|WindowModel|null $current = null): string
    {
        $base = Str::slug($value) ?: 'window';
        $slug = $base;
        $suffix = 2;
        while ($modelClass::query()->where('slug', $slug)->when($current, fn ($query) => $query->whereKeyNot($current->getKey()))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function storePublicUpload(UploadedFile $file, string $directory): string
    {
        $relativeDirectory = trim($directory, '/\\');
        $destination = public_path('uploads/'.$relativeDirectory);
        File::ensureDirectoryExists($destination);

        $filename = Str::uuid().'.'.$file->extension();
        $file->move($destination, $filename);

        return $relativeDirectory.'/'.$filename;
    }

    private function deleteUploads(array $uploads): void
    {
        foreach (array_filter(array_unique($uploads)) as $path) {
            if (! str_starts_with($path, 'http')) {
                File::delete(public_path('uploads/'.ltrim($path, '/')));
            }
        }
    }
}
