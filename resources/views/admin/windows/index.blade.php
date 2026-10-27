@extends('admin.layout')

@section('title', 'Windows')

@section('content')
    <section class="page-heading">
        <div>
            <div class="page-kicker"><span class="live-dot"></span> WINDOW CATALOG</div>
            <h1>Windows<span>.</span></h1>
            <p>Manage window products, models, shades, gallery images, and sizes.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.windows.import.index') }}" class="btn btn-outline-primary">Import Spreadsheet</a>
            <a href="{{ route('admin.windows.create') }}" class="btn btn-primary">Add Window Product</a>
        </div>
    </section>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm role-alert">{{ session('success') }}</div>
    @endif

    <div class="panel-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Window product</th>
                        <th>Category</th>
                        <th>Models</th>
                        <th>Shades</th>
                        <th>Starting price</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($windows as $window)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    @if ($window->main_image)
                                        <img src="{{ asset('uploads/'.$window->main_image) }}" alt="{{ $window->name }}" class="rounded" style="width:48px;height:48px;object-fit:cover;">
                                    @else
                                        <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:48px;height:48px;">W</div>
                                    @endif
                                    <div>
                                        <div class="fw-semibold">{{ $window->name }}</div>
                                        <small class="text-muted">{{ $window->slug }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $window->category ?? '—' }}</td>
                            <td>{{ $window->models_count }}</td>
                            <td>{{ $window->models->sum(fn ($model) => $model->shades->count()) }}</td>
                            <td>{{ $window->starting_price ? '₹'.number_format($window->starting_price, 2) : '—' }}</td>
                            <td>
                                @if ($window->is_active)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.windows.edit', $window) }}" class="btn btn-sm btn-outline-primary">Manage</a>
                                    <form action="{{ route('admin.windows.destroy', $window) }}" method="POST" onsubmit="return confirm('Delete this window product and all its models, shades, images, and sizes?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No window products found yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $windows->links() }}</div>
    </div>
@endsection
