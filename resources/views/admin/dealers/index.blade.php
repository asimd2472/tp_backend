@extends('admin.layout')

@section('title', 'Dealers')

@section('content')
    <section class="page-heading">
        <div>
            <div class="page-kicker"><span class="live-dot"></span> DEALER DIRECTORY</div>
            <h1>Dealers<span>.</span></h1>
            <p>Manage dealer contact details and profile images.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.dealers.import.index') }}" class="btn btn-outline-primary">Import Spreadsheet</a>
            <a href="{{ asset('excel/dealer.csv') }}" class="btn btn-outline-secondary" download>Download Sample CSV</a>
            <a href="{{ route('admin.dealers.create') }}" class="btn btn-primary">Add Dealer</a>
        </div>
    </section>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm role-alert">{{ session('success') }}</div>
    @endif

    <form action="{{ route('admin.dealers.index') }}" method="GET" class="panel-card p-4 mb-4">
        <div class="row g-3 align-items-end">
            @foreach ([
                'state' => 'State',
                'district' => 'District',
                'dealer_name' => 'Dealer name',
                'dealer_address' => 'Dealer address',
                'contact_person' => 'Contact person',
                'mobile_number' => 'Mobile number',
                'email_id' => 'Email',
            ] as $field => $label)
                <div class="col-md-4 col-lg-3">
                    <label class="form-label" for="filter-{{ $field }}">{{ $label }}</label>
                    <input id="filter-{{ $field }}" name="{{ $field }}" type="search" class="form-control" value="{{ $filters[$field] ?? '' }}">
                </div>
            @endforeach
            <div class="col-md-4 col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="{{ route('admin.dealers.index') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </div>
    </form>

    <div class="panel-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Dealer</th>
                        <th>State</th>
                        <th>District</th>
                        <th>Address</th>
                        <th>Contact person</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dealers as $dealer)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    @if ($dealer->image_url)
                                        <img src="{{ $dealer->image_url }}" alt="{{ $dealer->dealer_name }}" class="rounded" style="width:48px;height:48px;object-fit:cover;">
                                    @else
                                        <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:48px;height:48px;">D</div>
                                    @endif
                                    <span class="fw-semibold">{{ $dealer->dealer_name }}</span>
                                </div>
                            </td>
                            <td>{{ $dealer->state ?? '—' }}</td>
                            <td>{{ $dealer->district ?? '—' }}</td>
                            <td>{{ $dealer->dealer_address ?? '—' }}</td>
                            <td>{{ $dealer->contact_person ?? '—' }}</td>
                            <td>{{ $dealer->mobile_number ?? '—' }}</td>
                            <td>{{ $dealer->email_id ?? '—' }}</td>
                            <td>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.dealers.edit', $dealer) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('admin.dealers.destroy', $dealer) }}" method="POST" onsubmit="return confirm('Delete this dealer?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">No dealers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $dealers->links() }}</div>
    </div>
@endsection
