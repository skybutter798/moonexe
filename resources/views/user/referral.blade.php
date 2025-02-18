@extends('layouts.users.app')

@section('title', 'Referral Program')

@section('content')
<div class="container py-4">
  <!-- Referral Banner: Background covers both banner text and referral box -->
  <div class="referral-banner p-4 mb-4 rounded" style="background: url('{{ asset('img/referral_bg.png') }}'); background-size: cover; height:350px">
    <div class="row align-items-center">
      <!-- Left: Banner Text -->
      <div class="col-md-8">
        <h2 class="mb-3 text-white">Refer Friends.<br>Get Equivalent Trading Referral Fee Credit Each.</h2>
        <p class="text-white-50 mb-0">
          You can earn extra credits every time your referred friend completes a trade.
          Share your referral link and watch your rewards grow!
        </p>
      </div>
      <!-- Right: Referral Box -->
      <div class="col-md-4">
        <div class="referral-box p-4 bg-dark rounded">
          <label for="ref-id" class="fw-bold mb-1 text-white">REFERRAL ID</label>
          <input type="text" id="ref-id" class="form-control mb-3" value="MEX_000MOON01" readonly>
          <label for="ref-link" class="fw-bold mb-1 text-white">REFERRAL LINK</label>
          <input type="text" id="ref-link" class="form-control mb-3" value="https://moonex_app.com/ref/xxxx" readonly>
          <button class="btn btn-primary btn-sm">Invite Friends</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Tips Section -->
    <div class="tips-section mb-4">
      <h3 class="mb-3">Tips</h3>
      <div class="row">
        <!-- Tip Card 1 -->
        <div class="col-md-4 mb-3">
          <div class="card tip-card h-100">
            <div class="card-body text-center">
              <!-- Icon on top, enlarged -->
              <i class="bi bi-share-fill fs-1 mb-2 text-white"></i>
              <h5 class="card-title">Step 1</h5>
              <p class="card-text">Share your referral link with friends.</p>
            </div>
          </div>
        </div>
        <!-- Tip Card 2 -->
        <div class="col-md-4 mb-3">
          <div class="card tip-card h-100">
            <div class="card-body text-center">
              <i class="bi bi-person-plus-fill fs-1 mb-2 text-white"></i>
              <h5 class="card-title">Step 2</h5>
              <p class="card-text">Invite friends to sign up and accumulatively deposit more than $50.</p>
            </div>
          </div>
        </div>
        <!-- Tip Card 3 -->
        <div class="col-md-4 mb-3">
          <div class="card tip-card h-100">
            <div class="card-body text-center">
              <i class="bi bi-cash-stack fs-1 mb-2 text-white"></i>
              <h5 class="card-title">Step 3</h5>
              <p class="card-text">Receive 100 USD cashback voucher each.</p>
            </div>
          </div>
        </div>
      </div>
    </div>


  <!-- Dark Section (Rules & FAQ) -->
  <div class="dark-section mb-4">
    <div class="card bg-dark text-white">
      <div class="card-body">
        <h3 class="card-title">Rules & FAQ</h3>
        <p class="card-text">
          Share your Referral ID / link with a friend who does not have a Binance account.
        </p>
        <h5 class="mt-4">Regular Task:</h5>
        <p class="card-text">
          Referees must accumulatively deposit more than $50 within 14 days of registration.
          Both referrer and referee will be rewarded with a 100 USD trading fee rebate voucher each.
        </p>
        <h5 class="mt-4">Disclaimer:</h5>
        <p class="card-text">
          You can only claim one reward per referral. For example, you will not be eligible for Referral Pro rewards if friends sign up using your [Referral Mode] ID / link.
        </p>
      </div>
    </div>
  </div>

  <!-- My Referrals Table -->
  <div class="my-referrals">
    <h4 class="mb-3">My Referrals</h4>
    <div class="table-responsive">
      <table class="table custom-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>MON_02</td>
            <td>Tothemoon</td>
            <td>1/27/25</td>
            <td>Pending</td>
          </tr>
          <tr>
            <td>MON_082</td>
            <td>Downtoearth</td>
            <td>1/27/25</td>
            <td>Pending</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
