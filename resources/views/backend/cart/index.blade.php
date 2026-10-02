@extends('backend.master-tailwind')
@section('title', __('POS'))
@section('content')
    <div id="cart"><x-backend.state variant="loading" :title="__('Loading workspace')" /></div>
@endsection
