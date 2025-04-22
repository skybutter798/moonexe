@extends('layouts.guest')

@section('content')
<div class="auth-wrapper" style="min-height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 1rem;">
    <!-- Logo Container -->
    <div class="logo-container" style="margin-bottom: 1rem;">
        <img src="{{ asset('img/main_logo.png') }}" alt="Logo" style="height: 40px;">
    </div>

    <!-- Login Card -->
    <div class="login-card">
        <h2>{{ __('Login') }}</h2>
        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label for="login">{{ __('Email or Username') }}</label>
                <input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus>
                @error('login')
                    <div class="error-msg">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">{{ __('Password') }}</label>
                <input id="password" type="password" name="password" required>
                @error('password')
                    <div class="error-msg">{{ $message }}</div>
                @enderror
            </div>

            <div class="checkbox-container">
                <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <label for="remember">{{ __('Remember Me') }}</label>
            </div>

            <button type="submit" class="btn-primary">
                {{ __('Login') }}
            </button>

            @if (Route::has('password.request'))
                <div class="forgot-link">
                    <a href="{{ route('password.request') }}">{{ __('Forgot Your Password?') }}</a>
                </div>
            @endif
        </form>

        <!-- Modern link to registration -->
        <div class="switch-auth" style="text-align:center; margin-top: 1rem; font-size: small;">
            <span>{{ __("Don't have an account yet?") }}</span>
            <a href="{{ route('register') }}" style="color: #4c4cff; text-decoration: none; font-weight: 600;">
                {{ __('Click here to register') }}
            </a>
        </div>
    </div>
</div>
@endsection
