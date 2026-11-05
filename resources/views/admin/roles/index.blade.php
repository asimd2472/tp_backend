@extends('admin.layout')

@section('title', 'Role management')

@section('content_class', 'role-page-content')

@section('content')

<section class="page-heading">
                    <div>
                        <div class="page-kicker"><span class="live-dot"></span> ACCESS CONTROL</div>
                        <h1>Role management<span>.</span></h1>
                        <p>Manage permissions, assign roles, and keep workspace access secure.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @can('manage_users')
                            <a href="#create-user" class="btn btn-outline-primary btn-cta">Create user</a>
                        @endcan
                        <a href="#create-role" class="btn btn-primary btn-cta">Create role</a>
                    </div>
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

                <section class="panel-card role-assignment-panel" id="user-access">
                    @can('manage_users')
                        <div class="panel-heading" id="create-user">
                            <div><span class="panel-eyebrow">NEW USER</span><h2>Create user</h2></div>
                        </div>
                        <form action="{{ route('admin.users.store') }}" method="POST" class="role-form mb-4">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6 form-field">
                                    <label for="new-user-name" class="form-label">Name</label>
                                    <input id="new-user-name" name="user_name" type="text" class="form-control form-input" value="{{ old('user_name') }}" required>
                                </div>
                                <div class="col-md-6 form-field">
                                    <label for="new-user-email" class="form-label">Email</label>
                                    <input id="new-user-email" name="user_email" type="email" class="form-control form-input" value="{{ old('user_email') }}" required>
                                </div>
                                <div class="col-md-6 form-field">
                                    <label for="new-user-phone" class="form-label">Phone</label>
                                    <input id="new-user-phone" name="user_phone" type="text" class="form-control form-input" value="{{ old('user_phone') }}">
                                </div>
                                <div class="col-md-6 form-field">
                                    <label for="new-user-password" class="form-label">Password</label>
                                    <input id="new-user-password" name="user_password" type="password" class="form-control form-input" minlength="8" autocomplete="new-password" required>
                                </div>
                                <div class="col-md-6 form-field">
                                    <label for="new-user-password-confirmation" class="form-label">Confirm password</label>
                                    <input id="new-user-password-confirmation" name="user_password_confirmation" type="password" class="form-control form-input" minlength="8" autocomplete="new-password" required>
                                </div>
                                <div class="col-12 form-field">
                                    <label for="new-user-roles" class="form-label">Assign roles (optional)</label>
                                    <select id="new-user-roles" name="user_roles[]" class="form-select" multiple size="4">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->name }}" {{ in_array($role->name, old('user_roles', []), true) ? 'selected' : '' }}>{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Use Ctrl (Windows) or Command (Mac) to select multiple roles.</small>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">Create user</button>
                        </form>
                    @endcan

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
                                    <th scope="col">PHONE</th>
                                    <th scope="col">CURRENT ROLES</th>
                                    @can('manage_users')
                                        <th scope="col">UPDATE ROLES</th>
                                        <th scope="col">ACTIONS</th>
                                    @endcan
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
                                        <td>{{ $user->phone ?: '—' }}</td>
                                        <td>
                                            @forelse ($user->roles as $role)
                                                <span class="role-pill role-admin"><span></span>{{ $role->name }}</span>
                                            @empty
                                                <span class="text-muted">No role assigned</span>
                                            @endforelse
                                        </td>
                                        @can('manage_users')
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
                                            <td>
                                                <details>
                                                    <summary class="btn btn-outline-secondary btn-sm">Edit user</summary>
                                                    <form action="{{ route('admin.users.update', $user) }}" method="POST" class="role-form-inline mt-2">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="form-field compact-field">
                                                            <label for="user-name-{{ $user->id }}" class="form-label">Name</label>
                                                            <input id="user-name-{{ $user->id }}" name="name" type="text" class="form-control form-input" value="{{ $user->name }}" required>
                                                        </div>
                                                        <div class="form-field compact-field">
                                                            <label for="user-email-{{ $user->id }}" class="form-label">Email</label>
                                                            <input id="user-email-{{ $user->id }}" name="email" type="email" class="form-control form-input" value="{{ $user->email }}" required>
                                                        </div>
                                                        <div class="form-field compact-field">
                                                            <label for="user-phone-{{ $user->id }}" class="form-label">Phone</label>
                                                            <input id="user-phone-{{ $user->id }}" name="phone" type="text" class="form-control form-input" value="{{ $user->phone }}">
                                                        </div>
                                                        <div class="form-field compact-field">
                                                            <label for="user-password-{{ $user->id }}" class="form-label">New password (optional)</label>
                                                            <input id="user-password-{{ $user->id }}" name="password" type="password" class="form-control form-input" minlength="8" autocomplete="new-password">
                                                        </div>
                                                        <div class="form-field compact-field">
                                                            <label for="user-password-confirmation-{{ $user->id }}" class="form-label">Confirm new password</label>
                                                            <input id="user-password-confirmation-{{ $user->id }}" name="password_confirmation" type="password" class="form-control form-input" minlength="8" autocomplete="new-password">
                                                        </div>
                                                        <button type="submit" class="btn btn-primary btn-sm">Save user</button>
                                                    </form>
                                                </details>
                                                @unless (auth()->id() === $user->id)
                                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="mt-2" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete user</button>
                                                    </form>
                                                @endunless
                                            </td>
                                        @endcan
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ auth()->user()->can('manage_users') ? 6 : 4 }}"><div class="empty-directory"><span class="empty-directory-icon">+</span><strong>No users found</strong><span>New workspace accounts will appear here.</span></div></td></tr>
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

@endsection
