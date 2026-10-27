<div class="mt-3">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Model name</label>
            <input type="text" name="models[{{ $modelIndex }}][name]" class="form-control" value="{{ old('models.'.$modelIndex.'.name', $model?->name ?? '') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Starting price</label>
            <input type="number" min="0" step="0.01" name="models[{{ $modelIndex }}][starting_price]" class="form-control" value="{{ old('models.'.$modelIndex.'.starting_price', $model?->starting_price ?? 0) }}">
        </div>
        <div class="col-md-5">
            <label class="form-label">Model image</label>
            @if ($model?->main_image)
                <img src="{{ $model->main_image_url }}" alt="{{ $model->name }}" class="d-block rounded mb-2" style="max-height:70px;">
            @endif
            <input type="file" name="models[{{ $modelIndex }}][main_image]" class="form-control" accept="image/jpeg,image/png,image/webp">
        </div>
        <div class="col-md-6">
            <label class="form-label">Short description</label>
            <textarea name="models[{{ $modelIndex }}][short_description]" class="form-control" rows="2">{{ old('models.'.$modelIndex.'.short_description', $model?->short_description ?? '') }}</textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Description</label>
            <textarea name="models[{{ $modelIndex }}][description]" class="form-control" rows="2">{{ old('models.'.$modelIndex.'.description', $model?->description ?? '') }}</textarea>
        </div>
        <div class="col-12 d-flex gap-4">
            <div class="form-check">
                <input type="hidden" name="models[{{ $modelIndex }}][is_default]" value="0">
                <input type="checkbox" name="models[{{ $modelIndex }}][is_default]" id="window-model-default-{{ $modelIndex }}" class="form-check-input" value="1" {{ old('models.'.$modelIndex.'.is_default', $model?->is_default ?? false) ? 'checked' : '' }}>
                <label class="form-check-label" for="window-model-default-{{ $modelIndex }}">Default model</label>
            </div>
            <div class="form-check">
                <input type="hidden" name="models[{{ $modelIndex }}][is_active]" value="0">
                <input type="checkbox" name="models[{{ $modelIndex }}][is_active]" id="window-model-active-{{ $modelIndex }}" class="form-check-input" value="1" {{ old('models.'.$modelIndex.'.is_active', $model?->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="window-model-active-{{ $modelIndex }}">Active</label>
            </div>
            <div class="form-check">
                <input type="hidden" name="models[{{ $modelIndex }}][is_premium]" value="0">
                <input type="checkbox" name="models[{{ $modelIndex }}][is_premium]" id="window-model-premium-{{ $modelIndex }}" class="form-check-input" value="1" {{ old('models.'.$modelIndex.'.is_premium', $model?->is_premium ?? false) ? 'checked' : '' }}>
                <label class="form-check-label" for="window-model-premium-{{ $modelIndex }}">Premium</label>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
        <h3 class="h6 mb-0">Shades and sizes</h3>
        <button type="button" class="btn btn-outline-primary btn-sm" data-add-window-shade data-model-index="{{ $modelIndex }}">Add shade</button>
    </div>
    <div data-shade-list>
        @forelse (($model?->shades ?? []) as $shadeIndex => $shade)
            @include('admin.windows.partials.shade-fields', ['shade' => $shade, 'modelIndex' => $modelIndex, 'shadeIndex' => $shadeIndex])
        @empty
            @include('admin.windows.partials.shade-fields', ['shade' => null, 'modelIndex' => $modelIndex, 'shadeIndex' => 0])
        @endforelse
    </div>
</div>
