@extends('layouts.guest')

@section('content')
<div class="auth-wrapper" style="min-height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 1rem;">
    <!-- Logo -->
    <div class="logo-container" style="margin-bottom: 1rem;">
        <img src="{{ asset('img/moon_logo.png') }}" alt="Logo" style="height: 100px;">
    </div>

    <!-- Reset Password Card -->
    <div class="login-card">
        <h2>{{ __('Reset Your Password') }}</h2>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group">
                <label for="email">{{ __('Email Address') }}</label>
                <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}" required autofocus>
                @error('email')
                    <div class="error-msg">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">{{ __('New Password') }}</label>
                <input id="password" type="password" name="password" required autocomplete="new-password">
                @error('password')
                    <div class="error-msg">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password-confirm">{{ __('Confirm New Password') }}</label>
                <input id="password-confirm" type="password" name="password_confirmation" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn-primary">
                {{ __('Reset Password') }}
            </button>

            <div class="forgot-link">
                <a href="{{ route('login') }}">{{ __('Back to Login') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
