@extends('layouts.guest')

@section('content')
<!-- Container using flexbox to center both logo and form -->
<div class="auth-wrapper" style="min-height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 1rem;">
    <!-- Logo Container -->
    <div class="logo-container" style="margin-bottom: 1rem;">
        <img src="{{ asset('img/moon_logo.png') }}" alt="Logo" style="height: 100px;">
    </div>

    <!-- Registration Card -->
    <div class="login-card">
        <h2>{{ __('Register') }}</h2>
        <form method="POST" action="{{ route('register') }}">
            @csrf

            <!-- Name Field -->
            <div class="form-group">
                <label for="name">{{ __('Username') }}</label>
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

            <!-- Referral Code Field -->
            <div class="form-group">
                <label for="referral_code">{{ __('Referral Code') }}</label>
                <input id="referral_code" type="text" name="referral_code" value="{{ old('referral_code', request('ref')) }}" required>
                @error('referral_code')
                    <div class="error-msg">{{ $message }}</div>
                @enderror
            </div>

            <!-- Promotion Code Field (Optional) -->
            <div class="form-group">
                <label for="promotion_code">{{ __('Promotion Code (Optional)') }}</label>
                <input id="promotion_code" type="text" name="promotion_code" value="{{ old('promotion_code') }}">
                <small id="promotionInfo" style="display: none; color: red; margin-top:8px; margin-left:5px"></small>
                @error('promotion_code')
                    <div class="error-msg">{{ $message }}</div>
                @enderror
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-primary">
                {{ __('Register') }}
            </button>
        </form>

        <!-- Link to Login -->
        <div class="switch-auth" style="text-align:center; margin-top: 1rem; font-size: small;">
            <span>{{ __('Already have an account?') }}</span>
            <a href="{{ route('login') }}" style="color: #4c4cff; text-decoration: none; font-weight: 600;">
                {{ __('Back to Login') }}
            </a>
        </div>
    </div>
</div>

<!-- Additional Scripts and Modals -->

<script>
    console.log("Script loaded");
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var promotionInput = document.getElementById('promotion_code');
        var promotionInfo = document.getElementById('promotionInfo');
    
        // Debounce the input to avoid too many API calls
        let debounceTimer;
        promotionInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            var code = this.value.trim();
            if (code.length === 0) {
                promotionInfo.style.display = 'none';
                return;
            }
            debounceTimer = setTimeout(function() {
                // Fetch promotion info from API
                fetch('/api/promotion-info?code=' + encodeURIComponent(code))
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Promotion code not found');
                        }
                        return response.json();
                    })
                    .then(data => {
                        promotionInfo.textContent = " Code left: " + data.left;
                        promotionInfo.style.display = 'block';
                    })
                    .catch(error => {
                        promotionInfo.textContent = error.message;
                        promotionInfo.style.display = 'block';
                    });
            }, 500); // wait 500ms after the user stops typing
        });
    });
</script>

@if($errors->has('referral_code'))
<div class="modal fade" id="referralModal" tabindex="-1" aria-labelledby="referralModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="referralModalLabel">Referral Code Error</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        The referral code provided is either incorrect or missing. Please contact <a href="mailto:support@moonexe.con">support@moonexe.con</a> to obtain a valid referral code.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
@endif

@endsection

@section('scripts')
@if($errors->has('referral_code'))
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var referralModal = new bootstrap.Modal(document.getElementById('referralModal'));
        referralModal.show();
    });
</script>
@endif
@endsection
