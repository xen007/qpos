@extends('frontend.authentication.layout')
@section('title', __('Password Reset'))
@section('description', __('Please enter the code we emailed you.'))
@section('back')
    <a href="{{ route('login') }}"><span aria-hidden="true">←</span> {{ __('Back to login') }}</a>
@endsection
@section('content')
    <form action="{{ route('password.reset') }}" method="post" class="qpos-auth-form">
        @csrf
        <fieldset class="qpos-otp-fieldset">
            <legend>{{ __('Verification code') }}</legend>
            <div class="qpos-otp">
                @for ($digit = 1; $digit <= 5; $digit++)
                    <input type="text" name="number_{{ $digit }}" maxlength="1" inputmode="numeric" pattern="[0-9]" required
                        autocomplete="{{ $digit === 1 ? 'one-time-code' : 'off' }}" data-otp-digit
                        aria-label="{{ __('Code digit :number', ['number' => $digit]) }}"
                        @if ($errors->has('number_' . $digit)) aria-invalid="true" @endif>
                @endfor
            </div>
        </fieldset>
        <button type="submit" class="qpos-auth-primary">{{ __('Continue') }} <span aria-hidden="true">→</span></button>
    </form>
    <form action="{{ route('resend.otp') }}" method="post" class="qpos-auth-resend">
        @csrf
        <button type="submit" class="qpos-auth-secondary">{{ __('Resend code') }}</button>
    </form>
@endsection
