@extends('admin.layout')

@section('title', 'Overview')

@section('content_class', '')

@section('content_id', 'overview')

@section('content')

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

@endsection
