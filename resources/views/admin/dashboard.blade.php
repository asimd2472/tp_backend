<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f5f7f8">
    <title>Overview · TATA Pravesh Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="dashboard-page">
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
                <a class="sidebar-link is-active" href="#overview">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="3" width="7" height="8" rx="1.5"></rect><rect x="14" y="3" width="7" height="5" rx="1.5"></rect><rect x="14" y="12" width="7" height="9" rx="1.5"></rect><rect x="3" y="15" width="7" height="6" rx="1.5"></rect></svg>
                    <span>Overview</span>
                </a>
                <a class="sidebar-link" href="#directory">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5"></circle><path d="M2.8 20c.4-3.3 2.5-5 6.2-5s5.8 1.7 6.2 5M17 5.5a3.5 3.5 0 0 1 0 6.8m.8 2.8c2.2.6 3.4 2.1 3.5 4.9"></path></svg>
                    <span>Account directory</span>
                    <span class="nav-count">{{ $totalUsers }}</span>
                </a>
                <a class="sidebar-link" href="{{ route('admin.roles.index') }}">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3.5 4.5 7v5.6c0 3.8 2.6 6.9 7.5 9.9 4.9-3 7.5-6.1 7.5-9.9V7L12 3.5Zm-1.5 7.8 2.1 2.1 4.2-4.2"></path></svg>
                    <span>Role management</span>
                </a>
                <div class="sidebar-product-group">
                    <a class="sidebar-link sidebar-product-toggle {{ request()->routeIs('admin.products.*', 'admin.windows.*') ? 'is-active' : '' }}" href="#" aria-expanded="false">
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
                            <a class="sidebar-sublink {{ request()->routeIs('admin.windows.*') ? 'is-active' : '' }}" href="{{ route('admin.windows.index') }}">
                                <span class="sidebar-subnav-dot"></span>
                                <span>Window</span>
                            </a>
                        </div>
                    </div>
                </div>
                <a class="sidebar-link" href="#security">
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
                <div class="breadcrumb-label"><span>Workspace</span><span class="breadcrumb-slash">/</span><strong>Overview</strong></div>
                <div class="topbar-right">
                    <span class="topbar-date">{{ now()->format('D, d M Y') }}</span>
                    <span class="topbar-separator"></span>
                    <div class="topbar-user">
                        <span class="user-avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                        <span class="topbar-user-copy"><strong>{{ $user->name }}</strong><span>Administrator</span></span>
                        <span class="admin-indicator" title="Administrator account"></span>
                    </div>
                </div>
            </header>

            <main class="dashboard-content" id="overview">
                <section class="page-heading">
                    <div>
                        <div class="page-kicker"><span class="live-dot"></span> YOUR ADMIN WORKSPACE</div>
                        <h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ $user->name }}<span>.</span></h1>
                        <p>Here’s what’s happening across your TATA Pravesh workspace.</p>
                    </div>
                    <div class="heading-date">
                        <span class="date-icon"><svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><rect x="3" y="5" width="14" height="12" rx="2"></rect><path d="M6.5 3v4M13.5 3v4M3 9h14"></path></svg></span>
                        <span><strong>{{ now()->format('F j, Y') }}</strong><small>Local workspace date</small></span>
                    </div>
                </section>

                <section class="metric-grid" aria-label="Workspace summary">
                    <article class="metric-card">
                        <div class="metric-top"><span class="metric-label">Registered accounts</span><span class="metric-icon metric-icon-blue"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5"></circle><path d="M2.8 20c.4-3.3 2.5-5 6.2-5s5.8 1.7 6.2 5M17 5.5a3.5 3.5 0 0 1 0 6.8m.8 2.8c2.2.6 3.4 2.1 3.5 4.9"></path></svg></span></div>
                        <div class="metric-value">{{ number_format($totalUsers) }}</div>
                        <div class="metric-foot"><span class="metric-foot-dot dot-blue"></span>All accounts in your workspace</div>
                    </article>
                    <article class="metric-card">
                        <div class="metric-top"><span class="metric-label">Administrators</span><span class="metric-icon metric-icon-violet"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m12 3 7 3v5.6c0 4.2-2.9 7.5-7 9.4-4.1-1.9-7-5.2-7-9.4V6l7-3Z"></path><path d="m9 12 2 2 4-4"></path></svg></span></div>
                        <div class="metric-value">{{ number_format($adminCount) }}</div>
                        <div class="metric-foot"><span class="metric-foot-dot dot-violet"></span>Accounts with elevated access</div>
                    </article>
                    <article class="metric-card">
                        <div class="metric-top"><span class="metric-label">Standard members</span><span class="metric-icon metric-icon-teal"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="3.5"></circle><path d="M4.5 20c.5-3.8 3-5.7 7.5-5.7s7 1.9 7.5 5.7"></path></svg></span></div>
                        <div class="metric-value">{{ number_format($memberCount) }}</div>
                        <div class="metric-foot"><span class="metric-foot-dot dot-teal"></span>Accounts without admin access</div>
                    </article>
                </section>

                <section class="dashboard-panels">
                    <article class="panel-card growth-panel">
                        <div class="panel-heading">
                            <div><span class="panel-eyebrow">ACCOUNT ACTIVITY</span><h2>Registration overview</h2></div>
                            <span class="period-chip"><span class="period-dot"></span>Last 6 months</span>
                        </div>
                        @php($maxRegistrations = max($chartValues ?: [0]))
                        <div class="bar-chart" role="img" aria-label="New account registrations over the last six months">
                            @foreach ($chartLabels as $index => $label)
                                @php($value = $chartValues[$index])
                                <div class="chart-column">
                                    <span class="chart-value">{{ $value }}</span>
                                    <div class="chart-track"><span class="chart-bar {{ $value > 0 ? 'has-value' : '' }}" style="height: {{ $maxRegistrations > 0 ? round(($value / $maxRegistrations) * 100) : 0 }}%"></span></div>
                                    <span class="chart-label">{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="chart-caption"><span class="chart-caption-key"></span>New accounts created</div>
                    </article>

                    <article class="panel-card security-panel" id="security">
                        <div class="panel-heading">
                            <div><span class="panel-eyebrow">ACCESS &amp; SECURITY</span><h2>Workspace protected</h2></div>
                            <span class="security-status">ACTIVE</span>
                        </div>
                        <div class="security-emblem"><svg viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="m24 5 16 6.5v12.2c0 9.2-6.6 15.8-16 20.3C14.6 39.5 8 32.9 8 23.7V11.5L24 5Z"></path><path d="m17.5 24 4.2 4.2 9-9"></path></svg></div>
                        <h3>Admin access is active</h3>
                        <p>Your session is protected. Only accounts explicitly granted administrator access can open this workspace.</p>
                        <div class="security-divider"></div>
                        <div class="security-detail"><span class="security-check">✓</span><span>Administrator-only routes</span><span class="detail-enabled">Enabled</span></div>
                        <div class="security-detail"><span class="security-check">✓</span><span>Protected sign-in session</span><span class="detail-enabled">Enabled</span></div>
                    </article>
                </section>

                <section class="panel-card directory-panel" id="directory">
                    <div class="directory-heading">
                        <div><span class="panel-eyebrow">PEOPLE</span><h2>Account directory</h2><p>A view of the accounts registered in your workspace.</p></div>
                        <div class="directory-tools">
                            <label class="directory-search" for="directorySearch">
                                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="8.8" cy="8.8" r="5.8"></circle><path d="m13.2 13.2 4 4"></path></svg>
                                <input type="search" id="directorySearch" placeholder="Search accounts" aria-label="Search accounts">
                            </label>
                            <span class="directory-total">{{ number_format($totalUsers) }} total</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table directory-table align-middle mb-0">
                            <thead><tr><th scope="col">ACCOUNT</th><th scope="col">ACCESS LEVEL</th><th scope="col">DATE ADDED</th><th scope="col">EMAIL ADDRESS</th></tr></thead>
                            <tbody id="directoryRows">
                                @forelse ($users as $account)
                                    <tr data-account-row>
                                        <td>
                                            <div class="account-cell">
                                                <span class="account-avatar {{ $account->isAdmin() ? 'avatar-admin' : '' }}">{{ mb_strtoupper(mb_substr($account->name, 0, 1)) }}</span>
                                                <span class="account-copy"><strong>{{ $account->name }}</strong><small>{{ $account->isAdmin() ? 'Administrator account' : 'Workspace member' }}</small></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="role-pill {{ $account->isAdmin() ? 'role-admin' : 'role-member' }}">
                                                <span></span>
                                                {{ $account->getRoleNames()->first() ?: ($account->isAdmin() ? 'Administrator' : 'Member') }}
                                            </span>
                                        </td>
                                        <td class="date-cell">{{ $account->created_at?->format('M d, Y') ?? '—' }}</td>
                                        <td class="email-cell">{{ $account->email }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4"><div class="empty-directory"><span class="empty-directory-icon">+</span><strong>No accounts yet</strong><span>New workspace accounts will appear here.</span></div></td></tr>
                                @endforelse
                                <tr class="search-empty d-none" id="searchEmpty"><td colspan="4"><div class="empty-directory"><strong>No matching accounts</strong><span>Try another name or email address.</span></div></td></tr>
                            </tbody>
                        </table>
                    </div>

                    @if ($users->hasPages())
                        <div class="directory-pagination">
                            <span>Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ number_format($users->total()) }} accounts</span>
                            {{ $users->onEachSide(1)->links('pagination::bootstrap-5') }}
                        </div>
                    @else
                        <div class="directory-pagination"><span>Showing {{ $users->count() }} of {{ number_format($totalUsers) }} accounts</span></div>
                    @endif
                </section>

                <footer class="dashboard-footer"><span>© {{ now()->year }} TATA Pravesh</span><span>Administrative workspace <span class="footer-separator">·</span> Secure session</span></footer>
            </main>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-4.0.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/admin.js') }}" defer></script>
</body>
</html>
