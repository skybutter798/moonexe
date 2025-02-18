@extends('layouts.users.app')

@section('title', 'Account')

@section('content')
<div class="container py-4">
  <!-- Account Header -->
  <div class="card mb-4">
    <div class="card-body d-flex align-items-center">
      <div class="me-3">
        <!-- Avatar placeholder -->
        <div class="rounded" style="width:80px; height:80px; background-color:#101012;"></div>
      </div>
      <div>
        <span class="badge bg-warning text-dark">Gold</span>
        <h3 class="mb-0">SKYBUTTER</h3>
        <p class="mb-0">UID: 0000001 • <span class="text-success">Verified</span></p>
      </div>
    </div>
  </div>

  <!-- Upgrade & Verification Levels -->
  <div class="row mb-4">
    <!-- Upgrade Box -->
    <div class="col-md-8">
      <div class="card mb-3">
        <div class="card-body">
          <p>
            Upgrade to <strong>xxx package</strong> to increase your trading limits to
            <strong>xxx USD Daily</strong>.
          </p>
          <p>
            Required:<br>
            - Proof of address
          </p>
          <button class="btn btn-success btn-sm">Get Package</button>
        </div>
      </div>
    </div>
    <!-- Verification Levels -->
    <div class="col-md-4">
      <div class="card mb-3">
        <div class="card-body">
          <h4 class="card-title mb-3">Verification Levels</h4>
          <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="verification" id="verified" checked>
            <label class="form-check-label" for="verified">
              <strong>Verified</strong> (Limit of 80K USD Daily)
            </label>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="verification" id="packageA">
            <label class="form-check-label" for="packageA">
              <strong>Package A</strong> (Limit of 200K USD Daily)
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="verification" id="packageB">
            <label class="form-check-label" for="packageB">
              <strong>Package B</strong> (Limit of 300K USD Daily)
            </label>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Account Limits -->
  <div class="card mb-4">
    <div class="card-body">
      <h4 class="card-title">Account Limits</h4>
      <ul class="list-unstyled mb-0">
        <li><strong>Deposit &amp; Withdrawal Limits:</strong> <span>80K USD Daily</span></li>
        <li><strong>Package Limit:</strong> <span>30K Daily</span></li>
        <li><strong>Trading Limit:</strong> <span>Unlimited</span></li>
        <li><strong>Transaction Limit:</strong><span>Unlimited</span></li>
      </ul>
    </div>
  </div>

  <!-- Personal Information -->
  <div class="card mb-4">
    <div class="card-body">
      <h4 class="card-title">Personal Information</h4>
      <p><strong>Legal Name:</strong> LIM LI XIANG</p>
      <p><strong>Date of Birth:</strong> 1988-05-12</p>
      <p><strong>Identification Documents:</strong> 8805******15</p>
      <p><strong>Email Address:</strong> li***@gmail.com</p>
      <p><strong>Country:</strong> Malaysia</p>
    </div>
  </div>
</div>
@endsection
