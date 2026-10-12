<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Role management · TATA Pravesh Admin</title>
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
                <a class="sidebar-link is-active" href="{{ route('admin.roles.index') }}">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3.5 4.5 7v5.6c0 3.8 2.6 6.9 7.5 9.9 4.9-3 7.5-6.1 7.5-9.9V7L12 3.5Zm-1.5 7.8 2.1 2.1 4.2-4.2"></path></svg>
                    <span>Role management</span>
                </a>
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
                <div class="breadcrumb-label"><span>Workspace</span><span class="breadcrumb-slash">/</span><strong>Role management</strong></div>
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
                <section class="page-heading">
                    <div>
                        <div class="page-kicker"><span class="live-dot"></span> ACCESS CONTROL</div>
                        <h1>Role management<span>.</span></h1>
                        <p>Manage permissions, assign roles, and keep workspace access secure.</p>
                    </div>
                    <a href="#create-role" class="btn btn-primary btn-cta">Create role</a>
                </section>

                @if (session('success'))
                    <div class="alert alert-success border-0 shadow-sm role-alert">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger border-0 shadow-sm role-alert">{{ session('error') }}</div>
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

                <section class="role-metric-grid" aria-label="Role management metrics">
                    <article class="metric-card">
                        <div class="metric-top"><span class="metric-label">Total roles</span><span class="metric-icon metric-icon-blue"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 7h10v10H8zm2 0V5m8 2V5M5 12h3"></path></svg></span></div>
                        <div class="metric-value">{{ $roles->count() }}</div>
                        <div class="metric-foot"><span class="metric-foot-dot dot-blue"></span>Defined in the workspace</div>
                    </article>
                    <article class="metric-card">
                        <div class="metric-top"><span class="metric-label">Permissions</span><span class="metric-icon metric-icon-violet"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3.5 4.5 7v5.6c0 3.8 2.6 6.9 7.5 9.9 4.9-3 7.5-6.1 7.5-9.9V7L12 3.5Zm-1.5 7.8 2.1 2.1 4.2-4.2"></path></svg></span></div>
                        <div class="metric-value">{{ $permissions->count() }}</div>
                        <div class="metric-foot"><span class="metric-foot-dot dot-violet"></span>Granular access points</div>
                    </article>
                    <article class="metric-card">
                        <div class="metric-top"><span class="metric-label">Assigned users</span><span class="metric-icon metric-icon-teal"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5"></circle><path d="M2.8 20c.4-3.3 2.5-5 6.2-5s5.8 1.7 6.2 5M17 5.5a3.5 3.5 0 0 1 0 6.8m.8 2.8c2.2.6 3.4 2.1 3.5 4.9"></path></svg></span></div>
                        <div class="metric-value">{{ $users->total() }}</div>
                        <div class="metric-foot"><span class="metric-foot-dot dot-teal"></span>Users with access assignments</div>
                    </article>
                </section>

                <section class="role-grid">
                    <div class="role-form-card panel-card" id="create-role">
                        <div class="panel-heading">
                            <div><span class="panel-eyebrow">NEW ROLE</span><h2>Create Role</h2></div>
                            <span class="security-status">RBAC</span>
                        </div>

                        <form action="{{ route('admin.roles.store') }}" method="POST" class="role-form">
                            @csrf
                            <div class="form-field">
                                <label for="role-name" class="form-label">Role name</label>
                                <input id="role-name" name="name" type="text" class="form-control form-input" placeholder="Enter a role name" value="{{ old('name') }}" required>
                            </div>

                            <div class="form-field">
                                <label class="form-label">Permissions</label>
                                <div class="permission-list">
                                    @foreach ($permissions as $permission)
                                        <label class="permission-chip">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" {{ in_array($permission->name, old('permissions', []), true) ? 'checked' : '' }}>
                                            <span>{{ $permission->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 btn-cta-role">Save role</button>
                        </form>
                    </div>

                    <div class="role-list panel-card">
                        <div class="panel-heading">
                            <div><span class="panel-eyebrow">ROLE CATALOG</span><h2>Available roles</h2></div>
                            <span class="period-chip"><span class="period-dot"></span>{{ $roles->count() }} active</span>
                        </div>

                        <div class="role-stack">
                            @forelse ($roles as $role)
                                <div class="role-item">
                                    <div class="role-item-head">
                                        <div>
                                            <span class="role-badge {{ $role->name === 'super-admin' ? 'role-super' : ($role->name === 'admin' ? 'role-admin' : 'role-custom') }}">{{ $role->name }}</span>
                                            <p>{{ $role->permissions->count() }} permissions</p>
                                        </div>
                                        @if (! in_array($role->name, ['super-admin', 'admin'], true))
                                            <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Delete this role?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        @endif
                                    </div>

                                    <form action="{{ route('admin.roles.update', $role) }}" method="POST" class="role-form-inline">
                                        @csrf
                                        @method('PUT')

                                        <div class="form-field compact-field">
                                            <label for="role-name-{{ $role->id }}" class="form-label">Role name</label>
                                            <input id="role-name-{{ $role->id }}" name="name" type="text" class="form-control form-input" value="{{ old('name', $role->name) }}" required>
                                        </div>

                                        <div class="form-field compact-field">
                                            <label class="form-label">Permissions</label>
                                            <div class="permission-list">
                                                @foreach ($permissions as $permission)
                                                    <label class="permission-chip permission-chip-small">
                                                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}>
                                                        <span>{{ $permission->name }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary btn-update-role">Update role</button>
                                    </form>
                                </div>
                            @empty
                                <div class="role-empty">
                                    <span class="empty-directory-icon">+</span>
                                    <strong>No roles created yet</strong>
                                    <span>Create your first access role to begin managing permissions.</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="panel-card role-assignment-panel">
                    <div class="directory-heading">
                        <div><span class="panel-eyebrow">USER ACCESS</span><h2>Role assignment matrix</h2><p>Assign and review access for each workspace member.</p></div>
                        <div class="directory-tools">
                            <span class="directory-total">{{ $users->total() }} users</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table directory-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">USER</th>
                                    <th scope="col">EMAIL</th>
                                    <th scope="col">CURRENT ROLES</th>
                                    <th scope="col">UPDATE ROLES</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $user)
                                    <tr>
                                        <td>
                                            <div class="account-cell">
                                                <span class="account-avatar {{ $user->isAdmin() ? 'avatar-admin' : '' }}">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                                <span class="account-copy"><strong>{{ $user->name }}</strong><small>{{ $user->isAdmin() ? 'Administrator' : 'Member' }}</small></span>
                                            </div>
                                        </td>
                                        <td class="email-cell">{{ $user->email }}</td>
                                        <td>
                                            @forelse ($user->roles as $role)
                                                <span class="role-pill role-admin"><span></span>{{ $role->name }}</span>
                                            @empty
                                                <span class="text-muted">No role assigned</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            <form action="{{ route('admin.users.roles.update', $user) }}" method="POST" class="d-flex flex-wrap gap-2 align-items-center assignment-form">
                                                @csrf
                                                <select name="roles[]" class="form-select form-select-sm role-select" multiple size="4" aria-label="User roles">
                                                    @foreach ($roles as $role)
                                                        <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>{{ $role->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-outline-primary btn-sm">Save</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4"><div class="empty-directory"><span class="empty-directory-icon">+</span><strong>No users found</strong><span>New workspace accounts will appear here.</span></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($users->hasPages())
                        <div class="directory-pagination">
                            <span>Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ number_format($users->total()) }} users</span>
                            {{ $users->onEachSide(1)->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </section>

                <footer class="dashboard-footer"><span>© {{ now()->year }} TATA Pravesh</span><span>Administrative workspace <span class="footer-separator">·</span> Secure access</span></footer>
            </main>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-4.0.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
