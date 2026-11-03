@extends('admin.layout')

@section('title', $isEdit ? 'Edit Dealer' : 'Add Dealer')

@section('content')
    <section class="page-heading">
        <div>
            <div class="page-kicker"><span class="live-dot"></span> DEALER MANAGEMENT</div>
            <h1>{{ $isEdit ? 'Edit dealer' : 'Add dealer' }}<span>.</span></h1>
            <p>Update dealer contact details and profile image.</p>
        </div>
    </section>

    <div class="panel-card p-4">
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Dealer was not saved. Please correct these issues:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ $isEdit ? route('admin.dealers.update', $dealer) : route('admin.dealers.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="row g-3">
                @foreach ([
                    'state' => ['State', 'text'],
                    'district' => ['District', 'text'],
                    'dealer_name' => ['Dealer name', 'text'],
                    'dealer_address' => ['Dealer address', 'textarea'],
                    'contact_person' => ['Contact person', 'text'],
                    'mobile_number' => ['Mobile number', 'text'],
                    'email_id' => ['Email', 'email'],
                ] as $field => [$label, $type])
                    <div class="{{ $field === 'dealer_address' ? 'col-12' : 'col-md-6' }}">
                        <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                        @if ($type === 'textarea')
                            <textarea id="{{ $field }}" name="{{ $field }}" class="form-control" rows="3">{{ old($field, $dealer->{$field}) }}</textarea>
                        @else
                            <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" class="form-control" value="{{ old($field, $dealer->{$field}) }}" {{ $field === 'dealer_name' ? 'required' : '' }}>
                        @endif
                    </div>
                @endforeach
                <div class="col-md-6">
                    <label class="form-label" for="image">Dealer image</label>
                    @if ($dealer->image_url)
                        <div class="mb-2"><img src="{{ $dealer->image_url }}" alt="{{ $dealer->dealer_name }}" class="rounded" style="max-width:180px;max-height:140px;object-fit:cover;"></div>
                    @endif
                    <input id="image" type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Save changes' : 'Create dealer' }}</button>
                <a href="{{ route('admin.dealers.index') }}" class="btn btn-outline-secondary">Back to dealers</a>
            </div>
        </form>
    </div>
@endsection
