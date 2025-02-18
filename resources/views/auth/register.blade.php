@extends('layouts.guest')

@section('content')
<div class="login-container">
    <div class="login-card">
        <h2>{{ __('Register') }}</h2>
        <form method="POST" action="{{ route('register') }}">
            @csrf

            <!-- Name Field -->
            <div class="form-group">
                <label for="name">{{ __('Name') }}</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus>
                @error('name')
                    <div class="error-msg">{{ $message }}</div>
                @enderror
            </div>

            <!-- Email Field -->
            <div class="form-group">
                <label for="email">{{ __('Email Address') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required>
                @error('email')
                    <div class="error-msg">{{ $message }}</div>
                @enderror
            </div>

            <!-- Password Field -->
            <div class="form-group">
                <label for="password">{{ __('Password') }}</label>
                <input id="password" type="password" name="password" required>
                @error('password')
                    <div class="error-msg">{{ $message }}</div>
                @enderror
            </div>

            <!-- Confirm Password Field -->
            <div class="form-group">
                <label for="password_confirmation">{{ __('Confirm Password') }}</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required>
            </div>

            <!-- Referral Code Field (Disabled) -->
            <div class="form-group">
                <label for="referral_code">{{ __('Referral Code') }}</label>
                <input id="referral_code" type="text" name="referral_code" value="{{ old('referral_code') }}" disabled>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-primary">
                {{ __('Register') }}
            </button>
        </form>

        <!-- Modern link to login -->
        <div class="switch-auth" style="text-align:center; margin-top: 1rem;font-size: small;">
            <span>{{ __('Already have an account?') }}</span>
            <a href="{{ route('login') }}" style="color: #4c4cff; text-decoration: none; font-weight: 600;">
                {{ __('Back to Login') }}
            </a>
        </div>
    </div>
</div>
@endsection
