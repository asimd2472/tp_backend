@extends('admin.layout')

@section('title', 'Blog')

@section('content')
    <section class="page-heading">
        <div>
            <div class="page-kicker"><span class="live-dot"></span> CONTENT MANAGEMENT</div>
            <h1>Blog posts<span>.</span></h1>
            <p>Create, edit, and publish articles with the rich text editor.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.blog.create') }}" class="btn btn-primary">Write a post</a>
        </div>
    </section>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm role-alert">{{ session('success') }}</div>
    @endif

    <div class="panel-card p-4 mb-4">
        <form action="{{ route('admin.blog.index') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label for="blog-search" class="form-label">Search posts</label>
                <input id="blog-search" type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Title or slug">
            </div>
            <div class="col-md-3">
                <label for="blog-status" class="form-label">Status</label>
                <select id="blog-status" name="status" class="form-select">
                    <option value="">All posts</option>
                    <option value="published" @selected(request('status') === 'published')>Published</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary">Filter</button>
                <a href="{{ route('admin.blog.index') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>

    <div class="panel-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Post</th>
                        <th>Category</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Last updated</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($posts as $post)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    @if ($post->featured_image_url)
                                        <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" class="rounded" style="width:52px;height:52px;object-fit:cover;">
                                    @else
                                        <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:52px;height:52px;">B</div>
                                    @endif
                                    <div>
                                        <div class="fw-semibold">{{ $post->title }}</div>
                                        <small class="text-muted">{{ $post->slug }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $post->category ?: '—' }}</td>
                            <td>{{ $post->author?->name ?? '—' }}</td>
                            <td>
                                @if ($post->status === 'published')
                                    <span class="badge bg-success-subtle text-success">Published</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Draft</span>
                                @endif
                            </td>
                            <td>{{ $post->updated_at->format('d M Y') }}</td>
                            <td>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.blog.edit', $post) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('admin.blog.destroy', $post) }}" method="POST" onsubmit="return confirm('Delete this blog post?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">No blog posts yet. Create your first post to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $posts->links() }}</div>
    </div>
@endsection
