<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $perPage = (int) ($validated['per_page'] ?? 10);

        $posts = BlogPost::query()
            ->where('status', 'published')
            ->where(function ($query): void {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $posts->getCollection()->map(fn (BlogPost $post): array => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'category' => $post->category,
                'tags' => $post->tags,
                'excerpt' => $post->excerpt,
                // 'content' => $post->content,
                'content' => Str::limit(
                    trim(preg_replace(
                        '/\s+/u',
                        ' ',
                        html_entity_decode(
                            strip_tags($post->content ?? ''),
                            ENT_QUOTES | ENT_HTML5,
                            'UTF-8'
                        )
                    ) ?? ''),
                    100,
                    '...'
                ),
                'featured_image_url' => $post->featured_image_url,
                'meta_title' => $post->meta_title,
                'meta_description' => $post->meta_description,
                'published_at' => $post->published_at?->toISOString(),
            ])->values(),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
                'from' => $posts->firstItem(),
                'to' => $posts->lastItem(),
                'next_page_url' => $posts->nextPageUrl(),
                'prev_page_url' => $posts->previousPageUrl(),
            ],
        ]);
    }

    public function details(string $slug): JsonResponse
    {
        $post = BlogPost::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->where(function ($query): void {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->first();

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Blog post not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'category' => $post->category,
                'tags' => $post->tags,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'featured_image_url' => $post->featured_image_url,
                'meta_title' => $post->meta_title,
                'meta_description' => $post->meta_description,
                'published_at' => $post->published_at?->toISOString(),
            ],
        ]);
    }
}


