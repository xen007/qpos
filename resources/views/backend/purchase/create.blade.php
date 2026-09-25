@extends('backend.master')

@section('title', request()->filled('purchase_id') ? __('Edit Purchase') : __('Purchase Create'))

@section('content')
<div id="purchase"></div>
@endsection
@push('style')
<style>
  .react-datepicker-wrapper {
    width: 100%;
    box-sizing: border-box;
  }
</style>
@endpush
@push('script')
<script>
</script>
@endpush
