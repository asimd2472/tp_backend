@extends('admin.layout')

@section('title', 'Import Windows')

@section('content')
    <section class="page-heading">
        <div>
            <div class="page-kicker"><span class="live-dot"></span> WINDOW IMPORT</div>
            <h1>Import window spreadsheet<span>.</span></h1>
            <p>Upload an ODS, XLSX, or CSV file with Product Name, Models, Shades, and Sizes columns.</p>
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
            <p class="mb-0 text-secondary">Use the window sample workbook as a reference format.</p>
            <a href="{{ asset('excel/window.xlsx') }}" class="btn btn-outline-primary" download>Download window sample</a>
        </div>

        <form action="{{ route('admin.windows.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="spreadsheet" class="form-label">Spreadsheet file</label>
                <input id="spreadsheet" type="file" name="spreadsheet" class="form-control" accept=".ods,.xlsx,.csv" required>
            </div>
            <button type="submit" class="btn btn-primary btn-cta">Import windows</button>
            <a href="{{ route('admin.windows.index') }}" class="btn btn-outline-secondary">Back to windows</a>
        </form>
    </div>
@endsection
