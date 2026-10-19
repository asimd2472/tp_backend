<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f5f7f8">
    <title>@yield('title', 'Admin') · TATA Pravesh Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="dashboard-page role-page">
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
                    <a class="sidebar-link {{ request()->routeIs('admin.products.*') ? 'is-active' : '' }}" href="{{ route('admin.products.index') }}">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m3 7 9-4 9 4-9 4-9-4Zm0 0v10l9 4 9-4V7M12 11v10"></path></svg>
                        <span>Products</span>
                    </a>
                    <a class="sidebar-sublink {{ request()->routeIs('admin.products.index') ? 'is-active' : '' }}" href="{{ route('admin.products.index') }}">
                        <span class="sidebar-subnav-dot"></span>
                        <span>Catalog</span>
                    </a>
                    <a class="sidebar-sublink {{ request()->routeIs('admin.products.french-door.*') ? 'is-active' : '' }}" href="{{ route('admin.products.french-door.index') }}">
                        <span class="sidebar-subnav-dot"></span>
                        <span>French-door</span>
                    </a>
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
                <div class="breadcrumb-label"><span>Workspace</span><span class="breadcrumb-slash">/</span><strong>@yield('title', 'Admin')</strong></div>
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

            <main class="dashboard-content role-page-content">
                @yield('content')
            </main>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-4.0.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/admin.js') }}" defer></script>
</body>
</html>
