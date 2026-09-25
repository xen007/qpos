@extends('backend.master')

@section('title', __('Create Supplier'))

@section('content')
<div class="card">
  <div class="card-body">
    <form action="{{ route('backend.admin.suppliers.store') }}" method="post" class="accountForm"
      enctype="multipart/form-data">
      @csrf
      <div class="card-body">
        <div class="row">
          <div class="mb-3 col-md-6">
            <label for="title" class="form-label">
              {{ __('Name') }}
              <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" placeholder="{{ __('Enter title') }}" name="name"
              value="{{ old('name') }}" required>
          </div>
          <div class="mb-3 col-md-6">
            <label for="title" class="form-label">
              {{ __('Phone') }}
              <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" placeholder="{{ __('Enter phone') }}" name="phone"
              value="{{ old('phone') }}" required>
          </div>
          <div class="mb-3 col-md-6">
            <label for="title" class="form-label">
              {{ __('Address') }}
            </label>
            <input type="text" class="form-control" placeholder="{{ __('Enter Address') }}" name="address"
              value="{{ old('Address') }}">
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <button type="submit" class="btn btn-md bg-gradient-primary">{{ __('Create') }}</button>
          </div>
        </div>
      </div>
      <!-- /.card-body -->
    </form>
  </div>
</div>
@endsection
@push('script')
<script>
</script>
@endpush
