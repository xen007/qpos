@extends('backend.master-tailwind')
@section('title', request()->filled('purchase_id') ? __('Edit Purchase') : __('Purchase Create'))
@section('content')
@include('backend.replenishment.quick-product')
    <div id="purchase"><x-backend.state variant="loading" :title="__('Loading workspace')" /></div>
@endsection
