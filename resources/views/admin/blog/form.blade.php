@extends('admin.layout')

@section('title', $isEdit ? 'Edit Blog Post' : 'Write a Blog Post')

@section('content')
    <section class="page-heading">
        <div>
            <div class="page-kicker"><span class="live-dot"></span> BLOG EDITOR</div>
            <h1>{{ $isEdit ? 'Edit blog post' : 'Write a blog post' }}<span>.</span></h1>
            <p>Write and format your article, add media, and save it as a draft or publish it.</p>
        </div>
    </section>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm role-alert">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm role-alert" role="alert">
            <strong>The blog post could not be saved. Please correct these issues:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="blog-post-form" action="{{ $isEdit ? route('admin.blog.update', $post) : route('admin.blog.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif
        <input type="hidden" name="status" id="blog-status-value" value="{{ old('status', $post->status) }}">

        <div class="row g-4">
            <div class="col-xl-9">
                <div class="panel-card p-4 mb-4">
                    <label class="form-label fw-semibold" for="blog-title">Title</label>
                    <input id="blog-title" type="text" name="title" class="form-control form-control-lg" value="{{ old('title', $post->title) }}" placeholder="Add a title" required maxlength="255">

                    <div class="row g-3 mt-2">
                        <div class="col-lg-7">
                            <label class="form-label" for="blog-slug">URL slug</label>
                            <input id="blog-slug" type="text" name="slug" class="form-control" value="{{ old('slug', $post->slug) }}" placeholder="generated-from-title">
                        </div>
                        <div class="col-lg-5">
                            <label class="form-label" for="blog-category">Category</label>
                            <input id="blog-category" type="text" name="category" class="form-control" value="{{ old('category', $post->category) }}" placeholder="News, guides, updates">
                        </div>
                    </div>
                </div>

                <div class="panel-card p-3 p-lg-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <div>
                            <h2 class="h5 mb-1">Article content</h2>
                            <p class="small text-muted mb-0">Format text, add links, images, tables, video, and custom HTML.</p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <input type="file" id="blog-media-file" class="d-none" accept="image/jpeg,image/png,image/webp,image/gif">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="blog-add-media">Add Media</button>
                            <span class="small text-muted" id="blog-media-status" aria-live="polite"></span>
                        </div>
                    </div>
                    <textarea id="blog-content" name="content">{{ old('content', $post->content) }}</textarea>
                </div>

                <div class="panel-card p-4">
                    <h2 class="h5 mb-3">Excerpt</h2>
                    <label class="form-label" for="blog-excerpt">Short summary</label>
                    <textarea id="blog-excerpt" name="excerpt" class="form-control" rows="3" maxlength="1000" placeholder="A short summary for listings and previews">{{ old('excerpt', $post->excerpt) }}</textarea>
                </div>
            </div>

            <div class="col-xl-3">
                <div class="panel-card p-4 mb-4">
                    <h2 class="h5 mb-3">Publish</h2>
                    <p class="small text-muted mb-2">Current status:
                        <strong id="blog-status-label">{{ ucfirst(old('status', $post->status)) }}</strong>
                    </p>
                    <label class="form-label" for="published_at">Publish date</label>
                    <input id="published_at" type="datetime-local" name="published_at" class="form-control mb-3" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}">
                    <button type="button" class="btn btn-outline-secondary w-100 mb-2" data-blog-save="draft">Save draft</button>
                    <button type="button" class="btn btn-primary w-100 btn-cta" data-blog-save="published">{{ $post->status === 'published' ? 'Update post' : 'Publish' }}</button>
                    <a href="{{ route('admin.blog.index') }}" class="btn btn-link w-100 mt-2">Back to posts</a>
                </div>

                <div class="panel-card p-4 mb-4">
                    <h2 class="h5 mb-3">Featured image</h2>
                    <div class="image-upload-field">
                        @if ($post->featured_image_url)
                            <div class="existing-image-previews mb-2">
                                <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" class="img-fluid rounded">
                            </div>
                        @endif
                        <div class="image-upload-previews d-flex flex-wrap gap-2 mb-2"></div>
                        <input type="file" name="featured_image" class="form-control image-preview-input" accept="image/jpeg,image/png,image/webp">
                    </div>
                </div>

                <div class="panel-card p-4 mb-4">
                    <h2 class="h5 mb-3">Tags</h2>
                    <label class="form-label" for="blog-tags">Comma-separated tags</label>
                    <input id="blog-tags" type="text" name="tags" class="form-control" value="{{ old('tags', $post->tags) }}" placeholder="design, home, tips">
                </div>

                <details class="panel-card p-4">
                    <summary class="h6 mb-0">Search engine settings</summary>
                    <div class="mt-3">
                        <label class="form-label" for="meta-title">Meta title</label>
                        <input id="meta-title" type="text" name="meta_title" class="form-control mb-3" value="{{ old('meta_title', $post->meta_title) }}" maxlength="255">
                        <label class="form-label" for="meta-description">Meta description</label>
                        <textarea id="meta-description" name="meta_description" class="form-control" rows="4" maxlength="1000">{{ old('meta_description', $post->meta_description) }}</textarea>
                    </div>
                </details>
            </div>
        </div>
    </form>

    <script src="https://cdn.jsdelivr.net/npm/tinymce@7.6.1/tinymce.min.js"></script>
    <script>
        (function () {
            const uploadUrl = @json(route('admin.blog.media.store'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const status = document.getElementById('blog-media-status');
            const mediaInput = document.getElementById('blog-media-file');
            const form = document.getElementById('blog-post-form');
            const statusInput = document.getElementById('blog-status-value');
            const statusLabel = document.getElementById('blog-status-label');

            async function uploadImage(file) {
                const body = new FormData();
                body.append('image', file);
                const response = await fetch(uploadUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body,
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const validationMessage = Object.values(result.errors || {}).flat()[0];
                    throw new Error(validationMessage || result.message || 'Image upload failed.');
                }

                return result.location;
            }

            tinymce.init({
                selector: '#blog-content',
                license_key: 'gpl',
                height: 560,
                menubar: 'file edit view insert format tools table',
                plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table wordcount',
                toolbar: 'blocks | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | blockquote | link image media table | undo redo | removeformat | code fullscreen preview',
                toolbar_mode: 'sliding',
                content_style: 'body { font-family:Arial,sans-serif; font-size:16px; line-height:1.6; } img { max-width:100%; height:auto; }',
                automatic_uploads: true,
                file_picker_types: 'image',
                images_upload_handler: function (blobInfo, progress) {
                    return uploadImage(blobInfo.blob()).then(function (location) {
                        progress(100);
                        return location;
                    });
                },
                file_picker_callback: function (callback, value, meta) {
                    if (meta.filetype !== 'image') {
                        return;
                    }

                    mediaInput.onchange = async function () {
                        const file = mediaInput.files[0];
                        if (!file) {
                            return;
                        }

                        try {
                            status.textContent = 'Uploading image…';
                            callback(await uploadImage(file), { alt: file.name });
                            status.textContent = 'Image uploaded.';
                        } catch (error) {
                            status.textContent = error.message;
                        } finally {
                            mediaInput.value = '';
                        }
                    };
                    mediaInput.click();
                },
                setup: function (editor) {
                    document.getElementById('blog-add-media').addEventListener('click', function () {
                        mediaInput.onchange = async function () {
                            const file = mediaInput.files[0];
                            if (!file) {
                                return;
                            }

                            try {
                                status.textContent = 'Uploading image…';
                                const location = await uploadImage(file);
                                editor.insertContent('<p><img src="' + editor.dom.encode(location) + '" alt="' + editor.dom.encode(file.name) + '"></p>');
                                status.textContent = 'Image uploaded and inserted.';
                            } catch (error) {
                                status.textContent = error.message;
                            } finally {
                                mediaInput.value = '';
                            }
                        };
                        mediaInput.click();
                    });

                    editor.on('WordCountUpdate', function (event) {
                        const wordCount = event.wordCount.words;
                        editor.getContainer().setAttribute('data-word-count', wordCount);
                    });
                },
            });

            document.querySelectorAll('[data-blog-save]').forEach(function (button) {
                button.addEventListener('click', function () {
                    statusInput.value = button.dataset.blogSave;
                    statusLabel.textContent = button.dataset.blogSave === 'published' ? 'Published' : 'Draft';
                    if (window.tinymce) {
                        tinymce.triggerSave();
                    }
                    form.requestSubmit();
                });
            });

            document.addEventListener('change', function (event) {
                const input = event.target;
                if (!input.matches('input[type="file"].image-preview-input')) {
                    return;
                }

                const field = input.closest('.image-upload-field');
                const previewList = field?.querySelector('.image-upload-previews');
                if (!previewList) {
                    return;
                }

                previewList.replaceChildren();
                Array.from(input.files || []).forEach(function (file) {
                    if (!file.type.startsWith('image/')) {
                        return;
                    }
                    const preview = document.createElement('img');
                    preview.src = URL.createObjectURL(file);
                    preview.alt = file.name;
                    preview.className = 'img-fluid rounded';
                    preview.style.maxHeight = '180px';
                    previewList.appendChild(preview);
                });
            });
        })();
    </script>
@endsection
