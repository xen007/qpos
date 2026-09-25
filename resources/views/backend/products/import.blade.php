@extends('backend.master')

@section('title', __('Product Import'))

@section('content')
<div class="card">
  <div class="card-body">
    <form action="{{ route('backend.admin.products.import') }}" method="post" class="accountForm"
      enctype="multipart/form-data">
      @csrf
      <div class="card-body">
        <div class="row">
          <div class="mb-3 col-md-6">
            <div class="form-group">
              <label for="exampleInputFile">{{ __('File input') }}</label>
              <div class="input-group">
                <div class="custom-file">
                  <input type="file" class="custom-file-input" name="file" id="exampleInputFile" required>
                  <label class="custom-file-label" for="exampleInputFile">{{ __('Choose file') }}</label>
                </div>
                <div class="input-group-append">
                  <a class="input-group-text" href="{{ route('backend.admin.products.import',['download-demo' => true]) }}"><i class="fas fa-download"></i> {{ __('Download sample') }}</a>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="mb-3 col-md-6">
            <button type="submit" class="btn btn-block bg-gradient-primary">{{ __('Import') }}</button>
            <!-- /.card-body -->
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@push('style')
<style>
  .select2-container--default .select2-selection--single {
    height: calc(1.5em + 0.75rem + 2px) !important;
  }
</style>

@endpush
@push('script')
<script src="{{ asset('js/image-field.js') }}"></script>
@endpush
