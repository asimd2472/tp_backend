@extends('admin.layout')

@section('title', 'Import Products')

@section('content')
    <section class="page-heading">
        <div>
            <div class="page-kicker"><span class="live-dot"></span> IMPORT</div>
            <h1>Import product spreadsheet<span>.</span></h1>
            <p>Upload an ODS, XLSX, or CSV file containing Products, Models, Designs, Shades, and Sizes.</p>
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
            <p class="mb-0 text-secondary">Use the sample spreadsheet below as a reference format.</p>
            <a href="{{ asset('excel/TP product.xlsx') }}" class="btn btn-outline-primary" download>Download sample spreadsheet</a>
        </div>

        <form action="{{ route('admin.products.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="spreadsheet" class="form-label">Spreadsheet file</label>
                <input id="spreadsheet" type="file" name="spreadsheet" class="form-control" accept=".ods,.xlsx,.csv" required>
            </div>
            <button type="submit" class="btn btn-primary btn-cta">Import</button>
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Back to products</a>
        </form>
    </div>
@endsection
