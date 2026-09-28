@extends('backend.master')

@section('title', __('Home'))

@section('content')
    <h1>{{ readConfig('site_name') }}</h1>
@endsection
