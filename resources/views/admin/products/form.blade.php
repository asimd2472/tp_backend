@extends('admin.layout')

@section('title', $isEdit ? 'Edit Product' : 'Add Product')

@section('content')
    <section class="page-heading">
        <div>
            <div class="page-kicker"><span class="live-dot"></span> PRODUCT MANAGEMENT</div>
            <h1>{{ $isEdit ? 'Edit product' : 'Add product' }}<span>.</span></h1>
            <p>Manage the product’s identity, designs, shades, gallery, and available sizes.</p>
        </div>
    </section>

    <div class="panel-card p-4">
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Product was not saved. Please correct these issues:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ $isEdit ? route('admin.products.update', $product) : route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="mb-3">
                        <label class="form-label" for="name">Product name</label>
                        <input id="name" name="name" type="text" class="form-control" value="{{ old('name', $product->name ?? '') }}" required>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="category">Category</label>
                            <input id="category" name="category" type="text" class="form-control" value="{{ old('category', $product->category ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="model">Model / Series</label>
                            <input id="model" name="model" type="text" class="form-control" value="{{ old('model', $product->model ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="slug">Slug</label>
                            <input id="slug" name="slug" type="text" class="form-control" value="{{ old('slug', $product->slug ?? '') }}">
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label" for="starting_price">Starting price</label>
                            <input id="starting_price" name="starting_price" type="number" min="0" step="0.01" class="form-control" value="{{ old('starting_price', $product->starting_price ?? 0) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="is_premium">Premium badge</label>
                            <div class="form-check form-switch mt-2">
                                <input id="is_premium" name="is_premium" class="form-check-input" type="checkbox" value="1" {{ old('is_premium', $product->is_premium ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_premium">Enable premium</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="is_active">Status</label>
                            <div class="form-check form-switch mt-2">
                                <input id="is_active" name="is_active" class="form-check-input" type="checkbox" value="1" {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label" for="short_description">Short description</label>
                        <textarea id="short_description" name="short_description" class="form-control" rows="3">{{ old('short_description', $product->short_description ?? '') }}</textarea>
                    </div>

                    <div class="mt-3">
                        <label class="form-label" for="description">Full description</label>
                        <textarea id="description" name="description" class="form-control" rows="5">{{ old('description', $product->description ?? '') }}</textarea>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">Main product image</label>
                        <div class="image-upload-field">
                            @if ($product->main_image)
                                <div class="existing-image-previews mb-2">
                                    <img src="{{ $product->thumbnail_url }}" class="img-fluid rounded" style="max-height: 120px;" alt="{{ $product->name }}">
                                </div>
                            @endif
                            <div class="image-upload-previews d-flex flex-wrap gap-2 mb-2"></div>
                            <input type="file" name="main_image" class="form-control image-preview-input" accept="image/jpeg,image/png,image/webp">
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <p class="text-muted small">Updating this product only changes submitted fields. Existing models, designs, shades, sizes, and gallery images are preserved.</p>
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <h3 class="h5 mb-0">Models & designs</h3>
                <button type="button" id="add-model" class="btn btn-outline-primary btn-sm">Add model</button>
            </div>

            @php
                $models = $product->models ?? collect();
            @endphp

            <div id="model-list">
                @forelse ($models as $model)
                    <details class="border rounded p-3 mb-3 model-block" {{ $loop->first ? 'open' : '' }}>
                        <summary class="d-flex justify-content-between align-items-center gap-3 cursor-pointer list-unstyled">
                            <span class="fw-semibold">{{ $model->name ?: 'New model' }}</span>
                            <span class="text-muted small">Toggle</span>
                        </summary>

                        <div class="mt-3">
                            <input type="hidden" name="models[{{ $loop->index }}][id]" value="{{ $model->id }}">
                            <div class="row g-3 align-items-center mb-3">
                                <div class="col-md-5">
                                    <label class="form-label">Model name</label>
                                    <input type="text" name="models[{{ $loop->index }}][name]" class="form-control" value="{{ $model->name }}" required>
                                    <input type="hidden" name="models[{{ $loop->index }}][is_default]" value="0">
                                    <div class="form-check mt-2">
                                        <input type="checkbox" name="models[{{ $loop->index }}][is_default]" class="form-check-input default-model-input" id="model-default-{{ $model->id }}" value="1" {{ $model->is_default ? 'checked' : '' }}>
                                        <label class="form-check-label" for="model-default-{{ $model->id }}">Default model</label>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Model image</label>
                                    <div class="image-upload-field">
                                        @if ($model->main_image)
                                            <div class="existing-image-previews mb-2">
                                                <img src="{{ $model->main_image_url ?? asset('uploads/'.$model->main_image) }}" class="img-fluid rounded" style="max-height: 80px;" alt="{{ $model->name }}">
                                            </div>
                                        @endif
                                        <div class="image-upload-previews d-flex flex-wrap gap-2 mb-2"></div>
                                        <input type="file" name="models[{{ $loop->index }}][main_image]" class="form-control image-preview-input" accept="image/jpeg,image/png,image/webp" data-upload-url="{{ $isEdit ? route('admin.products.models.image', [$product, $model]) : '' }}">
                                        @if ($isEdit)
                                            <div class="small text-muted model-image-upload-status" aria-live="polite"></div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-2 text-end">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-model" disabled title="Existing models are preserved on update">Remove</button>
                                </div>
                            </div>

                            <div class="design-list">
                                @forelse ($model->designs as $design)
                                    <details class="border rounded p-3 mb-2 design-block" {{ $loop->first ? 'open' : '' }}>
                                        <summary class="d-flex justify-content-between align-items-center gap-3 cursor-pointer list-unstyled">
                                            <span class="fw-semibold">{{ $design->name ?: 'New design' }}</span>
                                            <span class="text-muted small">Toggle</span>
                                        </summary>
                                        <div class="mt-3">
                                            <input type="hidden" name="models[{{ $loop->parent->index }}][designs][{{ $loop->index }}][id]" value="{{ $design->id }}">
                                            <div class="row g-3 align-items-center">
                                                <div class="col-md-4">
                                                    <label class="form-label">Design name</label>
                                                    <input type="text" name="models[{{ $loop->parent->index }}][designs][{{ $loop->index }}][name]" class="form-control" value="{{ $design->name }}" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Code</label>
                                                    <input type="text" name="models[{{ $loop->parent->index }}][designs][{{ $loop->index }}][code]" class="form-control" value="{{ $design->code }}">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Thumbnail</label>
                                                    <div class="image-upload-field">
                                                        @if ($design->thumbnail_image)
                                                            <div class="existing-image-previews mb-2">
                                                                <img src="{{ $design->thumbnail_url }}" class="img-fluid rounded" style="max-height: 80px;" alt="{{ $design->name }}">
                                                            </div>
                                                        @endif
                                                        <div class="image-upload-previews d-flex flex-wrap gap-2 mb-2"></div>
                                                        <input type="file" name="models[{{ $loop->parent->index }}][designs][{{ $loop->index }}][thumbnail]" class="form-control image-preview-input" accept="image/jpeg,image/png,image/webp">
                                                    </div>
                                                </div>
                                                <div class="col-md-2 text-end">
                                                    <button type="button" class="btn btn-outline-danger btn-sm remove-design" disabled title="Existing designs are preserved on update">Remove</button>
                                                </div>
                                            </div>

                                        <div class="mt-3">
                                            <h4 class="h6">Shades</h4>
                                            <div class="shade-list">
                                                @forelse ($design->shades as $shade)
                                                    <div class="border rounded p-3 mb-2 shade-block">
                                                        <input type="hidden" name="models[{{ $loop->parent->parent->index }}][designs][{{ $loop->parent->index }}][shades][{{ $loop->index }}][id]" value="{{ $shade->id }}">
                                                        <div class="row g-3 align-items-center">
                                                            <div class="col-md-3">
                                                                <label class="form-label">Shade name</label>
                                                                <input type="text" name="models[{{ $loop->parent->parent->index }}][designs][{{ $loop->parent->index }}][shades][{{ $loop->index }}][name]" class="form-control" value="{{ $shade->name }}" required>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <label class="form-label">Color</label>
                                                                <input type="color" name="models[{{ $loop->parent->parent->index }}][designs][{{ $loop->parent->index }}][shades][{{ $loop->index }}][color_hex]" class="form-control form-control-color" value="{{ $shade->color_hex ?: '#cccccc' }}">
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label">Swatch image</label>
                                                                <div class="image-upload-field">
                                                                    @if ($shade->swatch_image)
                                                                        <div class="existing-image-previews mb-2">
                                                                            <img src="{{ $shade->swatch_url }}" class="img-fluid rounded" style="max-height: 80px;" alt="{{ $shade->name }}">
                                                                        </div>
                                                                    @endif
                                                                    <div class="image-upload-previews d-flex flex-wrap gap-2 mb-2"></div>
                                                                    <input type="file" name="models[{{ $loop->parent->parent->index }}][designs][{{ $loop->parent->index }}][shades][{{ $loop->index }}][swatch]" class="form-control image-preview-input" accept="image/jpeg,image/png,image/webp" data-upload-url="{{ $isEdit ? route('admin.products.shades.image', [$product, $model, $design, $shade, 'swatch']) : '' }}" data-upload-mode="replace">
                                                                    @if ($isEdit)
                                                                        <div class="small text-muted image-upload-status" aria-live="polite"></div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label">Gallery images</label>
                                                                <div class="image-upload-field">
                                                                    @if ($shade->images->isNotEmpty())
                                                                        <div class="existing-image-previews d-flex flex-wrap gap-2 mb-2">
                                                                            @foreach ($shade->images as $image)
                                                                                <img src="{{ $image->url }}" class="rounded" style="height: 64px; width: 64px; object-fit: cover;" alt="Gallery image">
                                                                            @endforeach
                                                                        </div>
                                                                    @endif
                                                                    <div class="image-upload-previews d-flex flex-wrap gap-2 mb-2"></div>
                                                                    <input type="file" name="models[{{ $loop->parent->parent->index }}][designs][{{ $loop->parent->index }}][shades][{{ $loop->index }}][images][0][file][]" class="form-control image-preview-input" accept="image/jpeg,image/png,image/webp" multiple data-upload-url="{{ $isEdit ? route('admin.products.shades.image', [$product, $model, $design, $shade, 'gallery']) : '' }}" data-upload-mode="append">
                                                                    @if ($isEdit)
                                                                        <div class="small text-muted image-upload-status" aria-live="polite"></div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            <div class="col-md-1 text-end">
                                                                <button type="button" class="btn btn-outline-danger btn-sm remove-shade" disabled title="Existing shades are preserved on update">Remove</button>
                                                            </div>
                                                        </div>
                                                        <details class="mt-3 border rounded p-3 size-combinations">
                                                            <summary class="d-flex justify-content-between align-items-center gap-3 cursor-pointer list-unstyled mb-0">
                                                                <span class="fw-semibold">Size combinations</span>
                                                                <span class="text-muted small">Show / hide</span>
                                                            </summary>
                                                            <div class="mt-3">
                                                                <div class="row g-2">
                                                                    @forelse ($shade->sizes as $size)
                                                                        <div class="col-md-4">
                                                                            <div class="input-group">
                                                                                <input type="hidden" name="models[{{ $loop->parent->parent->parent->index }}][designs][{{ $loop->parent->parent->index }}][shades][{{ $loop->parent->index }}][sizes][{{ $loop->index }}][id]" value="{{ $size->id }}">
                                                                                <input type="number" name="models[{{ $loop->parent->parent->parent->index }}][designs][{{ $loop->parent->parent->index }}][shades][{{ $loop->parent->index }}][sizes][{{ $loop->index }}][width_mm]" class="form-control" value="{{ $size->width_mm }}" placeholder="Width mm" required>
                                                                                <span class="input-group-text">×</span>
                                                                                <input type="number" name="models[{{ $loop->parent->parent->parent->index }}][designs][{{ $loop->parent->parent->index }}][shades][{{ $loop->parent->index }}][sizes][{{ $loop->index }}][height_mm]" class="form-control" value="{{ $size->height_mm }}" placeholder="Height mm" required>
                                                                            </div>
                                                                        </div>
                                                                    @empty
                                                                        <div class="col-12 text-muted">No sizes configured for this shade yet.</div>
                                                                    @endforelse
                                                                </div>
                                                            </div>
                                                        </details>
                                                    </div>
                                                @empty
                                                    <div class="text-muted">No shades in this design yet.</div>
                                                @endforelse
                                            </div>
                                            <button type="button" class="btn btn-outline-primary btn-sm add-shade">Add shade</button>
                                        </div>
                                    </div>
                                    </details>
                                @empty
                                    <div class="text-muted">No designs added yet.</div>
                                @endforelse
                            </div>

                            <button type="button" class="btn btn-outline-primary btn-sm add-design mt-3">Add design</button>
                        </div>
                    </details>
                @empty
                    <div class="text-muted">No models added yet.</div>
                @endforelse
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-cta">{{ $isEdit ? 'Update product' : 'Create product' }}</button>
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const modelList = document.getElementById('model-list');
            const productForm = modelList.closest('form');

            productForm?.addEventListener('submit', function (event) {
                const separatelyUploadedInputs = Array.from(productForm.querySelectorAll('input[type="file"][data-upload-url]'));
                if (separatelyUploadedInputs.some(input => input.dataset.uploading === '1')) {
                    event.preventDefault();
                    window.alert('Please wait for the model image uploads to finish, then save the product.');
                    return;
                }

                if (separatelyUploadedInputs.some(input => input.dataset.uploadFailed === '1')) {
                    event.preventDefault();
                    window.alert('Please fix the failed image upload before saving the product.');
                    return;
                }

                const selectedFiles = Array.from(productForm.querySelectorAll('input[type="file"]'))
                    .reduce((total, input) => total + input.files.length, 0);

                if (selectedFiles > 100) {
                    event.preventDefault();
                    window.alert('You can upload up to 100 images per product save.');
                }
            });

            async function uploadSelectedImages(input) {
                const files = Array.from(input.files || []);
                if (!files.length || !input.dataset.uploadUrl) {
                    return;
                }

                const field = input.closest('.image-upload-field');
                const status = field?.querySelector('.image-upload-status, .model-image-upload-status');
                const previewList = field?.querySelector('.image-upload-previews');
                const mode = input.dataset.uploadMode || 'replace';
                input.dataset.uploading = '1';
                delete input.dataset.uploadFailed;
                if (status) {
                    status.className = 'small text-muted model-image-upload-status';
                    status.textContent = `Uploading ${files.length} image${files.length === 1 ? '' : 's'}…`;
                }

                try {
                    let preview = field?.querySelector('.existing-image-previews');
                    if (!preview && field && mode === 'append') {
                        preview = document.createElement('div');
                        preview.className = 'existing-image-previews d-flex flex-wrap gap-2 mb-2';
                        field.insertBefore(preview, previewList);
                    }

                    for (const file of files) {
                        const body = new FormData();
                        body.append('image', file);
                        const response = await fetch(input.dataset.uploadUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            },
                            body,
                        });
                        const result = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            const validationMessage = Object.values(result.errors || {}).flat()[0];
                            throw new Error(validationMessage || result.message || 'Upload failed. Please try again.');
                        }

                        let image = mode === 'append' ? null : preview?.querySelector('img');
                        if (!image && preview) {
                            image = document.createElement('img');
                            image.className = mode === 'append' ? 'rounded' : 'img-fluid rounded';
                            image.style.height = mode === 'append' ? '64px' : '80px';
                            image.style.width = mode === 'append' ? '64px' : 'auto';
                            image.style.objectFit = 'cover';
                            image.alt = mode === 'append' ? 'Gallery image' : 'Uploaded image';
                            preview.appendChild(image);
                        }
                        if (image) {
                            image.src = result.image_url;
                        }
                    }

                    previewList?.replaceChildren();
                    input.value = '';
                    if (status) {
                        status.className = 'small text-success model-image-upload-status';
                        status.textContent = `${files.length} image${files.length === 1 ? '' : 's'} uploaded. Save the product to finish.`;
                    }
                } catch (error) {
                    input.dataset.uploadFailed = '1';
                    input.value = '';
                    if (status) {
                        status.className = 'small text-danger model-image-upload-status';
                        status.textContent = `${error.message} Successfully uploaded files are kept. Select only any files that failed.`;
                    }
                } finally {
                    delete input.dataset.uploading;
                }
            }

            function addShadeBlock(modelIndex, designIndex) {
                const modelBlock = modelList.querySelectorAll('.model-block')[modelIndex];
                const designBlock = modelBlock.querySelectorAll('.design-block')[designIndex];
                const shadeList = designBlock.querySelector('.shade-list');
                const count = shadeList.querySelectorAll('.shade-block').length;
                const template = `
                    <div class="border rounded p-3 mb-2 shade-block">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-3">
                                <label class="form-label">Shade name</label>
                                <input type="text" name="models[${modelIndex}][designs][${designIndex}][shades][${count}][name]" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Color</label>
                                <input type="color" name="models[${modelIndex}][designs][${designIndex}][shades][${count}][color_hex]" class="form-control form-control-color" value="#cccccc">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Swatch image</label>
                                <div class="image-upload-field">
                                    <div class="image-upload-previews d-flex flex-wrap gap-2 mb-2"></div>
                                    <input type="file" name="models[${modelIndex}][designs][${designIndex}][shades][${count}][swatch]" class="form-control image-preview-input" accept="image/jpeg,image/png,image/webp">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Gallery images</label>
                                <div class="image-upload-field">
                                    <div class="image-upload-previews d-flex flex-wrap gap-2 mb-2"></div>
                                    <input type="file" name="models[${modelIndex}][designs][${designIndex}][shades][${count}][images][0][file][]" class="form-control image-preview-input" accept="image/jpeg,image/png,image/webp" multiple>
                                </div>
                            </div>
                            <div class="col-md-1 text-end">
                                <button type="button" class="btn btn-outline-danger btn-sm remove-shade">Remove</button>
                            </div>
                        </div>
                        <details class="mt-3 border rounded p-3 size-combinations">
                            <summary class="d-flex justify-content-between align-items-center gap-3 cursor-pointer list-unstyled mb-0">
                                <span class="fw-semibold">Size combinations</span>
                                <span class="text-muted small">Show / hide</span>
                            </summary>
                            <div class="mt-3">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <div class="input-group">
                                            <input type="number" name="models[${modelIndex}][designs][${designIndex}][shades][${count}][sizes][0][width_mm]" class="form-control" placeholder="Width mm" required>
                                            <span class="input-group-text">×</span>
                                            <input type="number" name="models[${modelIndex}][designs][${designIndex}][shades][${count}][sizes][0][height_mm]" class="form-control" placeholder="Height mm" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </details>
                    </div>
                `;
                shadeList.insertAdjacentHTML('beforeend', template);
            }

            function addDesignBlock(modelIndex) {
                const modelBlock = modelList.querySelectorAll('.model-block')[modelIndex];
                const designList = modelBlock.querySelector('.design-list');
                const count = designList.querySelectorAll('.design-block').length;
                const template = `
                    <details class="border rounded p-3 mb-2 design-block" open>
                        <summary class="d-flex justify-content-between align-items-center gap-3 cursor-pointer">
                            <span class="fw-semibold">New design</span>
                            <span class="text-muted small">Toggle</span>
                        </summary>
                        <div class="mt-3">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-4">
                                    <label class="form-label">Design name</label>
                                    <input type="text" name="models[${modelIndex}][designs][${count}][name]" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Code</label>
                                    <input type="text" name="models[${modelIndex}][designs][${count}][code]" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Thumbnail</label>
                                    <div class="image-upload-field">
                                        <div class="image-upload-previews d-flex flex-wrap gap-2 mb-2"></div>
                                        <input type="file" name="models[${modelIndex}][designs][${count}][thumbnail]" class="form-control image-preview-input" accept="image/jpeg,image/png,image/webp">
                                    </div>
                                </div>
                                <div class="col-md-2 text-end">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-design">Remove</button>
                                </div>
                            </div>
                            <div class="mt-3">
                                <h4 class="h6">Shades</h4>
                                <div class="shade-list"></div>
                                <button type="button" class="btn btn-outline-primary btn-sm add-shade">Add shade</button>
                            </div>
                        </div>
                    </details>
                `;
                designList.insertAdjacentHTML('beforeend', template);
            }

            function addModelBlock() {
                const count = modelList.querySelectorAll('.model-block').length;
                const template = `
                    <details class="border rounded p-3 mb-3 model-block" open>
                        <summary class="d-flex justify-content-between align-items-center gap-3 cursor-pointer list-unstyled">
                            <span class="fw-semibold">New model</span>
                            <span class="text-muted small">Toggle</span>
                        </summary>
                        <div class="mt-3">
                            <div class="row g-3 align-items-center mb-3">
                                <div class="col-md-5">
                                    <label class="form-label">Model name</label>
                                    <input type="text" name="models[${count}][name]" class="form-control" required>
                                    <input type="hidden" name="models[${count}][is_default]" value="0">
                                    <div class="form-check mt-2">
                                        <input type="checkbox" name="models[${count}][is_default]" class="form-check-input default-model-input" id="model-default-new-${count}" value="1">
                                        <label class="form-check-label" for="model-default-new-${count}">Default model</label>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Model image</label>
                                    <div class="image-upload-field">
                                        <div class="image-upload-previews d-flex flex-wrap gap-2 mb-2"></div>
                                        <input type="file" name="models[${count}][main_image]" class="form-control image-preview-input" accept="image/jpeg,image/png,image/webp">
                                    </div>
                                </div>
                                <div class="col-md-2 text-end">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-model">Remove</button>
                                </div>
                            </div>
                            <div class="design-list"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm add-design mt-3">Add design</button>
                        </div>
                    </details>
                `;
                modelList.insertAdjacentHTML('beforeend', template);
            }

            document.getElementById('add-model')?.addEventListener('click', addModelBlock);

            document.addEventListener('click', function (event) {
                const target = event.target;
                if (target.disabled) {
                    return;
                }
                if (target.classList.contains('remove-model')) {
                    target.closest('.model-block').remove();
                    return;
                }
                if (target.classList.contains('remove-design')) {
                    target.closest('.design-block').remove();
                    return;
                }
                if (target.classList.contains('remove-shade')) {
                    target.closest('.shade-block').remove();
                    return;
                }
                if (target.classList.contains('add-design')) {
                    const modelBlock = target.closest('.model-block');
                    const modelIndex = [...modelList.querySelectorAll('.model-block')].indexOf(modelBlock);
                    addDesignBlock(modelIndex);
                    return;
                }
                if (target.classList.contains('add-shade')) {
                    const designBlock = target.closest('.design-block');
                    const modelBlock = target.closest('.model-block');
                    const modelIndex = [...modelList.querySelectorAll('.model-block')].indexOf(modelBlock);
                    const designIndex = [...modelBlock.querySelectorAll('.design-block')].indexOf(designBlock);
                    addShadeBlock(modelIndex, designIndex);
                }
            });

            document.addEventListener('change', function (event) {
                const input = event.target;
                if (input.classList.contains('default-model-input') && input.checked) {
                    document.querySelectorAll('.default-model-input').forEach(function (checkbox) {
                        if (checkbox !== input) {
                            checkbox.checked = false;
                        }
                    });
                }

                if (! input.classList.contains('image-preview-input')) {
                    return;
                }

                if (input.dataset.uploadUrl) {
                    uploadSelectedImages(input);
                }

                const previewList = input.closest('.image-upload-field')?.querySelector('.image-upload-previews');
                if (! previewList) {
                    return;
                }

                previewList.replaceChildren();
                Array.from(input.files || []).forEach(function (file) {
                    if (! file.type.startsWith('image/')) {
                        return;
                    }

                    const preview = document.createElement('img');
                    preview.src = URL.createObjectURL(file);
                    preview.alt = file.name;
                    preview.className = 'rounded';
                    preview.style.height = '64px';
                    preview.style.width = '64px';
                    preview.style.objectFit = 'cover';
                    previewList.appendChild(preview);
                });
            });
        })();
    </script>
@endsection
