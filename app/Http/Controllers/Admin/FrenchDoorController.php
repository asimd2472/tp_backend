<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductOption;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class FrenchDoorController extends Controller
{
    public function index(): View
    {
        return view('admin.products.french-door', [
            'designs' => ProductOption::query()
                ->where('product_slug', ProductOption::PRODUCT_SLUG)
                ->where('type', 'design')
                ->with(['children' => fn ($query) => $query->where('type', 'shade')->orderBy('name')])
                ->orderBy('name')
                ->get(),
            'sizes' => ProductOption::query()
                ->where('product_slug', ProductOption::PRODUCT_SLUG)
                ->where('type', 'size')
                ->orderBy('width_mm')
                ->orderBy('height_mm')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $type = $request->validate([
            'type' => ['required', Rule::in(['design', 'shade', 'size'])],
        ])['type'];

        $validated = $request->validate($this->rulesFor($type, updating: false));
        $parent = $type === 'shade' ? $this->findDesign($validated['parent_id']) : null;
        $this->ensureUniqueOption($type, $validated, $parent);

        $imagePath = null;

        try {
            DB::transaction(function () use ($type, $parent, $validated, $request, &$imagePath): void {
                $imagePath = $type === 'shade' ? $this->storeShadeImage($request) : null;
                $option = ProductOption::create([
                    'product_slug' => ProductOption::PRODUCT_SLUG,
                    'type' => $type,
                    'parent_id' => $parent?->id,
                    'name' => in_array($type, ['design', 'shade'], true) ? trim($validated['name']) : null,
                    'width_mm' => $type === 'size' ? $validated['width_mm'] : null,
                    'height_mm' => $type === 'size' ? $validated['height_mm'] : null,
                    'color_hex' => $type === 'shade' ? ($validated['color_hex'] ?? null) : null,
                    'image_path' => $imagePath,
                ]);

                if ($request->boolean('is_default')) {
                    $this->makeDefault($option);
                }
            });
        } catch (Throwable $exception) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $exception;
        }

        return redirect()->route('admin.products.french-door.index')->with('success', 'French-door option added.');
    }

    public function update(Request $request, ProductOption $option): RedirectResponse
    {
        $this->ensureFrenchDoorOption($option);

        $validated = $request->validate($this->rulesFor($option->type, updating: true));
        $parent = $option->type === 'shade' ? $this->findDesign($validated['parent_id']) : null;
        $this->ensureUniqueOption($option->type, $validated, $parent, $option);
        $previousImagePath = $option->image_path;
        $newImagePath = null;

        try {
            DB::transaction(function () use ($request, $option, $parent, $validated, &$newImagePath): void {
                if ($option->type === 'size') {
                    $option->width_mm = $validated['width_mm'];
                    $option->height_mm = $validated['height_mm'];
                } else {
                    $option->name = trim($validated['name']);

                    if ($option->type === 'shade') {
                        $option->parent_id = $parent->id;
                        $option->color_hex = $validated['color_hex'] ?? null;
                        $newImagePath = $this->storeShadeImage($request);
                        $option->image_path = $newImagePath ?? $option->image_path;
                    }
                }

                $option->save();

                if ($request->boolean('is_default')) {
                    $this->makeDefault($option);
                }
            });
        } catch (Throwable $exception) {
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $exception;
        }

        if ($newImagePath && $previousImagePath) {
            $this->deleteShadeImage($previousImagePath);
        }

        return redirect()->route('admin.products.french-door.index')->with('success', 'French-door option updated.');
    }

    public function destroy(ProductOption $option): RedirectResponse
    {
        $this->ensureFrenchDoorOption($option);
        $wasDefault = $option->is_default;
        $type = $option->type;
        $parentId = $option->parent_id;
        $imagePaths = $option->type === 'design'
            ? $option->children()->whereNotNull('image_path')->pluck('image_path')
            : collect([$option->image_path])->filter();

        DB::transaction(function () use ($option, $wasDefault, $type, $parentId): void {
            $option->delete();

            if (! $wasDefault) {
                return;
            }

            $replacement = ProductOption::query()
                ->where('product_slug', ProductOption::PRODUCT_SLUG)
                ->where('type', $type)
                ->when($type === 'shade', fn ($query) => $query->where('parent_id', $parentId))
                ->orderBy('id')
                ->first();

            if ($replacement) {
                if ($type === 'design') {
                    $this->makeDefault($replacement);
                } else {
                    $replacement->update(['is_default' => true]);
                }
            }
        });

        foreach ($imagePaths as $imagePath) {
            $this->deleteShadeImage($imagePath);
        }

        return redirect()->route('admin.products.french-door.index')->with('success', 'French-door option deleted.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rulesFor(string $type, bool $updating): array
    {
        return [
            'name' => in_array($type, ['design', 'shade'], true)
                ? ['required', 'string', 'max:120']
                : ['prohibited'],
            'parent_id' => $type === 'shade'
                ? ['required', 'integer']
                : ['prohibited'],
            'width_mm' => $type === 'size'
                ? ['required', 'integer', 'between:1,9999']
                : ['prohibited'],
            'height_mm' => $type === 'size'
                ? ['required', 'integer', 'between:1,9999']
                : ['prohibited'],
            'color_hex' => $type === 'shade'
                ? ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/']
                : ['prohibited'],
            'image' => $type === 'shade'
                ? [$updating ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']
                : ['prohibited'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    private function storeShadeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $path = $request->file('image')->store('french-door/shades', 'public');

        if (! $path) {
            throw new RuntimeException('The shade image could not be saved.');
        }

        return $path;
    }

    private function makeDefault(ProductOption $option): void
    {
        if ($option->type === 'design') {
            $shade = ProductOption::query()
                ->where('product_slug', ProductOption::PRODUCT_SLUG)
                ->where('type', 'shade')
                ->where('parent_id', $option->id)
                ->where('is_default', true)
                ->first();

            ProductOption::query()
                ->where('product_slug', ProductOption::PRODUCT_SLUG)
                ->where('type', 'design')
                ->update(['is_default' => false]);
            $option->update(['is_default' => true]);

            ProductOption::query()
                ->where('product_slug', ProductOption::PRODUCT_SLUG)
                ->where('type', 'shade')
                ->update(['is_default' => false]);

            $shade ??= ProductOption::query()
                ->where('product_slug', ProductOption::PRODUCT_SLUG)
                ->where('type', 'shade')
                ->where('parent_id', $option->id)
                ->orderBy('id')
                ->first();

            $shade?->update(['is_default' => true]);

            return;
        }

        if ($option->type === 'shade') {
            ProductOption::query()
                ->where('product_slug', ProductOption::PRODUCT_SLUG)
                ->where('type', 'design')
                ->update(['is_default' => false]);
            $option->parent()->update(['is_default' => true]);

            ProductOption::query()
                ->where('product_slug', ProductOption::PRODUCT_SLUG)
                ->where('type', 'shade')
                ->update(['is_default' => false]);
            $option->update(['is_default' => true]);

            return;
        }

        ProductOption::query()
            ->where('product_slug', ProductOption::PRODUCT_SLUG)
            ->where('type', 'size')
            ->update(['is_default' => false]);
        $option->update(['is_default' => true]);
    }

    private function deleteShadeImage(string $path): void
    {
        if (! Storage::disk('public')->delete($path)) {
            Log::warning('French-door shade image could not be deleted.', ['path' => $path]);
        }
    }

    private function findDesign(int|string $id): ProductOption
    {
        $design = ProductOption::query()
            ->where('product_slug', ProductOption::PRODUCT_SLUG)
            ->where('type', 'design')
            ->find($id);

        if (! $design) {
            throw ValidationException::withMessages([
                'parent_id' => 'Choose a valid French-door design for this shade.',
            ]);
        }

        return $design;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function ensureUniqueOption(string $type, array $validated, ?ProductOption $parent, ?ProductOption $except = null): void
    {
        $duplicate = ProductOption::query()
            ->where('product_slug', ProductOption::PRODUCT_SLUG)
            ->where('type', $type);

        if ($except) {
            $duplicate->whereKeyNot($except->id);
        }

        if ($type === 'size') {
            $duplicate->where('width_mm', $validated['width_mm'])
                ->where('height_mm', $validated['height_mm']);
        } else {
            $duplicate->where('name', trim($validated['name']));

            if ($type === 'shade') {
                $duplicate->where('parent_id', $parent->id);
            } else {
                $duplicate->whereNull('parent_id');
            }
        }

        if ($duplicate->exists()) {
            throw ValidationException::withMessages([
                $type === 'size' ? 'width_mm' : 'name' => $type === 'size'
                    ? 'This size is already available for French-door.'
                    : 'This option already exists.',
            ]);
        }
    }

    private function ensureFrenchDoorOption(ProductOption $option): void
    {
        abort_unless($option->product_slug === ProductOption::PRODUCT_SLUG, 404);
    }
}
