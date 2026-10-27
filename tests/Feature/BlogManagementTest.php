<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BlogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_blog_api_returns_published_posts_with_pagination(): void
    {
        BlogPost::create([
            'title' => 'Older published post',
            'slug' => 'older-published-post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'featured_image' => 'blogs/featured/older.jpg',
        ]);
        BlogPost::create([
            'title' => 'Newest published post',
            'slug' => 'newest-published-post',
            'status' => 'published',
            'published_at' => now(),
        ]);
        BlogPost::create([
            'title' => 'Draft post',
            'slug' => 'draft-post',
            'status' => 'draft',
            'published_at' => null,
        ]);
        BlogPost::create([
            'title' => 'Scheduled post',
            'slug' => 'scheduled-post',
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        $this->getJson(route('api.blogs.index', ['per_page' => 1]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.slug', 'newest-published-post')
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.per_page', 1)
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('pagination.last_page', 2)
            ->assertJsonPath('pagination.from', 1)
            ->assertJsonPath('pagination.to', 1)
            ->assertJsonStructure([
                'success',
                'data' => [['id', 'title', 'slug', 'featured_image_url', 'published_at']],
                'pagination' => ['current_page', 'per_page', 'total', 'last_page', 'from', 'to', 'next_page_url', 'prev_page_url'],
            ]);

        $this->getJson(route('api.blogs.index', ['per_page' => 1, 'page' => 2]))
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'older-published-post')
            ->assertJsonPath('pagination.prev_page_url', route('api.blogs.index', ['per_page' => 1, 'page' => 1]));
    }

    public function test_public_blog_api_validates_pagination_parameters(): void
    {
        $this->getJson(route('api.blogs.index', ['per_page' => 101]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_admin_can_create_update_and_delete_a_rich_blog_post(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.blog.create'))
            ->assertOk()
            ->assertSee('tinymce')
            ->assertSee('Add Media')
            ->assertSee('Save draft');

        $this->post(route('admin.blog.store'), [
            'title' => 'A Story About Windows',
            'slug' => '',
            'category' => 'Guides',
            'tags' => 'windows, design',
            'excerpt' => 'A short introduction.',
            'content' => '<h2>Beautiful homes</h2><p>Formatted <strong>article</strong>.</p>',
            'status' => 'published',
        ])->assertRedirect(route('admin.blog.edit', BlogPost::query()->firstOrFail()));

        $post = BlogPost::query()->firstOrFail();
        $this->assertSame('a-story-about-windows', $post->slug);
        $this->assertSame('published', $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertSame('<h2>Beautiful homes</h2><p>Formatted <strong>article</strong>.</p>', $post->content);
        $this->assertSame(auth()->id(), $post->author_id);

        $this->put(route('admin.blog.update', $post), [
            'title' => 'A Story About Windows - Updated',
            'category' => 'Updates',
            'content' => '<p>Updated draft content.</p>',
            'status' => 'draft',
        ])->assertRedirect(route('admin.blog.edit', $post));

        $this->assertDatabaseHas('blog_posts', [
            'id' => $post->id,
            'title' => 'A Story About Windows - Updated',
            'slug' => 'a-story-about-windows-updated',
            'status' => 'draft',
            'published_at' => null,
        ]);

        $this->delete(route('admin.blog.destroy', $post))
            ->assertRedirect(route('admin.blog.index'));
        $this->assertDatabaseMissing('blog_posts', ['id' => $post->id]);
    }

    public function test_blog_media_upload_returns_an_image_location_for_the_editor(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $response = $this->postJson(route('admin.blog.media.store'), [
            'image' => UploadedFile::fake()->image('editor-image.jpg'),
        ])->assertOk()->assertJsonStructure(['location']);

        $path = public_path(ltrim((string) parse_url($response->json('location'), PHP_URL_PATH), '/'));
        try {
            $this->assertFileExists($path);
            $this->assertStringContainsString('uploads/blogs/content/', $response->json('location'));
        } finally {
            File::delete($path);
        }
    }

    public function test_only_admins_can_open_blog_management(): void
    {
        $this->get(route('admin.blog.index'))->assertRedirect(route('admin.login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.blog.index'))
            ->assertForbidden();
    }
}
