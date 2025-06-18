<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>
        Dashboard
    </x-slot:pageTitle>

    <x-slot:headerFiles>
        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
        <!-- Chart.js & ApexCharts -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <!-- BS5 Intro Tour CSS/JS (Local Files) -->
        <link href="{{ asset('css/users/bs5-intro-tour.css') }}" rel="stylesheet">
        <script src="{{ asset('js/users/bs5-intro-tour.js') }}"></script>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

        <style>
            /* Force Next button to display */
            .popover-navigation button[data-role="next"] {
                display: inline-block !important;
                opacity: 1 !important;
            }
            .custom-table thead th {
              font-size: 1rem;
            }
            /* Default button size for larger screens */
            .action-btn {
              width: 100px;
            }
            /* Responsive adjustments for mobile (screens smaller than 576px) */
            @media (max-width: 576px) {
              .action-btn {
                width: 80px;
              }
            }
            body {
                background-color: white;
            }
            .card-clickable {
              cursor: pointer;
              transition: box-shadow 0.3s ease;
            }
            .card-clickable:hover {
              box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            .card .card-header {
                  background-color:#4d80b5;
                  color:white;
                  padding: 10px;
                  border-top-left-radius: 0px;
                  border-top-right-radius: 0px;
              }
            .banner {
              border: 1px solid #4d80b5;
              background-color: white;
              padding: 0;
              border-radius: 8px;
              overflow: hidden;
            }
            .banner-img {
              width: 100%;
              height: auto;
              display: block;
            }
            .announcement {
              border: 1px solid #4d80b5;
              background-color: white;
              overflow: hidden;
              height: 30px;
              position: relative;
              display: flex;
              align-items: center;
            }
            .announcement-text {
              white-space: nowrap;
              display: inline-block;
              padding-left: 100%;
              animation: scroll-left 15s linear infinite;
              font-size: 14px;
            }
            @keyframes scroll-left {
              0% { transform: translateX(0%); }
              100% { transform: translateX(-100%); }
            }
            .assets {
              border: 1px solid #4d80b5;
              background-color: white;
              padding: 20px;
              text-align: center;
              align-content: center;
              border-radius: 0px;
            }
            .estbalance {
              border: 1px solid #4d80b5;
              background-color: white;
              padding: 15px;
            }
            .custom-btn {
                background-color: #162c81;
                color: white;
                border-radius: 15px;
            }
            .wallet-btn {
                background-color: #162c81;
                color: white;
                border-radius: 15px;
                width: 40%;
                margin: auto;
            }
            .text-custom {
                color: #162c81;
            }
            .bg-custom {
                background-color: #4d80b5;
            }
            .value {
                padding: 8px;
                background-color: #e1e1e1;
                color: black;
                border-radius: 25px;
                font-weight: bolder;
                width: 50%;
                margin: auto;
            }
            .forexprice {
                padding: 8px;
                background-color: #e1e1e1;
                color: black;
                border-radius: 25px;
                font-weight: bolder;
                width: 80%;
                margin: auto;
                text-align: center;
            }
            .openorder {
                position: absolute;
                top: 10px;
                right: 10px;
            }
            @media (max-width: 1199px) {
                #content .middle-content {
                    padding: 0 0px !important;
                }
            }
            .text-danger {
                margin-top: 0px !important;
            }
            
            .form-control:disabled:not(.flatpickr-input), .form-control[readonly]:not(.flatpickr-input) {
                background-color: none;
                cursor: no-drop;
                color: black;
            }
            
            /* announcement modal & backdrop on top of everything */
            #announcementModal.modal {
              z-index: 20000 !important;
            }
            #announcementModal + .modal-backdrop {
              z-index: 19999 !important;
            }
            
            #campaignProgressBar {
                font-weight: bold;
                font-size: 14px;
                line-height: 28px;
                color: white;
            }
            
            .bonus-glow {
              text-shadow: 0 0 8px #28a745, 0 0 16px #28a745, 0 0 24px #28a745;
              animation: glowPulse 1.5s infinite ease-in-out;
            }
            
            @keyframes glowPulse {
              0%   { text-shadow: 0 0 8px #28a745; }
              50%  { text-shadow: 0 0 16px #28a745, 0 0 24px #28a745; }
              100% { text-shadow: 0 0 8px #28a745; }
            }

        </style>
    </x-slot:headerFiles>

    <div class="container-fluid py-4">
        <!-- Flash Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        <!-- banner -->
        <div class="col-12">
          <div class="banner">
            <img src="/img/banner_1.png" alt="Banner" style="width: 100%; height: auto;">
          </div>
        </div>
        
        @foreach($announcements as $index => $announcement)
          <div class="modal fade" id="announcementModal{{ $announcement->id }}" tabindex="-1" aria-labelledby="announcementModalLabel{{ $announcement->id }}" aria-hidden="true" style="z-index: 9999;">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
              <div class="modal-content bg-white shadow-lg rounded-3 border-0">
                <div class="modal-header bg-primary border-0 rounded-top">
                  <h5 class="modal-title text-white" id="announcementModalLabel{{ $announcement->id }}">{{ $announcement->name }}</h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3 text-black">
                  {!! nl2br($announcement->content) !!}
                </div>
                <div class="modal-footer border-0">
                  <button type="button" class="btn btn-outline-primary px-4" data-bs-dismiss="modal">Close</button>
                </div>
              </div>
            </div>
          </div>
        @endforeach

        
        <!-- announcement -->
        <div class="col-12">
          <div class="announcement mt-2 mb-2">
            <div class="announcement-text text-custom">
              Welcome to your Moonexe Dashboard — from here, you can access your performance analytics, monitor wallet balances, review reports, and stay up to date with the latest updates and insights.
            </div>
          </div>
        </div>
        
        <!-- assets analysis -->
        <div class="col-12">
          <div class="assets mt-2 mb-2">
            <p>Assets Analysis Chart Section</p>
            <!-- Container for the ApexCharts chart -->
            <div id="s-line-area"></div>
          </div>
        </div>
        
        <!-- trading analysis -->
        <div class="col-12">
          <div class="assets mt-2 mb-2">
            <p>Trading Profit Analysis Chart Section</p>
            <!-- Container for the Trading Profit Chart -->
            <div id="profitChart"></div>
          </div>
        </div>
        
        <hr>
        
        {{--@include('user.partials.widget-card')
        @stack('scripts')--}}

        
        <!-- Estimated Balance + Actions -->
        <div class="estbalance mt-2 mb-2" style="background-image: url('/img/usdt.png'); background-repeat: no-repeat; background-position: left center;">
            <div class="row mb-4 mb-md-0 align-items-center">
                <div class="col-md-6">
                    <strong class="text-dark">Hi, {{ $user->name }}</strong>
                    <button id="restartTourBtn" type="button" class="btn btn-sm btn-link p-0 ms-2" title="Show tutorial again" style="font-size: 14px; background-color:white; width: 30px; height: 30px"> 
                    <i class="bi bi-exclamation-circle text-danger" style="font-size: 1.0rem;"></i> </button>
                    
                    @if(isset($currentRange))
                        <p class="mt-1">
                            <span class="badge bg-custom text-white">{{ $currentRange->name }}</span>
                        </p>
                    @else
                        <p class="mt-1">
                            <a href="#" class="badge bg-secondary text-white" data-bs-toggle="modal" data-bs-target="#packageModal">
                                Activate Trade Account
                            </a>
                        </p>
                    @endif
                    <h2 class="mb-1 text-custom">
                        {{ number_format($total_balance, 2) }} USDT
                    </h2>
                </div>
                <div class="col-md-6 text-md-end mt-4 mt-md-0">
                    <div class="row g-2 d-flex justify-content-center justify-content-md-end">
                        <!-- Deposit -->
                        <div class="col-3 col-md-auto text-center">
                          <div class="d-flex flex-column align-items-center">
                            <i class="bi bi-cash-coin fs-1 text-custom"></i>
                            <button id="depositButton" class="btn p-1 action-btn custom-btn mt-2">
                              Deposit
                            </button>
                          </div>
                        </div>
                        <!-- Trade -->
                        <div class="col-3 col-md-auto text-center">
                            <div class="d-flex flex-column align-items-center">
                                <i class="bi bi-coin fs-1 text-custom"></i>
                                <button type="button" id="tradeButton" class="btn p-1 action-btn custom-btn mt-2" onclick="window.location.href='{{ route('user.order') }}';">
                                Trade
                                </button>
                            </div>
                        </div>
                        <!-- Send -->
                        <div class="col-3 col-md-auto text-center">
                            <div class="d-flex flex-column align-items-center">
                                <i class="bi bi-send fs-1 text-custom"></i>
                                <button type="button" class="btn p-1 action-btn custom-btn mt-2" data-bs-toggle="modal" data-bs-target="#sendModal">
                                    Send
                                </button>
                            </div>
                        </div>
                        <!-- Withdraw -->
                        <div class="col-3 col-md-auto text-center">
                            <div class="d-flex flex-column align-items-center">
                                <i class="bi bi-dash-circle fs-1 text-custom"></i>
                                <button class="btn p-1 action-btn custom-btn mt-2" data-bs-toggle="modal" data-bs-target="#withdrawalModal">
                                    Withdraw
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <hr>
        
        <!-- Sub-Wallets -->
        <div class="row">
            <h2 class="text-primary"><strong>Wallet Balance</strong></h2>
            
            <!--@php
                $bonus = number_format($megadropDeposit ?? 0, 2);
            @endphp
            
            <div class="my-4 p-4 text-center border rounded shadow-sm bg-light">
                <h4 class="text-primary mb-3">
                    🎯 $3,000,000 Growth Initiative Concludes Successfully!
                </h4>
                <p class="mb-2 fw-semibold text-dark">
                    Thank you for being part of this incredible milestone.
                    Your eligible <span class="text-success">Campaign Bonus Margin: <strong>${{ $bonus }}</strong></span>
                </p>
                <p class="text-muted small">
                    Bonuses will be credited soon. Stay tuned for upcoming campaigns!
                </p>
            </div>-->

            <!-- USDT Wallet Card -->
            <div class="col-12 col-md-6 mb-3">
                <div class="card h-100 text-center p-2 assets" style="background-image: url('/img/usdt.png'); background-repeat: no-repeat; background-position: left center;">
                    <div class="card-body d-flex flex-column">
                        <h5 class="text-custom">USDT Wallet</h5>
                        <p class="sub-wallet-amount mb-0 value">
                            {{ number_format($wallets->cash_wallet, 2) }}
                        </p>
                        <!-- Package Button -->
                        <button id="activateTradingAccount" class="btn wallet-btn btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#packageModal">
                            {{ $hasPackageTransfer ? 'Top-up' : 'Activate' }}
                        </button>
                    </div>
                    <!--<div id="megadropCountdown" class="mt-2 text-danger small fw-bold ">
                        CAMPAIGN <span id="countdownTimer">Loading...</span>
                    </div>-->
                </div>
            </div>
            @php
                $isDeactivated = $user->status == 0;
            @endphp
            
            @if($user->bonus)
            <!-- Trading Wallet (with bonus) -->
            <div class="col-12 col-md-6 mb-3">
                <div id="tradingWalletCard" class="card h-100 text-center p-2 position-relative assets"
                     style="background-image: url('/img/trademargin.png'); background-repeat: no-repeat; background-position: left center; {{ $isDeactivated ? 'background-color: #f0f0f0; background-image: none;' : '' }}">
                    <div class="card-body d-flex flex-column">
                        <h5 class="text-custom">Trade Margin</h5>
                        <p class="sub-wallet-amount mb-1 value" style="position: relative;">
                            {{ number_format($wallets->trading_wallet, 2) }}
                            <button type="button"
                                    class="btn btn-sm custom-btn"
                                    style="position: absolute; right: 5px; top: 10%; padding: 8px 12px; line-height: 1; border-radius: 100px;"
                                    data-bs-toggle="modal"
                                    data-bs-target="#tradingTransferModal"
                                    {{ $isDeactivated ? 'disabled' : '' }}>
                                -
                            </button>
                        </p>
                        <a href="{{ route('user.order') }}"
                           class="btn wallet-btn btn-sm mt-2"
                           {!! $isDeactivated ? 'onclick="return false;" style="background:#ccc;cursor:not-allowed;"' : '' !!}>
                            Trade
                        </a>
                        <p class="openorder"><small class="text-danger">*Open Order: ${{ number_format($pendingBuy, 4) }}</small></p>
                    </div>
                    {{--<div class="mt-1 text-black small " id="bonusInfoText">
                        Congratulation! Total campaign Bonus Margin: <strong class="text-success">${{ number_format($megadropDeposit, 2) }}</strong><br>
                        <small class="text-black" id="bonusCreditNote">
                           All bonus margin will be distributed within the next 7 days
                        </small>
                    </div>--}}

                </div>
            </div>
            
            <!-- Bonus Margin Card -->
            <div class="col-12 col-md-6 mb-3">
                <div id="bonusWalletCard" class="card h-100 text-center p-2 assets" style="{{ $isDeactivated ? 'background-color: #f0f0f0;' : '' }}">
                    <div class="card-body d-flex flex-column">
                        <h5 class="text-custom">Bonus Margin</h5>
                        <p class="sub-wallet-amount mb-0 value">{{ number_format($wallets->bonus_wallet, 2) }}</p>
                        <a href="{{ route('user.order') }}"
                           class="btn wallet-btn btn-sm mt-2"
                           {!! $isDeactivated ? 'onclick="return false;" style="background:#ccc;cursor:not-allowed;"' : '' !!}>
                            Trade
                        </a>
                    </div>
                </div>
            </div>
            @else
            <!-- Trading Wallet (without bonus) -->
            <div class="col-12 col-md-6 mb-3">
                <div id="tradingWalletCard" class="card h-100 text-center p-2 position-relative assets"
                     style="background-image: url('/img/trademargin.png'); background-repeat: no-repeat; background-position: left center; {{ $isDeactivated ? 'background-color: #f0f0f0; background-image: none;' : '' }}">
                    @if(is_null($user->bonus))
                    <div class="col-12 mt-2">
                        <button class="btn custom-btn btn-sm"
                                style="position: absolute; top: 10px; left: 20px; height: 22px; padding: 5px; line-height: 1;"
                                data-bs-toggle="modal" data-bs-target="#promotionModal"
                                {{ $isDeactivated ? 'disabled' : '' }}>
                            Promotion Code
                        </button>
                    </div>
                    @endif
                    <div class="card-body d-flex flex-column">
                        <h5 class="text-custom">Trade Margin</h5>
                        <p class="sub-wallet-amount mb-1 value position-relative" style="font-size: 1.1rem;">
                            {{ number_format($wallets->trading_wallet, 2) }}
                            <button type="button"
                                    class="btn btn-sm custom-btn"
                                    style="position: absolute; right: 5px; top: 10%; padding: 6px 10px; line-height: 1; border-radius: 100px;"
                                    data-bs-toggle="modal"
                                    data-bs-target="#tradingTransferModal"
                                    {{ $isDeactivated ? 'disabled' : '' }}>
                                -
                            </button>
                        </p>
                        <div class="d-flex flex-column align-items-center gap-1 mt-2">
                            <span class="badge rounded-pill bg-primary text-white px-3 py-1">
                                Bonus Margin: ${{ number_format($campaignTradingBonus, 2) }}
                            </span>
                            <!--<span class="badge rounded-pill bg-primary px-3 py-1">
                                ✅ Real Margin: ${{ number_format($wallets->trading_wallet - $campaignTradingBonus, 2) }}
                            </span>-->
                        </div>

                        <div class="row mt-2">
                            <div class="col-12">
                                <a href="{{ route('user.order') }}"
                                   class="btn wallet-btn btn-sm"
                                   {!! $isDeactivated ? 'onclick="return false;" style="background:#ccc;cursor:not-allowed;"' : '' !!}>
                                    Trade
                                </a>
                            </div>
                        </div>
                        <p class="openorder"><small class="text-danger">*Open Order: ${{ number_format($pendingBuy, 4) }}</small></p>
                    </div>
                    {{--@if($megadropDeposit > 0)
                        <div class="p-3 bg-light border rounded text-center small" id="bonusInfoText" style="max-width: 500px; margin: 0 auto;">
                            <div class="mb-2">
                                <strong>Congratulations!</strong><br>
                                Total Campaign Bonus Margin:
                                <strong class="text-primary">${{ number_format($megadropDeposit, 2) }}</strong>
                            </div>
                            <button class="btn btn-primary" id="startClaimBtn"> Claim Bonus </button>
                        </div>
                    @endif--}}
                </div>
            </div>
            @endif
            

            <!-- Earning Wallet -->
            <div class="col-12 col-md-6 mb-3">
                <div id="tradingWalletCard" class="card h-100 text-center p-2 assets" style="background-image: url('/img/tradingprofit.png'); background-repeat: no-repeat; background-position: left center;">
                    <div class="card-body d-flex flex-column">
                        <h5 class="text-custom">Trading Profit</h5>
                        <p class="sub-wallet-amount mb-0 value">{{ number_format($wallets->earning_wallet, 2) }}</p>
                        <button class="btn wallet-btn btn-sm mt-2 wallet-transfer-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#walletTransferModal"
                                data-transfer-type="earning_to_cash"
                                data-wallet-balance="{{ $wallets->earning_wallet }}"
                                data-wallet-name="Earning">
                            Collect Profit
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Affiliates Wallet -->
            <div class="col-12 col-md-6 mb-3">
                <div id="affiliatesWalletCard" class="card h-100 text-center p-2 assets" style="background-image: url('/img/incentive.png'); background-repeat: no-repeat; background-position: left center;">
                    <div class="card-body d-flex flex-column">
                        <h5 class="text-custom">Affiliate Incentive</h5>
                        <p class="sub-wallet-amount mb-0 value">{{ number_format($wallets->affiliates_wallet, 2) }}</p>
                        <button class="btn wallet-btn btn-sm mt-2 wallet-transfer-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#walletTransferModal"
                                data-transfer-type="affiliates_to_cash"
                                data-wallet-balance="{{ $wallets->affiliates_wallet }}"
                                data-wallet-name="Affiliates">
                            Collect Incentive
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Sub-Wallets -->
        
        <hr>
        
        <!-- Forex Cards Section -->
        @php
            // Define a mapping of symbols to flag filenames.
            $flagMapping = [
                'CHFUSD'  => 'ch.svg',
                'TRYUSD'  => 'tr.svg',
                'HKDUSD'  => 'hk.svg',
                'THBUSD'  => 'th.svg',
                'TWDUSD'  => 'tw.svg',
                'USDVND'  => 'vn.svg',
                'NZDUSD'  => 'nz.svg',
                'BRLUSD'  => 'br.svg',
                'USDCOP'  => 'co.svg',
                'USDLKR'  => 'lk.svg',
                'AUDUSD'  => 'au.svg',
                'EURUSD'  => 'eu.svg',
                'CADUSD'  => 'ca.svg',
                'EGPUSD'  => 'eg.svg',
                'GBPUSD'  => 'gb.svg',
                'USDIDR'  => 'id.svg',
                'JODUSD'  => 'jo.svg',
                'KWDUSD'  => 'kw.svg',
                'MXNUSD'  => 'mx.svg',
                'ZARUSD'  => 'za.svg'
            ];
        @endphp
        
        <div class="row mb-4" id="forexCards">
          <h2 class="text-primary"><strong>Currency Data</strong></h2>
        
          @foreach($forexRecords as $record)
            @php
                // Get the correct flag filename for this symbol. Use a fallback if not set.
                $flagFile = $flagMapping[$record->symbol] ?? 'default.svg';
            @endphp
            <div class="col-md-4 mb-3 forex-card">
              <div class="card">
                <div class="card-header d-flex align-items-center">
                  <img src="{{ asset('img/1x1/' . $flagFile) }}" style="width:20px; margin-right:5px;">
                  {{ $record->symbol }}
                </div>
                <div class="card-body">
                  <div class="row">
                    <!-- Left Column -->
                    <div class="col-8 border-end text-center card-symbol" 
                         data-symbol="{{ $record->symbol }}" 
                         onclick="navigateToOrder('{{ $record->symbol }}')">
                      <p class="card-title forexprice" id="price-{{ $record->symbol }}">Price: Loading...</p>
                      <p class="card-text m-0">
                        <span class="text-success" id="bid-{{ $record->symbol }}">Bid: Loading...</span> | 
                        <span class="text-danger" id="ask-{{ $record->symbol }}">Ask: Loading...</span>
                      </p>
                      <p class="card-text">
                        <small class="text-muted" id="time-{{ $record->symbol }}">Last updated: Loading...</small>
                      </p>
                    </div>
                    <!-- Right Column: Sparkline Chart Container -->
                    <div class="col-4 card-clickable" data-symbol="{{ $record->symbol }}">
                      <div id="sparkline-{{ $record->symbol }}" style="margin-top:25px"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          @endforeach
        
          <!-- Load More Button -->
          <div class="col-12 text-center mt-3">
              <button id="loadMoreBtn" class="btn btn-primary">Load More...</button>
          </div>
        </div>
        <!-- End Forex Cards -->

        <!-- Chart Modal -->
        <div class="modal fade" id="chartModal" tabindex="-1" aria-labelledby="chartModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content bg-white">
              <div class="modal-header">
                <h5 class="modal-title" id="chartModalLabel">Chart Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <!-- Interval Selection -->
                <div class="mb-3">
                  <label for="intervalSelect" class="form-label">Select Interval</label>
                  <select id="intervalSelect" class="form-select">
                    <option value="daily" selected>Daily</option>
                    <option value="hourly">Hourly</option>
                    <option value="minute">Minute</option>
                  </select>
                </div>
                <!-- Chart Container for Modal -->
                <div id="modalChart"></div>
              </div>
              <div class="modal-footer">
                <!-- Reload Chart Button -->
                <button type="button" id="reloadChartBtn" class="btn btn-primary">Reload Chart</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Deposit Modal -->
        <div class="modal fade" id="depositModal" tabindex="-1" aria-labelledby="depositModalLabel" aria-hidden="true" >
            <div class="modal-dialog">
                <div class="modal-content bg-white">
                    <div class="modal-header">
                        <h5 class="modal-title" id="depositModalLabel">Deposit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <!-- 🚧 Upgrade Notice (hidden by default) -->
                    {{--<div id="depositUpgradeNotice" class="alert alert-danger fw-normal text-start" style="margin: 10px;">
                        🚧 <strong>Deposit Notice</strong><br><br>
                        We would like to inform you that our payment infrastructure partner, <strong>Coinremitter</strong>, is currently undergoing a scheduled system upgrade.<br><br>
                        As a result, TRC20 deposits may experience delays in detection and balance updates during this period.<br><br>
                        All valid transactions will be credited once the upgrade is completed and systems are fully synchronized.<br><br>
                        We sincerely appreciate your patience and understanding. Thank you for your continued trust in our platform.
                    </div>--}}
                    <form action="{{ route('user.deposit') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <p class="fw-semibold fs-6">
                                Hi {{ $user->name }}, your current USDT balance is <span class="text-danger">{{ number_format($wallets->cash_wallet, 2) }} USDT</span>.
                            </p>

                            <div class="mb-3 text-center">
                                <img id="walletQR" src="{{ $user->wallet_qr ?? asset('img/QR_trc20.png') }}" alt="TRC20 QR Code" class="img-fluid" style="max-width: 200px;">
                                <p class="mt-2">Scan to deposit</p>
                            </div>
                            <div class="input-group mb-3">
                              <input
                                type="text"
                                name="trc20_address"
                                class="form-control"
                                id="depositTRC20"
                                value="TPjV4gDxbtNpHXLSzNG2Se4aGP3RSfjwoa"
                                readonly
                              >
                              <button
                                class="btn btn-dark"
                                type="button"
                                id="copyTrc20"
                                onclick="
                                  navigator.clipboard
                                    .writeText(document.getElementById('depositTRC20').value)
                                    .then(() => { this.textContent = 'Copied!'; setTimeout(() => this.textContent = 'Copy', 1500); })
                                    .catch(() => { alert('Copy failed—please select and copy manually.'); })
                                "
                              >
                                Copy
                              </button>
                            </div>
                            <div class="mt-3">
                                <input type="number" step="1" min="10" name="amount" class="form-control" id="depositAmount" required placeholder="Amount">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Submit Deposit</button>
                        </div>
                        <div class="alert alert-warning p-2">
                            
                            <strong>⚠️ Important Notice:</strong><br>
                            • Please deposit <strong>only USDT (TRC20)</strong> to the address above.<br>
                            • Each user receives a <strong>unique wallet address</strong>.<br>
                            • <span class="text-danger fw-bold">DO NOT send USDT via other networks (e.g. ERC20, BEP20) or FUNDS WILL BE LOST AND CANNOT BE RECOVERED</span>.<br>
                        </div>

                        </form>
                </div>
            </div>
        </div>

        <!-- Withdrawal Modal -->
        <div class="modal fade" id="withdrawalModal" tabindex="-1" aria-labelledby="withdrawalModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-white">
                    <div class="modal-header">
                        <h5 class="modal-title" id="withdrawalModalLabel">Withdrawal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('user.withdrawal') }}" method="POST" class="p-3">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-4">
                                <h5 class="fw-semibold">USDT Balance:</h5>
                                <p class="fs-5 text-success mb-0">{{ number_format($wallets->cash_wallet, 2) }} USDT</p>
                            </div>
                    
                            <div class="mb-3">
                                <label for="withdrawalAmount" class="form-label fw-medium">Amount</label>
                                <input type="number" step="1" min="10" name="amount" inputmode="numeric" pattern="\d*" class="form-control shadow-sm" id="withdrawalAmount" placeholder="Enter amount to withdraw" required>
                                <small class="form-text text-danger mt-1">*Only whole numbers allowed. No decimals.</small>
                            </div>
                            
                            <hr>
                    
                            <div class="mb-3">
                                <label for="withdrawalTRC20" class="form-label fw-medium">TRC20 Wallet Address</label>
                                <input type="text" name="trc20_address" class="form-control shadow-sm" id="withdrawalTRC20" placeholder="Enter your TRC20 address" required>
                            </div>
                    
                            @php $twoFAEnabled = auth()->user()->two_fa_enabled; @endphp
                    
                            @if ($twoFAEnabled)
                                <div class="mb-3">
                                    <label for="otp" class="form-label fw-medium">2FA Code</label>
                                    <input type="text" name="otp" id="otp" class="form-control shadow-sm" placeholder="Enter your 6-digit code" required>
                                </div>
                            @endif
                    
                            <div class="alert alert-warning mt-4" id="withdrawalFeeInfo">
                                <strong>Note:</strong> A fee of <b>3%</b> or <b>minimum 7 USDT</b> (whichever is higher) will be deducted.
                                You will receive: <span class="fw-bold text-danger">0 USDT</span> <br>All withdrawals are processed within 24 hours.
                            </div>
                        </div>
                    
                        <div class="modal-footer border-top pt-3">
                            <button type="submit" class="btn btn-lg btn-primary w-100 shadow-sm">Request Withdrawal</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <!-- Wallet Transfer Modal for Earning/Affiliates -->
        <div class="modal fade" id="walletTransferModal" tabindex="-1" aria-labelledby="walletTransferModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-white">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="walletTransferModalLabel">Transfer</h5>
                            <small class="text-muted">
                                Collecting trading profit, or incetive profit
                            </small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('user.transfer') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <p class="fs-6 text-success mb-3" id="sourceWalletBalance"></p>
                            <input type="hidden" id="transferTypeHidden" name="transfer_type" value="">
                            <div class="mb-3">
                                <label for="transferAmountDynamic" class="form-label">Amount</label>
                                <input type="number" step="0.01" name="amount" class="form-control shadow-sm" id="transferAmountDynamic" required>
                            </div>
                            
                            @php $userSecurityPass = auth()->user()->security_pass; @endphp

                            @if ($userSecurityPass)
                                <div class="mb-3">
                                    <label for="securityPass" class="form-label fw-medium">Security Password</label>
                                    <input type="password" name="security_pass" class="form-control shadow-sm" id="securityPass" placeholder="Enter your security password" required>
                                </div>
                            @endif

                            <div class="alert alert-warning mt-4" id="withdrawalFeeInfo">
                                Please enter the transfer amount below. Funds will be debited from your trading profit or affiliates wallet and credited to your USDT wallet upon successful processing.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Collect</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Trading Wallet Transfer Modal -->
        <div class="modal fade" id="tradingTransferModal" tabindex="-1" aria-labelledby="tradingTransferModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-white">
                    <div class="modal-header">
                        <h5 class="modal-title" id="tradingTransferModalLabel">Terminate Trade Margin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
        
                    <form id="tradingTransferForm" action="{{ route('user.tradingTransfer') }}" method="POST">
                        @csrf
                        <!-- 🔐 Hidden 2FA value will be added here -->
                        <input type="hidden" name="otp" id="hiddenOtp">
        
                        <div class="modal-body">
                            <p><strong>Trading Balance:</strong> {{ number_format($wallets->trading_wallet - $campaignTradingBonus, 2) }} USDT ({{ number_format($campaignTradingBonus, 2) }} Campaign bonus margin)</p>
                            <p><strong>Fee Rate:</strong> 20%</p>
                            <p class="text-danger">
                                *** Upon termination, your full trading balance, including any open orders, will be credited to your USDT wallet. A 20% fee will be deducted.
                            </p>
                        </div>
                        <div class="modal-footer">
                            @if($realTradingBalance <= 0)
                                <button type="button" class="btn btn-secondary" disabled>Insufficient Real Balance</button>
                            @else
                                <button type="button" class="btn btn-primary" id="confirmTerminateBtn">Terminate</button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Confirmation Modal -->
        <div class="modal fade" id="tradingTransferConfirmModal" tabindex="-1" aria-labelledby="tradingTransferConfirmModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-white">
                    <div class="modal-header">
                        <h5 class="modal-title" id="tradingTransferConfirmModalLabel">Confirm Termination</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p id="transferConfirmationDetails"></p>
        
                        @php $user = auth()->user(); @endphp
                        <div id="2faWrapper" class="mt-3">
                            @if ($user->two_fa_enabled && $user->google2fa_secret)
                                <label for="otp" class="form-label">2FA Code</label>
                                <input type="text" id="otp" class="form-control" placeholder="Enter 6-digit 2FA code" required>
                            @else
                                <div class="alert alert-danger small">
                                    2FA is required to proceed. Please <a href="{{ route('user.account') }}" class="text-decoration-underline">enable it in your profile</a>.
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        @if ($user->two_fa_enabled && $user->google2fa_secret)
                            <button type="submit" class="btn btn-primary" id="finalizeTerminateBtn">Yes, Confirm</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Top-up Modal -->
        <div class="modal fade" id="packageModal" tabindex="-1" aria-labelledby="packageModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content bg-white" style="width: 80%;">
                    <div class="modal-header">
                        <h5 class="modal-title" id="packageModalLabel">
                            {{ $hasPackageTransfer ? 'Top-up' : 'Activate Trade' }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="px-4 pt-2">
                        <p class="text-muted d-block">
                            @if(!$user->package)
                                Please choose a package to activate your trading margin.
                            @else
                                Please key in the top-up amount to your trading margin.
                            @endif
                        </p>
                    </div>
                    <div class="modal-body">
                        @if(!$user->package)
                            <form action="{{ route('user.buyPackage') }}" method="POST" id="activationForm">
                                @csrf
                                <div class="mb-3">
                                    <label for="activation_amount" class="form-label">Enter Activation Amount</label>
                                    <input type="number" name="activation_amount" id="activation_amount" class="form-control shadow-sm" placeholder="Enter amount" min="10" step="10" required>
                                </div>
                                <div class="row">
                                    @foreach($directRanges->whereIn('id', [1,2,3]) as $range)
                                        <div class="col-md-4 mb-3">
                                            <div class="card h-100 shadow-sm">
                                                <div class="card-body text-center">
                                                    <h2 class="mb-3" style="font-size: 1.5rem; min-height: 3rem;">{{ $range->name }}</h2>
                                                    <div class="package-details text-left">
                                                        <p class="mb-1"><strong>Range:</strong> ${{ number_format($range->min, 2) }} - @if($range->max) ${{ number_format($range->max, 2) }} @else &infin; @endif</p>
                                                        <p><strong>ROI per trade:</strong> <span class="badge bg-success">{{ ($range->percentage * 100) . '%' }}</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Activate</button>
                            </form>
                        @else
                            <div class="current-package">
                                <form action="{{ route('user.buyPackage') }}" method="POST" class="package-form">
                                    @csrf
                                    <input type="hidden" name="directrange_id" value="{{ $currentRange->id }}">
                                    <div class="mb-2">
                                        <input type="number" name="topup_amount" class="form-control shadow-sm" placeholder="Enter top-up amount" min="10" step="10" required>
                                    </div>
                                    
                                    <div class="alert alert-warning mt-4" id="withdrawalFeeInfo">
                                        <span class="text-danger">*Top-up must be in multiples of 10. No decimal values allowed.</span>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary w-100 mt-2">Top-up</button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Send Modal -->
        <div class="modal fade" id="sendModal" tabindex="-1" aria-labelledby="sendModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-white">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="sendModalLabel">Send USDT to referral</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('user.sendFunds') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-4">
                                <h5 class="fw-semibold">USDT Balance:</h5>
                                <p class="fs-5 text-success mb-0">{{ number_format($wallets->cash_wallet, 2) }} USDT</p>
                            </div>
                            
                            <div class="mb-3">
                                <label for="downlineEmail" class="form-label fw-medium">Referral Email/Username</label>
                                <input type="text" name="downline_email" class="form-control shadow-sm" id="downlineEmail" placeholder="Enter referral email or username" required>

                            </div>
                            <div class="mb-3">
                                <label for="sendAmount" class="form-label fw-medium">Amount</label>
                                <input type="number" step="1" min="10" name="amount" class="form-control shadow-sm" id="sendAmount" placeholder="Enter amount" required>
                            </div>
                            
                            @php $userSecurityPass = auth()->user()->security_pass; @endphp

                            @if ($userSecurityPass)
                                <div class="mb-3">
                                    <label for="securityPass" class="form-label fw-medium">Security Password</label>
                                    <input type="password" name="security_pass" class="form-control shadow-sm" id="securityPass" placeholder="Enter your security password" required>
                                </div>
                            @endif
                        </div>

                        <div class="modal-footer">
                            <div class="alert alert-warning mt-4" id="withdrawalFeeInfo">
                            <p class="text-danger">*Please ensure the referral email or username is correct. The requested amount will be deducted from your USDT balance and processed.</p>
                            </div>
                        
                            <button type="submit" class="btn btn-primary">Send</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Promotion Code Modal -->
        <div class="modal fade" id="promotionModal" tabindex="-1" aria-labelledby="promotionModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <form id="promotionForm" method="POST" action="{{ route('user.applyPromotion') }}">
              @csrf
              <div class="modal-content bg-white">
                <div class="modal-header">
                  <h5 class="modal-title" id="promotionModalLabel">Enter Promotion Code</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class="form-group">
                    <label for="promotion_code_input">Promotion Code</label>
                    <input type="text" class="form-control" id="promotion_code_input" name="promotion_code" required>
                  </div>
                  @error('promotion_code')
                      <div class="error-msg">{{ $message }}</div>
                  @enderror
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-primary">Apply Code</button>
                </div>
              </div>
            </form>
          </div>
        </div>
        
        <!-- Bonus Claim Modal -->
        <div class="modal fade" id="bonusClaimModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-4 bg-white" style="width: 400px;">
        
            <!-- Step 1: Intro (Updated Design) -->
            <div class="modal-step step-1 text-center px-3">
              <h4 class="fw-bold text-primary mt-3">Congratulations!</h4>
              
              <img src="/img/claim.png" alt="Coins Drop" style="max-width: 260px;">
            
              <p class="px-2 mt-3">
                You’ve been an essential part of our incredible growth journey — and we’re excited to reward your commitment.
              </p>
            
              <p class="px-2">
                You are officially eligible for our <strong>Campaign Bonus Margin Drop</strong> — a limited-time initiative celebrating our $3M campaign milestone.
              </p>
            
              <p class="px-2 mb-3">
                Get ready.. your details are being prepared!
              </p>
            
              <button class="btn btn-outline-primary w-100 py-2 next-step">Next</button>
            </div>
        
              <!-- Step 2: Loading/Processing -->
              <div class="modal-step step-2 d-none text-center">
                <h5 class="fw-bold text-primary mb-3 text-center">Processing...</h5>
                <p class="text-dark" id="airdropStatus">Clearing previous records...</p>
                <div class="spinner-border text-primary mt-3" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>
        
                <!-- Step 3: Breakdown & Claim (Updated Design) -->
                <div class="modal-step step-3 d-none px-3 text-start">
                  <h5 class="fw-bold text-primary mb-3 text-left">Campaign Bonus Margin Breakdown</h5>
                
                  <div class="">
                    <div class="fw-semibold text-dark small">Total Bonus Margin:</div>
                    <div class="fs-3 fw-bold text-dark">$<span id="totalBonus">{{ number_format($megadropDeposit, 2) }}</span></div>
                    <hr style="margin-top: 10px;">
                
                    <div class="d-flex align-items-start mb-2">
                      <img src="/img/icon1.png" width="22" height="22" class="me-2" alt="Top-up Icon">
                      <div class="small text-dark">
                        Inclusive Top-up: $<span id="topupAmount">{{ number_format($topupBeforeBoost, 2) }}</span>
                      </div>
                    </div>
                
                    <div class="d-flex align-items-start mt-2">
                      <img src="/img/icon2.png" width="22" height="22" class="me-2" alt="Leverage Icon">
                      <div class="small text-dark">
                        Bonus Margin: $<span id="campaignBoost">{{ number_format($megadropDeposit, 2) }}</span>
                      </div>
                    </div>
                
                    <a href="javascript:void(0);" class="small text-primary mt-2 d-inline-block" id="toggleMoreBtn">
                      Details <span id="detailsArrow">▼</span>
                    </a>
                    <div id="topupBreakdown" class="d-none mb-4 mt-4 small" style="margin-left: 14px;">
                      <ul class="list-group list-group-flush">
                        @foreach ($campaignTopups as $topup)
                          <li class="text-dark px-0 py-1">
                            {{ $topup->created_at->format('d M Y H:i') }} — ${{ number_format($topup->amount, 2) }}
                          </li>
                        @endforeach
                      </ul>
                    </div>
                  </div>
                
                  <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="agreeTerms">
                    <label class="form-check-label small text-muted" for="agreeTerms">
                      I agree to the campaign <a href="javascript:void(0)" onclick="toggleTerms()" class="text-primary">terms and conditions</a>.
                    </label>
                  </div>
                
                  <button class="btn btn-outline-primary w-100 mt-3 disabled" id="processClaimBtn">
                    Process to Claim
                  </button>
                
                  @include('user.partials.campaign')
                
                </div>
        
                <!-- Step 4: Claimed Success (Updated Design) -->
                <div class="modal-step step-4 d-none text-center px-4 py-5">
                  <h5 class="text-primary fw-bold mb-3">Bonus Margin Claimed</h5>
                
                  <img src="/img/icon3.png" alt="Bonus Claimed Icon" class="mb-4" style="max-width: 160px;">
                
                  <div class="fw-bold display-5 text-white bg-primary rounded-pill d-inline-block px-4 py-2 shadow-sm">
                    $<span id="bonusReveal">{{ number_format($topupBeforeBoost, 2) }}</span>
                  </div>
                
                  <p class="text-dark mt-4 small">Your bonus has been credited successfully</p>
                
                    <button class="btn btn-outline-primary fw-bold mt-4" data-bs-dismiss="modal" onclick="location.reload();">
                      Thank you for participating!
                    </button>
                </div>
        
            </div>
          </div>
        </div>

    </div>
    
    <x-slot:footerFiles>
    
    <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/laravel-echo/1.11.3/echo.iife.js"></script>
    <script>
        window.hasFlashMessage = @json(session('success') || $errors->any());
        window.announcement     = @json($announcement ?? null);
    </script>
    
    @if(session('success') || $errors->any())
        <div class="position-fixed top-50 start-50 translate-middle" style="z-index: 99999; min-width: 300px;">
            <div id="flashToast"
                 class="toast show text-white bg-{{ session('success') ? 'success' : 'danger' }} border-0 shadow-lg"
                 role="alert" aria-live="assertive" aria-atomic="true"
                 style="padding: 10px; border-radius: 12px;">
              <div class="d-flex align-items-center justify-content-between">
                <div class="toast-body w-100 text-center">
                  {!! session('success') ?? $errors->first() !!}
                </div>
                <button type="button" class="btn-close btn-close-white ms-3"
                        data-bs-dismiss="toast" aria-label="Close"></button>
              </div>
            </div>
        </div>
    @endif
          
    <script>
        // -------------------------------
        // Load More Functionality for Forex Cards
        // -------------------------------
        document.addEventListener("DOMContentLoaded", function() {
            const itemsToShowInitially = 9;
            const itemsToShowPerClick = 9;
            const forexCards = document.querySelectorAll(".forex-card");
            const loadMoreBtn = document.getElementById("loadMoreBtn");
    
            // Hide all forex cards after the initial group
            forexCards.forEach((card, index) => {
                if (index >= itemsToShowInitially) {
                    card.style.display = "none";
                }
            });
    
            loadMoreBtn.addEventListener("click", function() {
                let hiddenCards = Array.from(forexCards).filter(card => card.style.display === "none");
                hiddenCards.slice(0, itemsToShowPerClick).forEach(card => {
                    card.style.display = "block";
                });
                // Hide the button if there are no more hidden cards
                hiddenCards = Array.from(forexCards).filter(card => card.style.display === "none");
                if (hiddenCards.length === 0) {
                    loadMoreBtn.style.display = "none";
                }
            });
            
            document.querySelectorAll('.wallet-transfer-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const transferType = button.getAttribute('data-transfer-type');
                    const balance = button.getAttribute('data-wallet-balance');
                    const name = button.getAttribute('data-wallet-name');
        
                    // Update hidden field in modal form
                    document.getElementById('transferTypeHidden').value = transferType;
        
                    // Optionally show current balance
                    const balanceLabel = document.getElementById('sourceWalletBalance');
                    balanceLabel.textContent = `${name} Balance: ${parseFloat(balance).toFixed(2)} USDT`;
                });
            });
        });
    
        // -------------------------------
        // MARKET DATA & DISPLAY CODE (unchanged)
        // -------------------------------
        const CACHE_KEY = 'marketData';
        const CACHE_EXPIRY = 3600000;
    
        function getCachedData() {
          const cache = localStorage.getItem(CACHE_KEY);
          if (cache) {
            const parsed = JSON.parse(cache);
            if (Date.now() - parsed.timestamp < CACHE_EXPIRY) {
              return parsed.data;
            }
          }
          return null;
        }
    
        function setCachedData(data) {
          localStorage.setItem(CACHE_KEY, JSON.stringify({
            timestamp: Date.now(),
            data: data
          }));
        }
    
        const lastMarketData = {};
        function updateSymbolDisplay(symbol, marketData) {
          const priceEl = document.getElementById(`price-${symbol}`);
          const bidEl = document.getElementById(`bid-${symbol}`);
          const askEl = document.getElementById(`ask-${symbol}`);
          const timeEl = document.getElementById(`time-${symbol}`);
          const updateTime = new Date().toLocaleTimeString();
    
          if (priceEl) priceEl.textContent = "Price: " + marketData.mid.toFixed(5);
          if (bidEl) bidEl.textContent = "Bid: " + marketData.bid.toFixed(5);
          if (askEl) askEl.textContent = "Ask: " + marketData.ask.toFixed(5);
          if (timeEl) timeEl.textContent = "Last updated: " + updateTime;
        }
    
        function updateDisplay(data) {
          for (const symbol in data) {
            lastMarketData[symbol] = {
              mid: parseFloat(data[symbol].mid),
              bid: parseFloat(data[symbol].bid),
              ask: parseFloat(data[symbol].ask)
            };
            updateSymbolDisplay(symbol, lastMarketData[symbol]);
          }
        }
    
        function fetchMarketData() {
          const cached = getCachedData();
          if (cached) {
            updateDisplay(cached);
          } else {
            fetch('https://app.moonexe.com/api/market-data')
              .then(response => response.json())
              .then(data => {
                setCachedData(data);
                updateDisplay(data);
              })
              .catch(error => console.error('Error fetching market data:', error));
          }
        }
    
        fetchMarketData();
        setInterval(fetchMarketData, 20000);
    
        function adjustMarketData() {
          for (const symbol in lastMarketData) {
            const randomDelta = (Math.random() * (0.0005 - 0.0001) + 0.0001) * (Math.random() < 0.5 ? -1 : 1);
            lastMarketData[symbol].mid += randomDelta;
            lastMarketData[symbol].bid = lastMarketData[symbol].mid - 0.0002;
            lastMarketData[symbol].ask = lastMarketData[symbol].mid + 0.0002;
            updateSymbolDisplay(symbol, lastMarketData[symbol]);
          }
        }
        setInterval(adjustMarketData, 3000);
    
        // -------------------------------
        // ApexCharts, Sparkline and Modal Code (unchanged)
        // -------------------------------
        let chartInstance;
        let currentSymbol = '';
        const TRADERMADE_API_KEY = @json($tradermadeApiKey);
    
        async function fetchHistoricalData(symbol, startDate, endDate, interval = 'daily', period = 1) {
          const apiKey = TRADERMADE_API_KEY;
          let url = `https://marketdata.tradermade.com/api/v1/timeseries?currency=${symbol}&api_key=${apiKey}&start_date=${startDate}&end_date=${endDate}&format=records&interval=${interval}`;
          if (interval !== 'daily') {
            url += `&period=${period}`;
          }
          try {
            const response = await fetch(url);
            if (!response.ok) throw new Error('Failed to fetch historical data.');
            return await response.json();
          } catch (error) {
            console.error("Error fetching historical data:", error);
            return null;
          }
        }
    
        function processHistoricalData(data) {
          const labels = [];
          const prices = [];
          if (!data.quotes || !Array.isArray(data.quotes)) {
            console.error("No quotes found in data:", data);
            return { labels, prices };
          }
          data.quotes.forEach(quote => {
            labels.push(quote.date);
            prices.push(quote.close);
          });
          return { labels, prices };
        }
    
        async function loadApexChart() {
          const modalChartContainer = document.querySelector("#modalChart");
          modalChartContainer.innerHTML = `
            <div class="text-center" style="padding: 100px 0;">
              <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p>Loading chart data...</p>
            </div>
          `;
    
          const interval = document.getElementById('intervalSelect').value;
          const now = new Date();
          let startDate, endDate, period = 1;
    
          if (interval === 'daily') {
            endDate = now.toISOString().slice(0, 10);
            const pastDate = new Date(now.getTime() - (10 * 24 * 60 * 60 * 1000));
            startDate = pastDate.toISOString().slice(0, 10);
          } else {
            endDate = now.toISOString().slice(0, 16).replace("T", "-");
            const pastDate = new Date(now.getTime() - (2 * 60 * 60 * 1000));
            startDate = pastDate.toISOString().slice(0, 16).replace("T", "-");
            if (interval === 'minute') {
              period = 15;
            }
          }
    
          const historicalData = await fetchHistoricalData(currentSymbol, startDate, endDate, interval, period);
          if (!historicalData) {
            alert("Failed to load chart data.");
            return;
          }
    
          const { labels, prices } = processHistoricalData(historicalData);
          const sLineArea = {
            chart: { height: 350, type: 'area', toolbar: { show: false } },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth' },
            series: [{ name: currentSymbol, data: prices }],
            xaxis: { type: 'datetime', categories: labels },
            tooltip: { x: { format: 'dd/MM/yy HH:mm' } }
          };
          modalChartContainer.innerHTML = "";
          if (chartInstance) {
            chartInstance.destroy();
          }
          chartInstance = new ApexCharts(modalChartContainer, sLineArea);
          chartInstance.render();
        }
    
        document.querySelectorAll('.card-clickable').forEach(card => {
          card.addEventListener('click', async function() {
            currentSymbol = this.getAttribute('data-symbol');
            document.getElementById('chartModalLabel').textContent = currentSymbol + " Chart Data";
            const modal = new bootstrap.Modal(document.getElementById('chartModal'));
            modal.show();
            await loadApexChart();
          });
        });
    
        document.getElementById('reloadChartBtn').addEventListener('click', loadApexChart);
    
        // Example: Sparkline charts
        function createSparklineChart(elementSelector) {
          const dummyData = Array.from({ length: 10 }, () =>
            parseFloat((Math.random() * (0.0010 - 0.0005) + 0.0005).toFixed(5))
          );
          const options = {
            chart: { type: 'line', sparkline: { enabled: true }, height: 50 },
            series: [{ data: dummyData }],
            stroke: { curve: 'smooth', width: 2 },
            tooltip: { enabled: false },
            grid: { show: false },
            xaxis: { labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { labels: { show: false } }
          };
          new ApexCharts(document.querySelector(elementSelector), options).render();
        }
    
        const forexRecordsData = @json($forexRecords);
        forexRecordsData.forEach(record => {
            const symbol = record.symbol.toUpperCase();
            createSparklineChart(`#sparkline-${symbol}`);
        });
    
        // -------------------------------
        // Assets and Profit Charts Code (unchanged)
        // -------------------------------
        var assetsData = @json($assetsRecords);
        var categories = assetsData.map(record => record.record_date);
        var values = assetsData.map(record => parseFloat(record.value));
    
        var sLineArea = {
            chart: { height: 160, type: 'area', toolbar: { show: false } },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth' },
            series: [{ name: 'Assets Over Time', data: values }],
            xaxis: { type: 'datetime', categories: categories },
            tooltip: { x: { format: 'dd/MM/yy HH:mm' } }
        };
        var chart = new ApexCharts(document.querySelector("#s-line-area"), sLineArea);
        chart.render();
    
        var profitData = @json($profitRecords);
        var profitCategories = profitData.map(record => record.record_date);
        var profitValues = profitData.map(record => parseFloat(record.value));
    
        var profitChartConfig = {
            chart: { height: 160, type: 'area', toolbar: { show: false } },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth' },
            series: [{ name: 'Trading Profit', data: profitValues }],
            xaxis: { type: 'datetime', categories: profitCategories },
            tooltip: { x: { format: 'dd/MM/yy' } }
        };
        var profitChart = new ApexCharts(document.querySelector("#profitChart"), profitChartConfig);
        profitChart.render();
        
        
        const withdrawalAmountInput = document.getElementById('withdrawalAmount');
        const feeInfoElement = document.getElementById('withdrawalFeeInfo');
        
        document.querySelector('form[action="{{ route('user.withdrawal') }}"]').addEventListener('submit', function (e) {
            const withdrawalAmount = document.getElementById('withdrawalAmount');
            const val = parseFloat(withdrawalAmount.value);
            if (!Number.isInteger(val)) {
                e.preventDefault();
                alert("Please enter a whole number amount (no decimals).");
            }
        });
    
        function updateFeeInfo() {
            let amount = parseFloat(withdrawalAmountInput.value);
            if (isNaN(amount) || amount <= 0) {
                feeInfoElement.textContent = "*Please enter a valid withdrawal amount. 3% fee will be applied, resulting in a net receipt of 0.00 USDT.";
                return;
            }
            // Calculate final amount after a 3% fee deduction (i.e., 97% of the amount)
            let receivedAmount = amount * 0.97;
            // Format the value to 2 decimal places.
            feeInfoElement.textContent = `*Please note: A 3% fee will be deducted, and you will receive a net total of ${receivedAmount.toFixed(2)} USDT upon successful withdrawal.`;
        }
    
        // Update fee info when the user types in a value.
        withdrawalAmountInput.addEventListener('input', updateFeeInfo);
        
        // Show confirmation modal with dynamic values
        document.getElementById('confirmTerminateBtn').addEventListener('click', function () {
            const tradingBalance = {{ $realTradingBalance ?? 0 }};
            const fee = tradingBalance * 0.20;
            const netAmount = tradingBalance - fee;
        
            const confirmationHTML = `
                <p>Please note: <strong>A 20% fee</strong> will be deducted from your real trading balance of 
                <span class="text-danger">${tradingBalance.toFixed(2)} USDT</span>.</p>
        
                <ul>
                    <li><strong>Fee:</strong> ${fee.toFixed(2)} USDT</li>
                    <li><strong>Net transferred to USDT Wallet:</strong> ${netAmount.toFixed(2)} USDT</li>
                </ul>
        
                <p class="alert alert-warning mt-4">Campaign bonus of <strong>{{ number_format($campaignTradingBonus, 2) }} USDT</strong> 
                is not eligible for withdrawal and will be forfeited.</p>
        
                <p class="text-danger fw-bold">
                    This action will <u>TERMINATE</u> your trading account. Any open orders will be closed and funds returned to your USDT Wallet.
                </p>
        
                <p class="alert alert-danger">Do you wish to proceed?</p>
            `;

        
            document.getElementById('transferConfirmationDetails').innerHTML = confirmationHTML;

            const confirmModal = new bootstrap.Modal(document.getElementById('tradingTransferConfirmModal'));
            confirmModal.show();
        });
        
        // Final submission with 2FA
        document.getElementById('finalizeTerminateBtn').addEventListener('click', function () {
            const confirmModalEl = document.getElementById('tradingTransferConfirmModal');
            const confirmModal = bootstrap.Modal.getInstance(confirmModalEl);
            confirmModal.hide();
        
            const otpInput = document.getElementById('otp');
            const hiddenOtp = document.getElementById('hiddenOtp');
        
            if (otpInput && hiddenOtp) {
                hiddenOtp.value = otpInput.value.trim();
            }
        
            document.getElementById('tradingTransferForm').submit();
        });

        
        document.querySelector('form.package-form').addEventListener('submit', function(e) {
            var topupInput = document.querySelector('input[name="topup_amount"]');
            var value = parseInt(topupInput.value, 10);
            if (value % 10 !== 0) {
                e.preventDefault();
                alert("Please enter a value in multiples of 10.");
            }
        });
        
        document.getElementById('activationForm').addEventListener('submit', function(e) {
            var activationInput = document.getElementById('activation_amount');
            var value = parseInt(activationInput.value, 10);
            if (value % 10 !== 0) {
              e.preventDefault();
              alert("Please enter a value in multiples of 10.");
            }
          });
          
        function navigateToOrder(symbol) {
            // Store the symbol in localStorage temporarily
            localStorage.setItem('navigateSymbol', symbol);
            // Navigate to the order_v2 page
            window.location.href = "{{ route('user.order_v2') }}";
        }
        
        document.addEventListener('DOMContentLoaded', () => {
          const copyBtn = document.getElementById('copyTrc20');
          const addressInput = document.getElementById('depositTRC20');
          if (!copyBtn || !addressInput) {
            console.warn('Copy button or input not found');
            return;
          }
        
          copyBtn.addEventListener('click', () => {
            const text = addressInput.value;
            console.log('Copy button clicked, text=', text);
        
            // Modern Clipboard API
            if (navigator.clipboard && window.isSecureContext) {
              navigator.clipboard.writeText(text).then(() => {
                console.log('Copied via Clipboard API');
                copyBtn.textContent = 'Copied!';
                setTimeout(() => (copyBtn.textContent = 'Copy'), 1500);
              }).catch(err => {
                console.error('Clipboard API failed, falling back', err);
                fallbackCopy(text);
              });
            } else {
              // Fallback for HTTP or older browsers
              fallbackCopy(text);
            }
          });
        
          function fallbackCopy(text) {
            console.log('Running fallback copy');
            const ta = document.createElement('textarea');
            ta.value = text;
            // keep off-screen
            ta.style.position = 'absolute';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            try {
              document.execCommand('copy');
              console.log('Copied via execCommand');
              copyBtn.textContent = 'Copied!';
              setTimeout(() => (copyBtn.textContent = 'Copy'), 1500);
            } catch (err) {
              console.error('Fallback copy failed', err);
              alert('Copy failed—please select and copy manually.');
            }
            document.body.removeChild(ta);
          }
        });
    </script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const depositBtn     = document.getElementById('depositButton');
            const depositModalEl = document.getElementById('depositModal');
            const bsDepositModal = new bootstrap.Modal(depositModalEl);
            const amountGroup    = depositModalEl.querySelector('.mt-3'); // the <div class="mt-3"> around amount input
            const submitBtn      = depositModalEl.querySelector('.modal-footer button[type="submit"]');
            
            depositBtn.addEventListener('click', async function () {
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                let data;
            
                try {
                  const res = await fetch("{{ route('user.generateWalletAddress') }}", {
                    method:      "POST",
                    credentials: "same-origin",
                    headers: {
                      "X-CSRF-TOKEN": token,
                      "Accept":       "application/json",
                      "Content-Type": "application/json"
                    },
                    body: JSON.stringify({})
                  });
            
                  // If the user is not permitted (403), treat it as "no new address" rather than an error
                  if (res.status === 403) {
                    data = {
                      wallet_address: null,
                      wallet_qr:      null
                    };
                  }
                  // Any other non-OK response should fall back silently
                  else if (!res.ok) {
                    throw new Error('Network response was not ok');
                  }
                  // On success, parse JSON
                  else {
                    data = await res.json();
                  }
                } catch (err) {
                  console.error(err);
                  // On unexpected errors, fall back to defaults
                  data = {
                    wallet_address: null,
                    wallet_qr:      null
                  };
                }
            
                // Populate address & QR if provided; otherwise leave the defaults
                if (data.wallet_address) {
                  document.getElementById('depositTRC20').value = data.wallet_address;
                }
                if (data.wallet_qr) {
                  document.getElementById('walletQR').src = data.wallet_qr;
                }
            
                // Show or hide amount input & submit button based on whether a wallet_address exists
                if (data.wallet_address) {
                  amountGroup.style.display = 'none';
                  submitBtn.style.display   = 'none';
                } else {
                  amountGroup.style.display = '';
                  submitBtn.style.display   = '';
                }
            
                // Finally, display the modal
                bsDepositModal.show();
              });
          
            const announcements = @json($announcements);
            let index = 0;
            
            function showNextAnnouncement() {
                if (index >= announcements.length) return;
            
                const ann = announcements[index];
                const modalEl = document.getElementById(`announcementModal${ann.id}`);
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            
                modalEl.addEventListener('hidden.bs.modal', () => {
                    index++;
                    showNextAnnouncement();
                });
            }
            
            showNextAnnouncement();
            
            const toastEl = document.getElementById('flashToast');
            if (toastEl) new bootstrap.Toast(toastEl, { delay: 6000 }).show();
            
            const pkgForm = document.querySelector('form.package-form');
            if (pkgForm) {
                pkgForm.addEventListener('submit', e => {
                    const val = parseFloat(pkgForm.querySelector('input[name="topup_amount"]').value);
                        if (!Number.isInteger(val) || val < 10 || val % 10 !== 0) {
                          e.preventDefault();
                          alert("Top-up must be an integer ≥ 10 and in multiples of 10.");
                    }
                });
            }
            
            const amountInput = document.getElementById('withdrawalAmount');
            const feeInfo = document.getElementById('withdrawalFeeInfo');
        
            amountInput.addEventListener('input', function () {
                const amount = parseFloat(amountInput.value);
                
                if (isNaN(amount) || amount < 10) {
                    feeInfo.textContent = `Minimum withdrawal amount is 10 USDT. Withdrawal will charge a fee of 3% or minimum 7 USDT (whichever is higher). You will receive a total of 0 USDT on completion.`;
                    return;
                }
        
                const fee = Math.max(amount * 0.03, 7);
                const finalAmount = amount - fee;
        
                feeInfo.textContent = `Withdrawal will charge a fee of 3% or minimum 7 USDT (whichever is higher). You will receive a total of ${finalAmount.toFixed(2)} USDT on completion.`;
            });
        });
    </script>
    
    <script>
        window.Pusher = Pusher;
    
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: '{{ env("PUSHER_APP_KEY") }}',
            cluster: '{{ env("PUSHER_APP_CLUSTER") }}',
            forceTLS: true,
            encrypted: true
        });

    
        console.log('✅ Laravel Echo initialized');
    </script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
        
            const startNY = new Date('2025-05-20T04:01:00Z').getTime(); // UTC equivalent
            const endNY   = new Date('2025-06-30T03:59:00Z').getTime(); // UTC equivalent
        
            function pad(num) {
                return num.toString().padStart(2, '0');
            }
        
            function updateCountdown() {
                const now = new Date().getTime();
                let label = '';
                let target = null;
                let text = '';
        
                if (now < startNY) {
                    label = "begin in:";
                    target = startNY;
                } else if (now >= startNY && now < endNY) {
                    label = "ending in:";
                    target = endNY;
                } else {
                    label = "has ended.";
                    text = "";
                    const creditNote = document.getElementById('bonusCreditNote');
                    const bonuscreditNote = document.getElementById('bonusNote');
        
                    if (creditNote) creditNote.remove();
                    if (bonuscreditNote) bonuscreditNote.remove();
                }
        
                if (target) {
                    const distance = target - now;
                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
                    text = `${days} DAY ${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
                }
        
                const walletLabel = document.getElementById("countdownTimer");
                const modalLabel = document.getElementById("modalCountdownTimer");
        
                if (walletLabel) walletLabel.innerHTML = label + ' ' + text;
                if (modalLabel) modalLabel.innerHTML = label + ' ' + text;
            }
        
            updateCountdown();
            setInterval(updateCountdown, 1000);
            
            const waitForEcho = () => {
                if (typeof window.Echo !== 'undefined') {
                    console.log('✅ Echo instance ready:', window.Echo);
        
                    window.Echo.channel('campaign-channel')
                    .listen('.balance.updated', (e) => {
                        console.log('📡 CampaignBalanceUpdated received:', e);
        
                        const newBalance = parseFloat(e.newBalance);
                        const max = 3000000;
                        const percentage = Math.min(100, Math.max(0, (newBalance / max) * 100));
        
                        const bar = document.getElementById('campaignProgressBar');
                        const text = document.getElementById('campaignProgressText');
        
                        if (bar) {
                            bar.style.width = `${percentage}%`;
                            bar.setAttribute('aria-valuenow', percentage.toFixed(2));
                        }
                        if (text) {
                            text.innerHTML = `<span class="text-danger">$${newBalance.toLocaleString()}</span> / $${max.toLocaleString()} Remaining`;
                        }

                    });
                } else {
                    console.warn('⏳ Waiting for Echo to be ready...');
                    setTimeout(waitForEcho, 300); // try again in 300ms
                }
            };
        
            waitForEcho();
        });
    </script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const activateForm = document.getElementById('activationForm');
        const packageForms = document.querySelectorAll('form.package-form');
    
        if (activateForm) {
            activateForm.addEventListener('submit', function (e) {
                const submitBtn = activateForm.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Processing...';
            });
        }
    
        packageForms.forEach(function (form) {
            form.addEventListener('submit', function (e) {
                const input = form.querySelector('input[name="topup_amount"]');
                const val = parseFloat(input.value);
                if (!Number.isInteger(val) || val < 10 || val % 10 !== 0) {
                    e.preventDefault();
                    alert("Top-up must be in multiples of 10 and at least 10.");
                    return;
                }
    
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Processing...';
            });
        });
    });
    </script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const steps = document.querySelectorAll('.modal-step');
        const nextStepBtn = document.querySelector('.step-1 .next-step');
        const processBtn = document.getElementById('processClaimBtn');
        const agreeCheckbox = document.getElementById('agreeTerms');
        const bonusAmount = parseFloat("{{ $megadropDeposit }}");
    
        function showStep(stepNumber) {
            steps.forEach(step => step.classList.add('d-none'));
            const target = document.querySelector(`.step-${stepNumber}`);
            if (target) target.classList.remove('d-none');
        }
    
        function simulateAirdrop(callback) {
            const statusEl = document.getElementById('airdropStatus');
            const messages = [
                "Clearing previous records...",
                "Checking eligibility...",
                "Calculating reward...",
                "Allocating bonus...",
                "Finalizing claim..."
            ];
            let index = 0;
    
            const interval = setInterval(() => {
                statusEl.textContent = messages[index];
                index++;
                if (index >= messages.length) {
                    clearInterval(interval);
                    setTimeout(callback, 2000);
                }
            }, 800);
        }
    
        function animateBonus(total) {
            const el = document.getElementById('bonusReveal');
            let current = 0;
            const steps = 30;
            const increment = total / steps;
    
            const interval = setInterval(() => {
                current += increment;
                if (current >= total) {
                    el.textContent = total.toFixed(2);
                    clearInterval(interval);
                } else {
                    el.textContent = current.toFixed(2);
                }
            }, 50);
        }
    
        nextStepBtn.addEventListener('click', () => {
            showStep(2);
            simulateAirdrop(() => {
                showStep(3);
            });
        });
    
        agreeCheckbox.addEventListener('change', function () {
            processBtn.classList.toggle('disabled', !this.checked);
        });
    
        processBtn.addEventListener('click', function () {
            if (agreeCheckbox.checked) {
                showStep(4);
                animateBonus(bonusAmount);
            }
        });
    
        const startClaimBtn = document.getElementById('startClaimBtn');
        startClaimBtn?.addEventListener('click', function () {
            showStep(1);
            const bonusModal = new bootstrap.Modal(document.getElementById('bonusClaimModal'));
            bonusModal.show();
        });
        
        document.getElementById('toggleMoreBtn')?.addEventListener('click', function () {
            const breakdown = document.getElementById('topupBreakdown');
            if (breakdown.classList.contains('d-none')) {
                breakdown.classList.remove('d-none');
                this.textContent = 'Details ▲';
            } else {
                breakdown.classList.add('d-none');
                this.textContent = 'Details ▼';
            }
        });
        
        document.getElementById('processClaimBtn').addEventListener('click', function () {
            if (agreeCheckbox.checked) {
                animateBonus(bonusAmount); // Optional: simulate animation first
        
                // Send POST request to /bonusclaim
                fetch("{{ route('user.claimCampaignBonus') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({})
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Step 4 must be visible *before* updating its elements
                        showStep(4);
        
                        // Use optional chaining to prevent null error
                        const topupEl = document.getElementById('topupAmount');
                        const boostEl = document.getElementById('campaignBoost');
                        
                        if (topupEl) topupEl.textContent = '$' + data.total_topup;
                        if (boostEl) boostEl.textContent = '$' + data.campaign_boost;

                        animateBonus(parseFloat(data.bonus_margin));
                    } else {
                        alert(data.error || 'An error occurred.');
                    }
                })
                .catch(err => {
                    console.error('Bonus claim failed:', err);
                    alert('Something went wrong while claiming your bonus.');
                });
            }
        });


    });
    </script>
    
    <script>
      function toggleTerms() {
        const box = document.getElementById('termsBox');
        box.classList.toggle('d-none');
      }
    </script>

    <script src="{{ asset('js/users/intro-steps.js') }}"></script>
    
    </x-slot:footerFiles>

</x-base-layout>