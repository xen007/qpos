@extends('frontend.authentication.layout')
@section('title', __('Forgot Password?'))
@section('description', __('Enter the email address you use to sign in.'))
@section('back')
    <a href="{{ route('login') }}"><span aria-hidden="true">←</span> {{ __('Back to login') }}</a>
@endsection
@section('content')
    <form action="{{ route('forget.password') }}" method="post" class="qpos-auth-form">
        @csrf
        <x-frontend.auth-field name="email" type="email" :label="__('Email')" autocomplete="email" />
        <button type="submit" class="qpos-auth-primary">{{ __('Request password reset') }} <span aria-hidden="true">→</span></button>
    </form>
@endsection
