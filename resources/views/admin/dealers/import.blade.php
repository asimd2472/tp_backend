@extends('admin.layout')

@section('title', 'Import Dealers')

@section('content')
    <section class="page-heading">
        <div>
            <div class="page-kicker"><span class="live-dot"></span> DEALER IMPORT</div>
            <h1>Import dealers<span>.</span></h1>
            <p>Upload a CSV, XLSX, or ODS file with the sample dealer columns.</p>
        </div>
    </section>

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm role-alert">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="panel-card p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <p class="mb-0 text-secondary">Required columns: State, District, Dealer Name, Dealer Address, Contact Person, Mobile Number, Email ID.</p>
            <a href="{{ asset('excel/dealer.csv') }}" class="btn btn-outline-primary" download>Download sample CSV</a>
        </div>

        <form action="{{ route('admin.dealers.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="spreadsheet" class="form-label">Dealer spreadsheet</label>
                <input id="spreadsheet" type="file" name="spreadsheet" class="form-control" accept=".csv,.txt,.xlsx,.ods" required>
            </div>
            <button type="submit" class="btn btn-primary">Import dealers</button>
            <a href="{{ route('admin.dealers.index') }}" class="btn btn-outline-secondary">Back to dealers</a>
        </form>
    </div>
@endsection
