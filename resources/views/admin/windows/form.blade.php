@extends('admin.layout')

@section('title', $isEdit ? 'Manage Window' : 'Add Window')

@section('content')
    <section class="page-heading">
        <div>
            <div class="page-kicker"><span class="live-dot"></span> WINDOW MANAGEMENT</div>
            <h1>{{ $isEdit ? 'Manage window product' : 'Add window product' }}<span>.</span></h1>
            <p>Manage product details, models, shades, gallery images, and available sizes. Windows do not use designs.</p>
        </div>
    </section>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm role-alert">{{ session('success') }}</div>
    @endif

    <div class="panel-card p-4">
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Window product was not saved. Please correct these issues:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ $isEdit ? route('admin.windows.update', $window) : route('admin.windows.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <h2 class="h5 mb-3">Window product details</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="name">Product name</label>
                    <input id="name" name="name" type="text" class="form-control" value="{{ old('name', $window->name) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="category">Category</label>
                    <input id="category" name="category" type="text" class="form-control" value="{{ old('category', $window->category) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="slug">Slug</label>
                    <input id="slug" name="slug" type="text" class="form-control" value="{{ old('slug', $window->slug) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="model">Model / Series</label>
                    <input id="model" name="model" type="text" class="form-control" value="{{ old('model', $window->model) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="starting_price">Starting price</label>
                    <input id="starting_price" name="starting_price" type="number" min="0" step="0.01" class="form-control" value="{{ old('starting_price', $window->starting_price ?? 0) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="rating">Rating</label>
                    <input id="rating" name="rating" type="number" min="0" max="5" step="0.1" class="form-control" value="{{ old('rating', $window->rating) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="short_description">Short description</label>
                    <textarea id="short_description" name="short_description" class="form-control" rows="2">{{ old('short_description', $window->short_description) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="description">Full description</label>
                    <textarea id="description" name="description" class="form-control" rows="2">{{ old('description', $window->description) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="review">Review</label>
                    <textarea id="review" name="review" class="form-control" rows="2">{{ old('review', $window->review) }}</textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="main_image">Main product image</label>
                    @if ($window->main_image)
                        <img src="{{ asset('uploads/'.$window->main_image) }}" alt="{{ $window->name }}" class="d-block rounded mb-2" style="max-height:70px;">
                    @endif
                    <input id="main_image" type="file" name="main_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="col-md-3 d-flex align-items-end gap-4 pb-2">
                    <div class="form-check">
                        <input id="is_premium" name="is_premium" class="form-check-input" type="checkbox" value="1" {{ old('is_premium', $window->is_premium ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_premium">Premium</label>
                    </div>
                    <div class="form-check">
                        <input id="is_active" name="is_active" class="form-check-input" type="checkbox" value="1" {{ old('is_active', $window->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>

            <hr class="my-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h5 mb-1">Frequently asked questions</h2>
                    <p class="small text-muted mb-0">Optional product questions and answers.</p>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" data-question-add="faqs">Add FAQ</button>
            </div>
            <div data-question-list="faqs">
                @foreach (old('faqs', $window->faqs ?? []) as $index => $item)
                    <div class="row g-2 mb-2" data-question-item>
                        <div class="col-md-5"><input name="faqs[{{ $index }}][question]" class="form-control" placeholder="Question" value="{{ $item['question'] ?? '' }}"></div>
                        <div class="col-md-6"><input name="faqs[{{ $index }}][answer]" class="form-control" placeholder="Answer" value="{{ $item['answer'] ?? '' }}"></div>
                        <div class="col-md-1"><button type="button" class="btn btn-outline-danger" data-question-remove aria-label="Remove FAQ">×</button></div>
                    </div>
                @endforeach
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
                <div>
                    <h2 class="h5 mb-1">Product specifications</h2>
                    <p class="small text-muted mb-0">Optional specification question and answer pairs.</p>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" data-question-add="specifications">Add specification</button>
            </div>
            <div data-question-list="specifications">
                @foreach (old('specifications', $window->specifications ?? []) as $index => $item)
                    <div class="row g-2 mb-2" data-question-item>
                        <div class="col-md-5"><input name="specifications[{{ $index }}][question]" class="form-control" placeholder="Specification" value="{{ $item['question'] ?? '' }}"></div>
                        <div class="col-md-6"><input name="specifications[{{ $index }}][answer]" class="form-control" placeholder="Value" value="{{ $item['answer'] ?? '' }}"></div>
                        <div class="col-md-1"><button type="button" class="btn btn-outline-danger" data-question-remove aria-label="Remove specification">×</button></div>
                    </div>
                @endforeach
            </div>

            <hr class="my-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h5 mb-1">Models, shades, and sizes</h2>
                    <p class="small text-muted mb-0">Add models and their shades directly; there is no design level for windows.</p>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="add-window-model">Add model</button>
            </div>

            <div id="window-model-list">
                @forelse ($window->models as $modelIndex => $model)
                    <details class="border rounded p-3 mb-3" open>
                        <summary class="fw-semibold cursor-pointer">{{ $model->name }}</summary>
                        <input type="hidden" name="models[{{ $modelIndex }}][id]" value="{{ $model->id }}">
                        @include('admin.windows.partials.model-fields', ['model' => $model, 'modelIndex' => $modelIndex])
                    </details>
                @empty
                    <details class="border rounded p-3 mb-3" open>
                        <summary class="fw-semibold cursor-pointer">New model</summary>
                        @include('admin.windows.partials.model-fields', ['model' => null, 'modelIndex' => 0])
                    </details>
                @endforelse
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-cta">{{ $isEdit ? 'Save window product' : 'Create window product' }}</button>
                <a href="{{ route('admin.windows.index') }}" class="btn btn-outline-secondary">Back to windows</a>
            </div>
        </form>
    </div>

    <template id="window-model-template">
        <details class="border rounded p-3 mb-3" open>
            <summary class="fw-semibold cursor-pointer">New model</summary>
            @include('admin.windows.partials.model-fields', ['model' => null, 'modelIndex' => '__MODEL__'])
        </details>
    </template>
    <template id="window-shade-template">
        @include('admin.windows.partials.shade-fields', ['shade' => null, 'modelIndex' => '__MODEL__', 'shadeIndex' => '__SHADE__'])
    </template>
    <template id="window-faq-template">
        <div class="row g-2 mb-2" data-question-item>
            <div class="col-md-5"><input name="__TYPE__[__INDEX__][question]" class="form-control" placeholder="Question"></div>
            <div class="col-md-6"><input name="__TYPE__[__INDEX__][answer]" class="form-control" placeholder="Answer"></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger" data-question-remove aria-label="Remove item">×</button></div>
        </div>
    </template>
    <script>
        (function () {
            const modelList = document.getElementById('window-model-list');
            const modelTemplate = document.getElementById('window-model-template').innerHTML;
            const shadeTemplate = document.getElementById('window-shade-template').innerHTML;
            let nextModel = Math.max(-1, ...Array.from(modelList.querySelectorAll('[name*="models["]'))
                .map(input => Number(input.name.match(/models\[(\d+)\]/)?.[1] ?? -1))) + 1;

            document.getElementById('add-window-model').addEventListener('click', function () {
                const index = nextModel++;
                modelList.insertAdjacentHTML('beforeend', modelTemplate.replaceAll('__MODEL__', String(index)));
            });

            document.addEventListener('click', function (event) {
                const addShade = event.target.closest('[data-add-window-shade]');
                if (addShade) {
                    const modelIndex = addShade.dataset.modelIndex;
                    const list = addShade.closest('[data-shade-list]');
                    const shadeIndexes = Array.from(list.querySelectorAll('[name*="[shades]["]'))
                        .map(input => Number(input.name.match(/\[shades\]\[(\d+)\]/)?.[1] ?? -1));
                    const nextShade = Math.max(-1, ...shadeIndexes) + 1;
                    list.insertAdjacentHTML('beforeend', shadeTemplate
                        .replaceAll('__MODEL__', modelIndex)
                        .replaceAll('__SHADE__', String(nextShade)));
                    return;
                }

                const addQuestion = event.target.closest('[data-question-add]');
                if (addQuestion) {
                    const type = addQuestion.dataset.questionAdd;
                    const list = document.querySelector(`[data-question-list="${type}"]`);
                    const indexes = Array.from(list.querySelectorAll('[name]'))
                        .map(input => Number(input.name.match(/\[(\d+)\]/)?.[1] ?? -1));
                    const next = Math.max(-1, ...indexes) + 1;
                    const row = document.getElementById('window-faq-template').innerHTML
                        .replaceAll('__TYPE__', type)
                        .replaceAll('__INDEX__', String(next));
                    list.insertAdjacentHTML('beforeend', row);
                    return;
                }

                const removeQuestion = event.target.closest('[data-question-remove]');
                if (removeQuestion) {
                    removeQuestion.closest('[data-question-item]').remove();
                }
            });

        })();
    </script>
@endsection
