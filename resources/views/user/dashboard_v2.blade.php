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
              border: 1px solid #4d80b5;
              border-radius:0px;
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
        
        <!-- Estimated Balance + Actions -->
        <div class="estbalance mt-2 mb-2" style="background-image: url('/img/usdt.png'); background-repeat: no-repeat; background-position: left center;">
            <div class="row mb-4 mb-md-0 align-items-center">
                <div class="col-md-6">
                    <strong class="text-dark">Hi, {{ $user->name }}</strong>
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
                            <button id="depositButton" class="btn p-1 action-btn custom-btn mt-2" data-bs-toggle="modal" data-bs-target="#depositModal">
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
                </div>
            </div>
            
            @if($user->bonus)
            <!-- Trading Wallet (with bonus) -->
            <div class="col-12 col-md-6 mb-3">
                <div id="tradingWalletCard" class="card h-100 text-center p-2 position-relative assets" style="background-image: url('/img/trademargin.png'); background-repeat: no-repeat; background-position: left center;">
                    <button class="btn wallet-btn btn-sm" style="position: absolute; top: 10px; right: 10px; width: 25px; height: 25px; padding: 0; line-height: 1;" data-bs-toggle="modal" data-bs-target="#tradingTransferModal">
                        -
                    </button>
                    <div class="card-body d-flex flex-column">
                        <h5 class="text-custom">Trade Margin</h5>
                        <p class="sub-wallet-amount mb-0 value">
                            {{ number_format($wallets->trading_wallet, 2) }}
                            <br><small>≈ Open Order: ${{ number_format($pendingBuy, 2) }}</small>
                        </p>
                        <a href="{{ route('user.order') }}" class="btn wallet-btn btn-sm mt-2">Trade</a>
                    </div>
                </div>
            </div>
            <!-- Bonus Margin Card -->
            <div class="col-12 col-md-6 mb-3">
                <div id="bonusWalletCard" class="card h-100 text-center p-2 assets">
                    <div class="card-body d-flex flex-column">
                        <h5 class="text-custom">Bonus Margin</h5>
                        <p class="sub-wallet-amount mb-0 value">{{ number_format($wallets->bonus_wallet, 2) }}</p>
                        <a href="{{ route('user.order') }}" class="btn wallet-btn btn-sm mt-2">Trade</a>
                    </div>
                </div>
            </div>
            @else
            <!-- Trading Wallet (without bonus) -->
            <div class="col-12 col-md-6 mb-3">
                <div id="tradingWalletCard" class="card h-100 text-center p-2 position-relative assets" style="background-image: url('/img/trademargin.png'); background-repeat: no-repeat; background-position: left center;">
                    @if(is_null($user->bonus))
                    <div class="col-12 mt-2">
                        <button class="btn custom-btn btn-sm" style="position: absolute; top: 10px; left: 20px; height: 22px; padding: 5px; line-height: 1;" data-bs-toggle="modal" data-bs-target="#promotionModal">
                            Promotion Code
                        </button>
                    </div>
                    @endif
                    <div class="card-body d-flex flex-column">
                        <h5 class="text-custom">Trade Margin</h5>
                        <p class="sub-wallet-amount mb-1 value">
                            {{ number_format($wallets->trading_wallet, 2) }}
                        </p>
                        <div class="row mt-2">
                            <div class="col-12">
                                <a href="{{ route('user.order') }}" class="btn wallet-btn btn-sm">Trade</a>
                            </div>
                        </div>
                        <p class="openorder"><small class="text-danger">*Open Order: ${{ number_format($pendingBuy, 2) }}</small></p>
                    </div>
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
        <div class="row mb-4" id="forexCards">
          <h2 class="text-primary"><strong>Currency Data</strong></h2>

          @foreach($forexRecords as $record)
            <div class="col-md-4 mb-3 forex-card">
              <div class="card card-clickable" data-symbol="{{ $record->symbol }}">
                <div class="card-header d-flex align-items-center">
                  <img src="{{ asset('img/1x1/' . strtolower($record->symbol) . '.svg') }}" style="width:20px; margin-right:5px;">
                  {{ $record->symbol }}
                </div>
                <div class="card-body">
                  <div class="row">
                    <!-- Left Column -->
                    <div class="col-8 border-end text-center">
                      <p class="card-title forexprice" id="price-{{ $record->symbol }}">Price: Loading...</p>
                      <p class="card-text m-0">
                        <span class="text-success" id="bid-{{ $record->symbol }}">Bid: Loading...</span> | 
                        <span class="text-danger" id="ask-{{ $record->symbol }}">Ask: Loading...</span>
                      </p>
                      <p class="card-text"><small class="text-muted" id="time-{{ $record->symbol }}">Last updated: Loading...</small></p>
                    </div>
                    <!-- Right Column: Sparkline Chart Container -->
                    <div class="col-4">
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
        <div class="modal fade" id="depositModal" tabindex="-1" aria-labelledby="depositModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-white">
                    <div class="modal-header">
                        <h5 class="modal-title" id="depositModalLabel">Deposit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('user.deposit') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <p>USDT Balance: {{ number_format($wallets->cash_wallet, 2) }} USDT</p>
                            <div class="mb-3 text-center">
                                <img src="{{ asset('img/QRTrc20.png') }}" alt="TRC20 QR Code" class="img-fluid" style="max-width: 200px;">
                                <p class="mt-2">Scan to deposit</p>
                            </div>
                            <input type="text" name="trc20_address" class="form-control" id="depositTRC20" value="TTmLmBpNNd8npEG3uwPoWV1RHZynbVqGT6" disabled>
                            <div class="mt-3">
                                <input type="number" step="0.01" name="amount" class="form-control" id="depositAmount" required placeholder="Amount">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Submit Deposit</button>
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
                    <form action="{{ route('user.withdrawal') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <p>USDT Balance: {{ number_format($wallets->cash_wallet, 2) }} USDT</p>
                            <div class="mb-3">
                                <label for="withdrawalAmount" class="form-label">Amount</label>
                                <input type="number" step="0.01" name="amount" class="form-control" id="withdrawalAmount" required>
                            </div>
                            <div class="mb-3">
                                <label for="withdrawalTRC20" class="form-label">TRC20 Address</label>
                                <input type="text" name="trc20_address" class="form-control" id="withdrawalTRC20" placeholder="Enter your TRC20 address" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Submit Withdrawal</button>
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
                        <h5 class="modal-title" id="walletTransferModalLabel">Transfer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('user.transfer') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <p id="sourceWalletBalance"></p>
                            <input type="hidden" id="transferTypeHidden" name="transfer_type" value="">
                            <div class="mb-3">
                                <label for="transferAmountDynamic" class="form-label">Amount</label>
                                <input type="number" step="0.01" name="amount" class="form-control" id="transferAmountDynamic" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Submit Transfer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Trading Wallet Transfer Modal (with 20% fee) -->
        <div class="modal fade" id="tradingTransferModal" tabindex="-1" aria-labelledby="tradingTransferModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-white">
                    <div class="modal-header">
                        <h5 class="modal-title" id="tradingTransferModalLabel">Terminate Trade Margin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('user.tradingTransfer') }}" method="POST" class="trading-transfer-form">
                        @csrf
                        <div class="modal-body">
                            <p>Trading Balance: {{ number_format($wallets->trading_wallet, 2) }} USDT</p>
                            <p><strong>Fee Rate:</strong> 20%</p>
                            <p class="text-danger">Please note: For users registered under 100 days, a 20% fee will be deducted from the transferred amount to the USDT wallet.</p>
                            <div class="mb-3">
                                <label for="tradingTransferAmount" class="form-label">Amount</label>
                                <input type="number" step="0.01" name="amount" class="form-control" id="tradingTransferAmount" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Terminate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Direct Range Modal -->
        <div class="modal fade" id="packageModal" tabindex="-1" aria-labelledby="packageModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content bg-white">
                    <div class="modal-header">
                        <h5 class="modal-title" id="packageModalLabel">
                            {{ $hasPackageTransfer ? 'Top-up' : 'Activate Trade' }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if(!$user->package)
                            <form action="{{ route('user.buyPackage') }}" method="POST" id="activationForm">
                                @csrf
                                <div class="mb-3">
                                    <label for="activation_amount" class="form-label">Enter Activation Amount</label>
                                    <input type="number" name="activation_amount" id="activation_amount" class="form-control" placeholder="Enter amount" required>
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
                                        <input type="number" name="topup_amount" class="form-control" placeholder="Enter top-up amount" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Top-up</button>
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
                        <h5 class="modal-title" id="sendModalLabel">Send USDT to Downline</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('user.sendFunds') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <p>USDT Balance: {{ number_format($wallets->cash_wallet, 2) }} USDT</p>
                            <div class="mb-3">
                                <label for="downlineEmail" class="form-label">Downline Email</label>
                                <input type="email" name="downline_email" class="form-control" id="downlineEmail" placeholder="Enter downline email" required>
                            </div>
                            <div class="mb-3">
                                <label for="sendAmount" class="form-label">Amount</label>
                                <input type="number" step="0.01" name="amount" class="form-control" id="sendAmount" placeholder="Enter amount" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Send Funds</button>
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

    </div>
    
    <x-slot:footerFiles>
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
        setInterval(adjustMarketData, 1000);
    
        // -------------------------------
        // ApexCharts, Sparkline and Modal Code (unchanged)
        // -------------------------------
        let chartInstance;
        let currentSymbol = '';
    
        async function fetchHistoricalData(symbol, startDate, endDate, interval = 'daily', period = 1) {
          const apiKey = 'ubDGPHnQR8C0jfyTfzp4';
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
    
        const forexSymbols = ['EURUSD', 'GBPUSD', 'THBUSD', 'CADUSD', 'CHFUSD', 'AUDUSD', 'USDVND', 'MXNUSD', 'USDIDR'];
        forexSymbols.forEach(symbol => {
          createSparklineChart('#sparkline-' + symbol);
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
      </script>
    </x-slot:footerFiles>

</x-base-layout>