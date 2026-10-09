@extends('backend.master-tailwind')
@section('title', __('POS'))
@section('content')
    <script>window.qposCanManageCash = @json(auth()->user()->can('cash_session_manage'));</script>
    <div id="cart"><x-backend.state variant="loading" :title="__('Loading workspace')" /></div>
@endsection
