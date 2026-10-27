@extends('admin.layout')

@section('title', 'Products')

@section('content')
    <section class="page-heading">
        <div>
            <div class="page-kicker"><span class="live-dot"></span> PRODUCT CATALOG</div>
            <h1>Products<span>.</span></h1>
            <p>Manage every product, design, shade, and size in one place.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.products.import.index') }}" class="btn btn-outline-primary">Import Spreadsheet</a>
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">Add Product</a>
        </div>
    </section>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm role-alert">{{ session('success') }}</div>
    @endif

    <div class="panel-card p-4 mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="product-search">Search</label>
                <input id="product-search" type="text" class="form-control" placeholder="Product name, category, model">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="category-filter">Category</label>
                <select id="category-filter" class="form-select">
                    <option value="">All categories</option>
                    @foreach ($products->pluck('category')->filter()->unique() as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status-filter">Status</label>
                <select id="status-filter" class="form-select">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <div class="panel-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Model</th>
                        <th>Designs</th>
                        <th>Shades</th>
                        <th>Starting Price</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    @if ($product->main_image)
                                        <img src="{{ asset('storage/'.$product->main_image) }}" alt="{{ $product->name }}" class="rounded" style="width:48px;height:48px;object-fit:cover;">
                                    @else
                                        <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:48px;height:48px;">P</div>
                                    @endif
                                    <div>
                                        <div class="fw-semibold">{{ $product->name }}</div>
                                        <small class="text-muted">{{ $product->slug }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $product->category ?? '—' }}</td>
                            <td>{{ $product->model ?? '—' }}</td>
                            <td>{{ $product->designs()->count() }}</td>
                            <td>{{ $product->designs()->withCount('shades')->get()->sum('shades_count') }}</td>
                            <td>{{ $product->starting_price ? '₹'.number_format($product->starting_price, 2) : '—' }}</td>
                            <td>
                                @if ($product->is_active)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>{{ $product->created_at->format('d M Y') }}</td>
                            <td>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <a href="{{ route('api.products.show', $product->slug) }}" target="_blank" class="btn btn-sm btn-outline-secondary">View</a>
                                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST" onsubmit="return confirm('Delete this product and all its related designs, shades, galleries, and sizes?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">No products found yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $products->links() }}</div>
    </div>
@endsection
