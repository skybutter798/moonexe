@extends('layouts.guest')

@section('content')
<div class="auth-wrapper" style="min-height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 1rem;">
    <!-- Logo -->
    <div class="logo-container" style="margin-bottom: 1rem;">
        <img src="{{ asset('img/main_logo.png') }}" alt="Logo" style="height: 40px;">
    </div>

    <!-- Forgot Password Card -->
    <div class="login-card">
        <h2>{{ __('Forgot Password') }}</h2>

        @if (session('status'))
            <div class="alert alert-success" style="margin-bottom: 1rem;">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="form-group">
                <label for="email">{{ __('Email Address') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <div class="error-msg">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn-primary">
                {{ __('Send Password Reset Link') }}
            </button>

            <!-- Optional: Go back to login -->
            <div class="forgot-link">
                <a href="{{ route('login') }}">{{ __('Back to Login') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
