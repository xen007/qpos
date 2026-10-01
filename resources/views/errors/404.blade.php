@extends('frontend.authentication.layout')
@section('title', __('Page not found'))
@section('description', __('This page is unavailable or has moved.'))
@section('content')
    <div class="qpos-error-code" aria-hidden="true">404</div>
    <a class="qpos-auth-primary" href="{{ route('frontend.home') }}">{{ __('Back to home') }} <span aria-hidden="true">→</span></a>
@endsection
