<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $posts = BlogPost::query()
            ->with('author')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->query('search'));
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%');
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.blog.index', compact('posts'));
    }

    public function create(): View
    {
        return view('admin.blog.form', [
            'post' => new BlogPost(['status' => 'draft']),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $featuredImage = $request->file('featured_image')
            ? $this->storeImage($request->file('featured_image'), 'blogs/featured')
            : null;

        try {
            $post = BlogPost::create($this->postData($validated, $request, $featuredImage));
        } catch (\Throwable $exception) {
            $this->deleteImage($featuredImage);
            throw $exception;
        }

        return redirect()->route('admin.blog.edit', $post)->with('success', 'Blog post saved successfully.');
    }

    public function edit(BlogPost $blog): View
    {
        return view('admin.blog.form', [
            'post' => $blog,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, BlogPost $blog): RedirectResponse
    {
        $validated = $request->validate($this->rules($blog));
        $newImage = $request->file('featured_image')
            ? $this->storeImage($request->file('featured_image'), 'blogs/featured')
            : null;
        $previousImage = $blog->featured_image;

        try {
            $blog->update($this->postData($validated, $request, $newImage ?? $previousImage, $blog));
        } catch (\Throwable $exception) {
            $this->deleteImage($newImage);
            throw $exception;
        }

        if ($newImage) {
            $this->deleteImage($previousImage);
        }

        return redirect()->route('admin.blog.edit', $blog)->with('success', 'Blog post updated successfully.');
    }

    public function destroy(BlogPost $blog): RedirectResponse
    {
        $featuredImage = $blog->featured_image;
        $blog->delete();
        $this->deleteImage($featuredImage);

        return redirect()->route('admin.blog.index')->with('success', 'Blog post deleted successfully.');
    }

    public function uploadMedia(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:20480'],
        ]);

        $path = $this->storeImage($validated['image'], 'blogs/content');

        return response()->json([
            'location' => asset('uploads/'.$path),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(?BlogPost $post = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:180', 'alpha_dash', Rule::unique('blog_posts', 'slug')->ignore($post?->id)],
            'category' => ['nullable', 'string', 'max:120'],
            'tags' => ['nullable', 'string', 'max:1000'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ];
    }

    private function postData(array $validated, Request $request, ?string $featuredImage, ?BlogPost $post = null): array
    {
        $status = $validated['status'];
        $publishedAt = $status === 'published'
            ? ($validated['published_at'] ?? $post?->published_at ?? now())
            : null;

        return [
            'author_id' => $post?->author_id ?? $request->user()->id,
            'title' => trim($validated['title']),
            'slug' => $this->resolveSlug($validated['slug'] ?? null, $validated['title'], $post),
            'category' => $validated['category'] ?? null,
            'tags' => $validated['tags'] ?? null,
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'] ?? null,
            'featured_image' => $featuredImage,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'status' => $status,
            'published_at' => $publishedAt,
        ];
    }

    private function resolveSlug(?string $slug, string $title, ?BlogPost $post = null): string
    {
        $base = Str::slug($slug ?: $title) ?: 'blog-post';
        $candidate = $base;
        $suffix = 2;

        while (BlogPost::query()
            ->where('slug', $candidate)
            ->when($post, fn ($query) => $query->whereKeyNot($post->id))
            ->exists()) {
            $candidate = $base.'-'.$suffix++;
        }

        return $candidate;
    }

    private function storeImage(UploadedFile $file, string $directory): string
    {
        $relativeDirectory = trim($directory, '/\\');
        $destination = public_path('uploads/'.$relativeDirectory);
        File::ensureDirectoryExists($destination);

        $filename = Str::uuid().'.'.$file->extension();
        $file->move($destination, $filename);

        return $relativeDirectory.'/'.$filename;
    }

    private function deleteImage(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http')) {
            File::delete(public_path('uploads/'.ltrim($path, '/')));
        }
    }
}
