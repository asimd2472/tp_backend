<div class="border rounded p-3 mb-2">
    @if ($shade)
        <input type="hidden" name="models[{{ $modelIndex }}][shades][{{ $shadeIndex }}][id]" value="{{ $shade->id }}">
    @endif
    <div class="row g-3 align-items-start">
        <div class="col-md-3">
            <label class="form-label">Shade name</label>
            <input type="text" name="models[{{ $modelIndex }}][shades][{{ $shadeIndex }}][name]" class="form-control" value="{{ old('models.'.$modelIndex.'.shades.'.$shadeIndex.'.name', $shade?->name ?? '') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Color (hex)</label>
            <input type="text" name="models[{{ $modelIndex }}][shades][{{ $shadeIndex }}][color_hex]" class="form-control" placeholder="#C58A45" value="{{ old('models.'.$modelIndex.'.shades.'.$shadeIndex.'.color_hex', $shade?->color_hex ?? '') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Swatch image</label>
            <div class="image-upload-field">
                @if ($shade?->swatch_image)
                    <div class="existing-image-previews mb-2">
                        <img src="{{ $shade->swatch_url }}" alt="{{ $shade->name }}" class="img-fluid rounded" style="max-height:54px;">
                    </div>
                @endif
                <div class="image-upload-previews d-flex flex-wrap gap-2 mb-2"></div>
                <input type="file" name="models[{{ $modelIndex }}][shades][{{ $shadeIndex }}][swatch]" class="form-control image-preview-input" accept="image/jpeg,image/png,image/webp" @if ($isEdit && $model?->exists && $shade?->exists) data-upload-url="{{ route('admin.windows.shades.image', [$window, $model, $shade, 'swatch']) }}" data-upload-mode="replace" @endif>
                @if ($isEdit && $model?->exists && $shade?->exists)
                    <div class="small text-muted image-upload-status" aria-live="polite"></div>
                @endif
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Gallery images</label>
            <div class="image-upload-field">
                @if ($shade?->images->isNotEmpty())
                    <div class="existing-image-previews d-flex flex-wrap gap-2 mb-2">
                    @foreach ($shade->images as $image)
                        <img src="{{ $image->url }}" alt="{{ $shade->name }} gallery image" class="rounded" style="width:48px;height:48px;object-fit:cover;">
                    @endforeach
                    </div>
                @endif
                <div class="image-upload-previews d-flex flex-wrap gap-2 mb-2"></div>
                <input type="file" name="models[{{ $modelIndex }}][shades][{{ $shadeIndex }}][images][]" class="form-control image-preview-input" accept="image/jpeg,image/png,image/webp" multiple @if ($isEdit && $model?->exists && $shade?->exists) data-upload-url="{{ route('admin.windows.shades.image', [$window, $model, $shade, 'gallery']) }}" data-upload-mode="append" @endif>
                @if ($isEdit && $model?->exists && $shade?->exists)
                    <div class="small text-muted image-upload-status" aria-live="polite"></div>
                @endif
            </div>
        </div>
        <div class="col-12">
            <label class="form-label">Sizes (width x height in mm; comma or line separated)</label>
            @php
                $sizeText = $shade ? $shade->sizes->map(fn ($size) => $size->width_mm.'x'.$size->height_mm)->implode(', ') : '';
            @endphp
            <textarea name="models[{{ $modelIndex }}][shades][{{ $shadeIndex }}][sizes]" class="form-control" rows="2" placeholder="914x1219, 914x1524">{{ old('models.'.$modelIndex.'.shades.'.$shadeIndex.'.sizes', $sizeText) }}</textarea>
        </div>
    </div>
</div>
