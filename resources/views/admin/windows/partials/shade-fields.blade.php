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
            @if ($shade?->swatch_image)
                <img src="{{ $shade->swatch_url }}" alt="{{ $shade->name }}" class="d-block rounded mb-2" style="max-height:54px;">
            @endif
            <input type="file" name="models[{{ $modelIndex }}][shades][{{ $shadeIndex }}][swatch]" class="form-control" accept="image/jpeg,image/png,image/webp">
        </div>
        <div class="col-md-4">
            <label class="form-label">Gallery images</label>
            @if ($shade)
                <div class="d-flex flex-wrap gap-2 mb-2">
                    @foreach ($shade->images as $image)
                        <img src="{{ $image->url }}" alt="{{ $shade->name }} gallery image" class="rounded" style="width:48px;height:48px;object-fit:cover;">
                    @endforeach
                </div>
            @endif
            <input type="file" name="models[{{ $modelIndex }}][shades][{{ $shadeIndex }}][images][]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
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
