@extends('admin.layout')

@section('title', 'French-door')

@section('content_class', 'product-page-content')

@section('body_class', 'dashboard-page product-page')

@section('content')

<section class="page-heading">
                    <div>
                        <div class="page-kicker"><span class="live-dot"></span> PRODUCT CATALOG</div>
                        <h1>French-door<span>.</span></h1>
                        <p>Manage designs and their shades, plus sizes shared across every design.</p>
                    </div>
                </section>

                @if (session('success'))
                    <div class="alert alert-success border-0 shadow-sm role-alert">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger border-0 shadow-sm role-alert">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <section class="role-metric-grid product-metrics" aria-label="French-door options">
                    <article class="metric-card">
                        <div class="metric-top"><span class="metric-label">Designs</span><span class="metric-icon metric-icon-blue"><span class="product-metric-symbol">D</span></span></div>
                        <div class="metric-value">{{ $designs->count() }}</div>
                        <div class="metric-foot"><span class="metric-foot-dot dot-blue"></span>French-door styles</div>
                    </article>
                    <article class="metric-card">
                        <div class="metric-top"><span class="metric-label">Shades</span><span class="metric-icon metric-icon-violet"><span class="product-metric-symbol">S</span></span></div>
                        <div class="metric-value">{{ $designs->sum(fn ($design) => $design->children->count()) }}</div>
                        <div class="metric-foot"><span class="metric-foot-dot dot-violet"></span>Attached to a design</div>
                    </article>
                    <article class="metric-card">
                        <div class="metric-top"><span class="metric-label">Sizes</span><span class="metric-icon metric-icon-teal"><span class="product-metric-symbol">↔</span></span></div>
                        <div class="metric-value">{{ $sizes->count() }}</div>
                        <div class="metric-foot"><span class="metric-foot-dot dot-teal"></span>Shared across designs</div>
                    </article>
                </section>

                <section class="product-panel panel-card" aria-labelledby="designs-heading">
                    <div class="product-section-heading">
                        <div>
                            <span class="panel-eyebrow">DESIGN &amp; SHADE</span>
                            <h2 id="designs-heading">Designs</h2>
                            <p>Each design has its own shades. Add or edit shades inside a design.</p>
                        </div>
                    </div>

                    <form action="{{ route('admin.products.french-door.options.store') }}" method="POST" class="product-create-design">
                        @csrf
                        <input type="hidden" name="type" value="design">
                        <label for="new-design" class="visually-hidden">New design name</label>
                        <input id="new-design" name="name" type="text" class="form-control form-input" placeholder="New design name" value="{{ old('type') === 'design' ? old('name') : '' }}" maxlength="120" required>
                        <label class="product-default-toggle"><input type="checkbox" name="is_default" value="1"><span>Default</span></label>
                        <button type="submit" class="btn btn-primary btn-cta">Add design</button>
                    </form>

                    <div class="design-grid">
                        @forelse ($designs as $design)
                            <article class="design-card">
                                <div class="design-card-heading">
                                    <form action="{{ route('admin.products.french-door.options.update', $design) }}" method="POST" class="design-name-form">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="type" value="design">
                                        <label class="visually-hidden" for="design-name-{{ $design->id }}">Design name</label>
                                        <input id="design-name-{{ $design->id }}" name="name" type="text" class="form-control form-input" value="{{ $design->name }}" maxlength="120" required>
                                        <label class="product-default-toggle" title="Use this design as the frontend default"><input type="checkbox" name="is_default" value="1" {{ $design->is_default ? 'checked' : '' }}><span>Default</span></label>
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Save</button>
                                    </form>
                                    <form action="{{ route('admin.products.french-door.options.destroy', $design) }}" method="POST" onsubmit="return confirm('Delete this design and its shades?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete {{ $design->name }}">Delete</button>
                                    </form>
                                </div>

                                <div class="shade-list-heading"><span>Shades</span><span>{{ $design->children->count() }}</span></div>
                                <div class="shade-list">
                                    @forelse ($design->children as $shade)
                                        <div class="shade-item">
                                            <span class="shade-swatch" style="--shade-color: {{ $shade->color_hex ?: '#e8ecec' }}" aria-hidden="true"></span>
                                            <div class="shade-image-preview">
                                                @if ($shade->image_path)
                                                    <img src="{{ asset('uploads/' . ltrim($shade->image_path, '/')) }}" alt="{{ $shade->name }} product">
                                                @else
                                                    <span aria-hidden="true">Add<br>photo</span>
                                                @endif
                                            </div>
                                            <form action="{{ route('admin.products.french-door.options.update', $shade) }}" method="POST" enctype="multipart/form-data" class="shade-edit-form">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="type" value="shade">
                                                <input type="hidden" name="parent_id" value="{{ $design->id }}">
                                                <label class="visually-hidden" for="shade-name-{{ $shade->id }}">Shade name</label>
                                                <input id="shade-name-{{ $shade->id }}" name="name" type="text" class="form-control form-input" value="{{ $shade->name }}" maxlength="120" required>
                                                <label class="visually-hidden" for="shade-color-{{ $shade->id }}">Shade color</label>
                                                <input id="shade-color-{{ $shade->id }}" name="color_hex" type="color" class="form-control form-control-color shade-color-input" value="{{ $shade->color_hex ?: '#e8ecec' }}" title="Choose shade color">
                                                <label class="shade-upload-label" for="shade-image-{{ $shade->id }}">Upload photo</label>
                                                <input id="shade-image-{{ $shade->id }}" name="image" type="file" class="form-control form-control-sm shade-image-input" accept="image/jpeg,image/png,image/webp">
                                                <label class="product-default-toggle" title="Use this shade and its design as frontend defaults"><input type="checkbox" name="is_default" value="1" {{ $shade->is_default ? 'checked' : '' }}><span>Default</span></label>
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">Save</button>
                                            </form>
                                            <form action="{{ route('admin.products.french-door.options.destroy', $shade) }}" method="POST" onsubmit="return confirm('Delete this shade?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-link text-danger shade-delete" aria-label="Delete {{ $shade->name }}">×</button>
                                            </form>
                                        </div>
                                    @empty
                                        <p class="product-empty-note">No shades yet. Add the first one below.</p>
                                    @endforelse
                                </div>

                                <form action="{{ route('admin.products.french-door.options.store') }}" method="POST" enctype="multipart/form-data" class="shade-create-form">
                                    @csrf
                                    <input type="hidden" name="type" value="shade">
                                    <input type="hidden" name="parent_id" value="{{ $design->id }}">
                                    <label class="visually-hidden" for="new-shade-{{ $design->id }}">New shade name</label>
                                    <input id="new-shade-{{ $design->id }}" name="name" type="text" class="form-control form-input" placeholder="New shade" maxlength="120" required>
                                    <label class="visually-hidden" for="new-shade-color-{{ $design->id }}">New shade color</label>
                                    <input id="new-shade-color-{{ $design->id }}" name="color_hex" type="color" class="form-control form-control-color shade-color-input" value="#b58a62" title="Choose shade color">
                                    <label class="shade-upload-label" for="new-shade-image-{{ $design->id }}">Choose photo</label>
                                    <input id="new-shade-image-{{ $design->id }}" name="image" type="file" class="form-control form-control-sm shade-image-input" accept="image/jpeg,image/png,image/webp" required>
                                    <label class="product-default-toggle" title="Set this shade and its design as frontend defaults"><input type="checkbox" name="is_default" value="1"><span>Default</span></label>
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Add shade</button>
                                </form>
                            </article>
                        @empty
                            <div class="role-empty product-empty">
                                <span class="empty-directory-icon">+</span>
                                <strong>No designs yet</strong>
                                <span>Add a design above to start building the French-door options.</span>
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="product-panel panel-card product-sizes-panel" aria-labelledby="sizes-heading">
                    <div class="product-section-heading">
                        <div>
                            <span class="panel-eyebrow">SHARED OPTIONS</span>
                            <h2 id="sizes-heading">Sizes</h2>
                            <p>Sizes are product-wide and are not linked to a design or shade.</p>
                        </div>
                    </div>

                    <form action="{{ route('admin.products.french-door.options.store') }}" method="POST" class="size-create-form">
                        @csrf
                        <input type="hidden" name="type" value="size">
                        <div class="size-input-group">
                            <label for="new-size-width" class="form-label">Width (mm)</label>
                            <input id="new-size-width" name="width_mm" type="number" class="form-control form-input" min="1" max="9999" placeholder="2134" required>
                        </div>
                        <span class="size-times" aria-hidden="true">×</span>
                        <div class="size-input-group">
                            <label for="new-size-height" class="form-label">Height (mm)</label>
                            <input id="new-size-height" name="height_mm" type="number" class="form-control form-input" min="1" max="9999" placeholder="2134" required>
                        </div>
                        <label class="product-default-toggle"><input type="checkbox" name="is_default" value="1"><span>Default</span></label>
                        <button type="submit" class="btn btn-primary btn-cta">Add size</button>
                    </form>

                    <div class="size-list">
                        @forelse ($sizes as $size)
                            <div class="size-item">
                                <form action="{{ route('admin.products.french-door.options.update', $size) }}" method="POST" class="size-edit-form">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="type" value="size">
                                    <label class="visually-hidden" for="size-width-{{ $size->id }}">Width in millimeters</label>
                                    <input id="size-width-{{ $size->id }}" name="width_mm" type="number" class="form-control form-input" min="1" max="9999" value="{{ $size->width_mm }}" required>
                                    <span aria-hidden="true">×</span>
                                    <label class="visually-hidden" for="size-height-{{ $size->id }}">Height in millimeters</label>
                                    <input id="size-height-{{ $size->id }}" name="height_mm" type="number" class="form-control form-input" min="1" max="9999" value="{{ $size->height_mm }}" required>
                                    <span class="size-unit">mm</span>
                                    <label class="product-default-toggle" title="Use this size as the frontend default"><input type="checkbox" name="is_default" value="1" {{ $size->is_default ? 'checked' : '' }}><span>Default</span></label>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Save</button>
                                </form>
                                <form action="{{ route('admin.products.french-door.options.destroy', $size) }}" method="POST" onsubmit="return confirm('Delete this size?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        @empty
                            <div class="role-empty product-empty">
                                <strong>No sizes yet</strong>
                                <span>Add a shared size above.</span>
                            </div>
                        @endforelse
                    </div>
                </section>

@endsection
