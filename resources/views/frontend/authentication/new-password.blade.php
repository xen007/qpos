@extends('frontend.authentication.layout')
@section('title', __('Reset Password'))
@section('description', __('Please enter your new password.'))
@section('back')
    <a href="{{ route('login') }}"><span aria-hidden="true">←</span> {{ __('Back to login') }}</a>
@endsection
@section('content')
    <form action="{{ route('new.password') }}" method="post" class="qpos-auth-form" data-password-confirmation="{{ __('Passwords do not match.') }}">
        @csrf
        <x-frontend.auth-field name="password" type="password" :label="__('Password')" autocomplete="new-password" minlength="6" />
        <x-frontend.auth-field name="password_confirmation" type="password" :label="__('Confirm password')" autocomplete="new-password" minlength="6" />
        <button type="submit" class="qpos-auth-primary">{{ __('Reset Password') }} <span aria-hidden="true">→</span></button>
    </form>
@endsection
