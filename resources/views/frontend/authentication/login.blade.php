@extends('frontend.authentication.layout')
@section('title', __('Sign in'))
@section('description', __('Welcome back! Sign in to access your account.'))
@section('content')
    <form action="{{ route('login') }}" method="post" class="qpos-auth-form">
        @csrf
        <x-frontend.auth-field name="email" type="email" :label="__('Email')" autocomplete="username" />
        <x-frontend.auth-field name="password" type="password" :label="__('Password')" autocomplete="current-password" />
        <div class="qpos-auth-options">
            <label class="qpos-auth-checkbox"><input type="checkbox" name="remember_me" value="1" @checked(old('remember_me'))> {{ __('Remember me') }}</label>
            <a href="{{ route('forget.password') }}">{{ __('Forgot password') }}</a>
        </div>
        <button type="submit" class="qpos-auth-primary">{{ __('Sign In') }} <span aria-hidden="true">→</span></button>
    </form>
@endsection
