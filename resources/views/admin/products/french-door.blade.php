<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>French-door · TATA Pravesh Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="dashboard-page product-page">
    <div class="dashboard-layout">
        <aside class="admin-sidebar" id="adminSidebar" aria-labelledby="sidebarBrand">
            <div class="sidebar-head">
                <a class="brand-lockup" id="sidebarBrand" href="{{ route('admin.dashboard') }}">
                    <span class="brand-mark" aria-hidden="true"><span></span><span></span><span></span></span>
                    <span class="brand-wordmark"><strong>TATA</strong><span>PRAVESH</span></span>
                </a>
                <button class="btn-close d-lg-none" type="button" data-sidebar-close="adminSidebar" aria-label="Close menu"></button>
            </div>

            <div class="sidebar-label">WORKSPACE</div>
            <nav class="sidebar-nav" aria-label="Main navigation">
                <a class="sidebar-link" href="{{ route('admin.dashboard') }}">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="3" width="7" height="8" rx="1.5"></rect><rect x="14" y="3" width="7" height="5" rx="1.5"></rect><rect x="14" y="12" width="7" height="9" rx="1.5"></rect><rect x="3" y="15" width="7" height="6" rx="1.5"></rect></svg>
                    <span>Overview</span>
                </a>
                <a class="sidebar-link" href="{{ route('admin.roles.index') }}">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3.5 4.5 7v5.6c0 3.8 2.6 6.9 7.5 9.9 4.9-3 7.5-6.1 7.5-9.9V7L12 3.5Zm-1.5 7.8 2.1 2.1 4.2-4.2"></path></svg>
                    <span>Role management</span>
                </a>
                <div class="sidebar-product-group">
                    <a class="sidebar-link sidebar-product-toggle is-active" href="#" aria-expanded="false">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m3 7 9-4 9 4-9 4-9-4Zm0 0v10l9 4 9-4V7M12 11v10"></path></svg>
                        <span>Products</span>
                        <span class="sidebar-nav-caret" aria-hidden="true">▾</span>
                    </a>
                    <div class="sidebar-product-menu">
                        <div class="sidebar-product-menu-inner">
                            <div class="sidebar-product-category">
                                <a class="sidebar-sublink {{ request()->routeIs('admin.products.index') || request()->routeIs('admin.products.french-door.*') ? 'is-active' : '' }}" href="{{ route('admin.products.index') }}">
                                    <span class="sidebar-subnav-dot"></span>
                                    <span>Door</span>
                                </a>
                                <div class="sidebar-subgroup">
                                    <a class="sidebar-sublink {{ request()->routeIs('admin.products.index') ? 'is-active' : '' }}" href="{{ route('admin.products.index') }}">
                                        <span class="sidebar-subnav-dot"></span>
                                        <span>Catalog</span>
                                    </a>
                                    <a class="sidebar-sublink {{ request()->routeIs('admin.products.french-door.*') ? 'is-active' : '' }}" href="{{ route('admin.products.french-door.index') }}">
                                        <span class="sidebar-subnav-dot"></span>
                                        <span>French-door</span>
                                    </a>
                                </div>
                            </div>
                            <a class="sidebar-sublink" href="{{ route('admin.windows.index') }}">
                                <span class="sidebar-subnav-dot"></span>
                                <span>Window</span>
                            </a>
                        </div>
                    </div>
                </div>
                <a class="sidebar-link" href="{{ route('admin.dashboard') }}#security">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m12 3 7 3v5.6c0 4.2-2.9 7.5-7 9.4-4.1-1.9-7-5.2-7-9.4V6l7-3Z"></path><path d="m9 12 2 2 4-4"></path></svg>
                    <span>Security</span>
                </a>
            </nav>

            <div class="sidebar-bottom">
                <div class="sidebar-support">
                    <div class="support-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M9.6 9a2.5 2.5 0 1 1 4.2 1.8c-1.2 1.1-1.8 1.5-1.8 3M12 17.2h.01"></path></svg></div>
                    <div><strong>Need a hand?</strong><span>Contact your system owner</span></div>
                </div>
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button class="sidebar-logout" type="submit">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 17l5-5-5-5m5 5H3m9-9h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"></path></svg>
                        <span>Sign out</span>
                    </button>
                </form>
                <div class="sidebar-version">TATA PRAVESH <span>ADMIN CONSOLE</span></div>
            </div>
        </aside>

        <div class="dashboard-main">
            <header class="topbar">
                <button class="btn sidebar-toggle d-lg-none" type="button" data-sidebar-toggle="adminSidebar" aria-controls="adminSidebar" aria-label="Open navigation">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg>
                </button>
                <div class="breadcrumb-label"><span>Products</span><span class="breadcrumb-slash">/</span><strong>French-door</strong></div>
                <div class="topbar-right">
                    <span class="topbar-date">{{ now()->format('D, d M Y') }}</span>
                    <span class="topbar-separator"></span>
                    <div class="topbar-user">
                        <span class="user-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="topbar-user-copy"><strong>{{ auth()->user()->name }}</strong><span>Administrator</span></span>
                        <span class="admin-indicator" title="Administrator account"></span>
                    </div>
                </div>
            </header>

            <main class="dashboard-content product-page-content">
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
            </main>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
</body>
</html>
