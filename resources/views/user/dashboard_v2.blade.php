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
                width: 60px;
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
            
            .card .card-body {
                padding: 24px 10px;
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

        <!-- Estimated Balance + Actions -->
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <!-- If user has a package -->
                <strong class="text-dark">Hi, {{ $user->name}}</strong>
                @if(isset($currentRange))
                    <p class="mb-1">
                        <span class="badge bg-primary text-white">{{ $currentRange->name }}</span>
                    </p>
                @else
                    <p class="mb-1">
                        <a href="#" class="badge bg-secondary text-white" data-bs-toggle="modal" data-bs-target="#packageModal">
                            Activate Trade Account
                        </a>
                    </p>
                @endif

                <h2 class="mb-1">
                    {{ number_format($total_balance, 2) }} <small>USDT</small>
                </h2>
            </div>

            <div class="col-md-6 text-md-end mt-4">
                <div class="row g-2 d-flex justify-content-center justify-content-md-end">
                    <!-- Deposit -->
                    <div class="col-3 col-md-auto text-center">
                        <button id="depositButton" class="btn btn-primary p-1 action-btn" data-bs-toggle="modal" data-bs-target="#depositModal">
                            <i class="bi bi-plus-circle fs-5"></i>
                        </button>
                        <div class="mt-1">Deposit</div>
                    </div>
        
                    <!-- Trade -->
                    <div class="col-3 col-md-auto text-center">
                        <a id="tradeButton" href="{{ route('user.order') }}" class="btn btn-primary p-1 action-btn _effect--ripple waves-effect waves-light">
                            <i class="bi bi-coin fs-5"></i>
                        </a>
                        <div class="mt-1">Trade</div>
                    </div>
        
                    <!-- Send -->
                    <div class="col-3 col-md-auto text-center">
                        <button type="button" class="btn btn-primary p-1 action-btn" data-bs-toggle="modal" data-bs-target="#sendModal">
                            <i class="bi bi-send fs-5"></i>
                        </button>
                        <div class="mt-1">Send</div>
                    </div>
        
                    <!-- Withdraw -->
                    <div class="col-3 col-md-auto text-center">
                        <button class="btn btn-primary p-1 action-btn" data-bs-toggle="modal" data-bs-target="#withdrawalModal">
                            <i class="bi bi-dash-circle fs-5"></i>
                        </button>
                        <div class="mt-1">Withdraw</div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Balance -->
          
        <hr>
          
        <!-- Sub-Wallets -->
        <div class="row">
            <!-- USDT Wallet Card -->
            <div class="col-6 col-md-3 mb-3">
                <div class="card h-100 text-center p-2">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">USDT Wallet</h5>
                        <p class="sub-wallet-amount mb-0 flex-grow-1">
                            {{ number_format($wallets->cash_wallet, 2) }}
                        </p>
                        <!-- Package Button -->
                        <button id="activateTradingAccount" class="btn btn-dark btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#packageModal">
                            {{ $hasPackageTransfer ? 'Top-up' : 'Activate' }}
                        </button>
                    </div>
                </div>
            </div>
            
            @if($user->bonus)
            <!-- Trading Wallet -->
            <div class="col-6 col-md-3 mb-3">
                <div id="tradingWalletCard" class="card h-100 text-center p-2 position-relative">
                    <!-- Top Right "-" Button -->
                    <button 
                        class="btn btn-dark btn-sm"
                        style="position: absolute; top: 10px; right: 10px; width: 25px; height: 25px; padding: 0; line-height: 1;"
                        data-bs-toggle="modal" 
                        data-bs-target="#tradingTransferModal">
                        -
                    </button>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Trade Margin</h5>
                        <p class="sub-wallet-amount mb-0 flex-grow-1">
                            {{ number_format($wallets->trading_wallet, 2) }}
                            <br><small>≈ Open Order: ${{ number_format($pendingBuy, 2) }}</small>
                        </p>
                        <!-- Trade Button at the Bottom -->
                        <a href="{{ route('user.order') }}" class="btn btn-primary btn-sm mt-2">
                            Trade
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Bonus Margin Card -->
            <div class="col-6 col-md-3 mb-3">
                <div id="bonusWalletCard" class="card h-100 text-center p-2">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Bonus Margin</h5>
                        <p class="sub-wallet-amount mb-0 flex-grow-1">
                            {{ number_format($wallets->bonus_wallet, 2) }}
                        </p>
                        <!-- Trade Button -->
                        <a href="{{ route('user.order') }}" class="btn btn-primary btn-sm mt-2">
                            Trade
                        </a>
                    </div>
                </div>
            </div>
            @else
            
            <!-- Trade Margin Card -->
            <div class="col-6 col-md-3 mb-3">
                <div id="tradingWalletCard" class="card h-100 text-center p-2 position-relative">
                    <!-- Top Right "-" Button remains -->
                    <button 
                        class="btn btn-dark btn-sm"
                        style="position: absolute; top: 10px; right: 10px; width: 22px; height: 22px; padding: 0; line-height: 1;"
                        data-bs-toggle="modal" 
                        data-bs-target="#tradingTransferModal">
                        -
                    </button>
                    @if(is_null($user->bonus))
                    <div class="col-12 mt-2">
                        <button class="btn btn-primary btn-sm"
                                style="position: absolute; top: 10px; left: 20px; height: 22px; padding: 5px; line-height: 1;"
                                data-bs-toggle="modal" 
                                data-bs-target="#promotionModal">
                            Promotion Code
                        </button>
                    </div>
                    @endif
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Trade Margin</h5>
                        
                        <p class="sub-wallet-amount mb-1 flex-grow-1">
                            {{ number_format($wallets->trading_wallet, 2) }}
                        </p>
                        <p><small class="text-danger">Open Order: ${{ number_format($pendingBuy, 2) }}</small></p>
                        <div class="row mt-2">
                            <div class="col-12">
                                <a href="{{ route('user.order') }}" class="btn btn-primary btn-sm w-100">
                                    Trade
                                </a>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
            @endif

            <!-- Earning Wallet -->
            <div class="col-6 col-md-3 mb-3">
                <div id="tradingWalletCard" class="card h-100 text-center p-2">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Trading Profit</h5>
                        <p class="sub-wallet-amount mb-0 flex-grow-1">
                            {{ number_format($wallets->earning_wallet, 2) }}
                        </p>
                        <!-- Earning Transfer Button -->
                        <button class="btn btn-primary btn-sm mt-2 wallet-transfer-btn _effect--ripple waves-effect waves-light" 
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
            <div class="col-6 col-md-3 mb-3">
                <div id="affiliatesWalletCard" class="card h-100 text-center p-2">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Affiliate Incentive</h5>
                        <p class="sub-wallet-amount mb-0 flex-grow-1">
                            {{ number_format($wallets->affiliates_wallet, 2) }}
                        </p>
                        <!-- Affiliates Transfer Button -->
                        <button class="btn btn-primary btn-sm mt-2 wallet-transfer-btn _effect--ripple waves-effect waves-light" 
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

        <!-- Forex Cards -->
        <div class="row mb-4" id="forexCards">
            <!-- EURUSD Card -->
            <div class="col-md-4 mb-3">
                <div class="card card-clickable" data-symbol="EURUSD">
                    <div class="card-header">
                        <img src="{{ asset('img/1x1/eu.svg') }}" style="width:20px; margin-right:5px;">
                        EURUSD
                    </div>
                    <div class="card-body">
                        <h5 class="card-title" id="price-EURUSD">Price: Loading...</h5>
                        <p class="card-text">
                            <span class="text-success" id="bid-EURUSD">Bid: Loading...</span> | 
                            <span class="text-danger" id="ask-EURUSD">Ask: Loading...</span>
                        </p>
                        <p class="card-text"><small class="text-muted" id="time-EURUSD">Last updated: Loading...</small></p>
                    </div>
                </div>
            </div>
        
            <!-- GBPUSD Card -->
            <div class="col-md-4 mb-3">
                <div class="card card-clickable" data-symbol="GBPUSD">
                    <div class="card-header">
                        <img src="{{ asset('img/1x1/gb.svg') }}" style="width:20px; margin-right:5px;">
                        GBPUSD
                    </div>
                    <div class="card-body">
                        <h5 class="card-title" id="price-GBPUSD">Price: Loading...</h5>
                        <p class="card-text">
                            <span class="text-success" id="bid-GBPUSD">Bid: Loading...</span> | 
                            <span class="text-danger" id="ask-GBPUSD">Ask: Loading...</span>
                        </p>
                        <p class="card-text"><small class="text-muted" id="time-GBPUSD">Last updated: Loading...</small></p>
                    </div>
                </div>
            </div>
        
            <!-- THBUSD Card -->
            <div class="col-md-4 mb-3">
                <div class="card card-clickable" data-symbol="THBUSD">
                    <div class="card-header">
                        <img src="{{ asset('img/1x1/th.svg') }}" style="width:20px; margin-right:5px;">
                        THBUSD
                    </div>
                    <div class="card-body">
                        <h5 class="card-title" id="price-THBUSD">Price: Loading...</h5>
                        <p class="card-text">
                            <span class="text-success" id="bid-THBUSD">Bid: Loading...</span> | 
                            <span class="text-danger" id="ask-THBUSD">Ask: Loading...</span>
                        </p>
                        <p class="card-text"><small class="text-muted" id="time-THBUSD">Last updated: Loading...</small></p>
                    </div>
                </div>
            </div>
        
            <!-- CADUSD Card -->
            <div class="col-md-4 mb-3">
                <div class="card card-clickable" data-symbol="CADUSD">
                    <div class="card-header">
                        <img src="{{ asset('img/1x1/us.svg') }}" style="width:20px; margin-right:5px;">
                        CADUSD
                    </div>
                    <div class="card-body">
                        <h5 class="card-title" id="price-CADUSD">Price: Loading...</h5>
                        <p class="card-text">
                            <span class="text-success" id="bid-CADUSD">Bid: Loading...</span> | 
                            <span class="text-danger" id="ask-CADUSD">Ask: Loading...</span>
                        </p>
                        <p class="card-text"><small class="text-muted" id="time-CADUSD">Last updated: Loading...</small></p>
                    </div>
                </div>
            </div>
        
            <!-- CHFUSD Card -->
            <div class="col-md-4 mb-3">
                <div class="card card-clickable" data-symbol="CHFUSD">
                    <div class="card-header">
                        <img src="{{ asset('img/1x1/us.svg') }}" style="width:20px; margin-right:5px;">
                        CHFUSD
                    </div>
                    <div class="card-body">
                        <h5 class="card-title" id="price-CHFUSD">Price: Loading...</h5>
                        <p class="card-text">
                            <span class="text-success" id="bid-CHFUSD">Bid: Loading...</span> | 
                            <span class="text-danger" id="ask-CHFUSD">Ask: Loading...</span>
                        </p>
                        <p class="card-text"><small class="text-muted" id="time-CHFUSD">Last updated: Loading...</small></p>
                    </div>
                </div>
            </div>
        
            <!-- AUDUSD Card -->
            <div class="col-md-4 mb-3">
                <div class="card card-clickable" data-symbol="AUDUSD">
                    <div class="card-header">
                        <img src="{{ asset('img/1x1/au.svg') }}" style="width:20px; margin-right:5px;">
                        AUDUSD
                    </div>
                    <div class="card-body">
                        <h5 class="card-title" id="price-AUDUSD">Price: Loading...</h5>
                        <p class="card-text">
                            <span class="text-success" id="bid-AUDUSD">Bid: Loading...</span> | 
                            <span class="text-danger" id="ask-AUDUSD">Ask: Loading...</span>
                        </p>
                        <p class="card-text"><small class="text-muted" id="time-AUDUSD">Last updated: Loading...</small></p>
                    </div>
                </div>
            </div>
            
            <!-- USDVND Card -->
            <div class="col-md-4 mb-3">
                <div class="card card-clickable" data-symbol="USDVND">
                    <div class="card-header">
                        <img src="{{ asset('img/1x1/vn.svg') }}" style="width:20px; margin-right:5px;">
                        USDVND
                    </div>
                    <div class="card-body">
                        <h5 class="card-title" id="price-USDVND">Price: Loading...</h5>
                        <p class="card-text">
                            <span class="text-success" id="bid-USDVND">Bid: Loading...</span> | 
                            <span class="text-danger" id="ask-USDVND">Ask: Loading...</span>
                        </p>
                        <p class="card-text"><small class="text-muted" id="time-USDVND">Last updated: Loading...</small></p>
                    </div>
                </div>
            </div>
            
            <!-- MXNUSD Card -->
            <div class="col-md-4 mb-3">
                <div class="card card-clickable" data-symbol="MXNUSD">
                    <div class="card-header">
                        <img src="{{ asset('img/1x1/mx.svg') }}" style="width:20px; margin-right:5px;">
                        MXNUSD
                    </div>
                    <div class="card-body">
                        <h5 class="card-title" id="price-MXNUSD">Price: Loading...</h5>
                        <p class="card-text">
                            <span class="text-success" id="bid-MXNUSD">Bid: Loading...</span> | 
                            <span class="text-danger" id="ask-MXNUSD">Ask: Loading...</span>
                        </p>
                        <p class="card-text"><small class="text-muted" id="time-MXNUSD">Last updated: Loading...</small></p>
                    </div>
                </div>
            </div>
            
            <!-- USDIDR Card -->
            <div class="col-md-4 mb-3">
                <div class="card card-clickable" data-symbol="USDIDR">
                    <div class="card-header">
                        <img src="{{ asset('img/1x1/id.svg') }}" style="width:20px; margin-right:5px;">
                        USDIDR
                    </div>
                    <div class="card-body">
                        <h5 class="card-title" id="price-USDIDR">Price: Loading...</h5>
                        <p class="card-text">
                            <span class="text-success" id="bid-USDIDR">Bid: Loading...</span> | 
                            <span class="text-danger" id="ask-USDIDR">Ask: Loading...</span>
                        </p>
                        <p class="card-text"><small class="text-muted" id="time-USDIDR">Last updated: Loading...</small></p>
                    </div>
                </div>
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
                        <!-- Chart Container -->
                        <div id="s-line-area"></div>
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
                        <!-- Title: Activate if first time, Top-up if already activated -->
                        <h5 class="modal-title" id="packageModalLabel">
                            {{ $hasPackageTransfer ? 'Top-up' : 'Activate Trade' }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if(!$user->package)
                            {{-- FIRST‑TIME ACTIVATION: One global amount input + display info for directrange IDs 1,2,3 --}}
                            <form action="{{ route('user.buyPackage') }}" method="POST" id="activationForm">
                                @csrf
                                <!-- Global activation amount input -->
                                <div class="mb-3">
                                    <label for="activation_amount" class="form-label">Enter Activation Amount</label>
                                    <input type="number" name="activation_amount" id="activation_amount" class="form-control" placeholder="Enter amount" required>
                                </div>
                                <!-- Display only directrange records with id 1, 2, and 3 -->
                                <div class="row">
                                    @foreach($directRanges->whereIn('id', [1,2,3]) as $range)
                                        <div class="col-md-4 mb-3">
                                            <div class="card h-100 shadow-sm">
                                                <div class="card-body text-center">
                                                    <h2 class="mb-3" style="font-size: 1.5rem; min-height: 3rem;">
                                                        {{ $range->name }}
                                                    </h2>
                                                    <div class="package-details text-left">
                                                        <p class="mb-1">
                                                            <strong>Range:</strong>
                                                            ${{ number_format($range->min, 2) }} -
                                                            @if($range->max)
                                                                ${{ number_format($range->max, 2) }}
                                                            @else
                                                                &infin;
                                                            @endif
                                                        </p>
                                                        <p>
                                                            <strong>ROI per trade:</strong>
                                                            <span class="badge bg-success">{{ ($range->percentage * 100) . '%' }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Activate</button>
                            </form>
                        @else
                            {{-- EXISTING USER: Upgrade Layout --}}
                            @php
                                $nextRange = null;
                                foreach($directRanges as $range) {
                                    if ($range->min > $currentRange->min) {
                                        $nextRange = $range;
                                        break;
                                    }
                                }
                            @endphp
                            <div class="current-package">
                                <div class="row align-items-center mb-4">
                                    <!-- Current Range Card -->
                                    <div class="col-md-5">
                                        <div class="card text-center shadow-sm">
                                            <div class="card-body">
                                                <h5 class="card-title">{{ $currentRange->name }}</h5>
                                                <p class="badge badge-success text-white">ROI per trade: <strong>{{ ($currentRange->percentage * 100) . '%' }}</strong></p>
                                                <p class="card-text">Range: {{ $currentRange->min }} -> {{ $currentRange->max }}</p>
                                            </div>
                                        </div>
                                    </div>
                            
                                    <!-- Arrow Indicator -->
                                    <div class="col-md-2 text-center">
                                        <!-- Desktop arrow-right -->
                                        <i class="bi bi-arrow-right d-none d-md-block" style="font-size: 2rem;"></i>
                                        <!-- Mobile arrow-down -->
                                        <i class="bi bi-arrow-down d-block d-md-none" style="font-size: 2rem;"></i>
                                    </div>
                            
                                    <!-- Next Range Card -->
                                    <div class="col-md-5">
                                        @if($nextRange)
                                            <div class="card text-center shadow-sm">
                                                <div class="card-body">
                                                    <h5 class="card-title">{{ $nextRange->name }}</h5>
                                                    <p class="badge badge-success text-white">ROI per trade: <strong>{{ ($nextRange->percentage * 100) . '%' }}</strong></p>
                                                    <p class="card-text">Range: {{ $nextRange->min }} -> {{ $nextRange->max }}</p>
                                                </div>
                                            </div>
                                        @else
                                            <div class="card text-center shadow-sm">
                                                <div class="card-body">
                                                    <h5 class="card-title">Highest Level</h5>
                                                    <p class="card-text">No upgrade available</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            
                                <!-- Upgrade Information and Input -->
                                @if($nextRange)
                                    <div class="text-center mb-3">
                                        <p>
                                            You are currently in the 
                                            <strong class="badge badge-success">{{ $currentRange->name }}</strong> range with a total of 
                                            ${{ number_format($rangeData['total'], 2) }} Group Trading Margin.
                                        </p>
                                        <p>
                                            To reach
                                            <strong class="badge badge-success">{{ $nextRange->name }}</strong> you need to upgrade a minimum of 
                                            ${{ number_format($nextRange->min - $rangeData['total'], 2) }}.
                                        </p>
                                    </div>

                                @endif
                            
                                {{-- Top-up Form --}}
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
            document.addEventListener("DOMContentLoaded", function() {
                // Wallet Transfer and Package Actions
                var currentWalletName = "";
                var currentWalletBalance = 0;
                
                const walletTransferButtons = document.querySelectorAll('.wallet-transfer-btn');
                walletTransferButtons.forEach(function(button) {
                    button.addEventListener('click', function() {
                        const transferType = this.getAttribute('data-transfer-type');
                        const walletBalance = this.getAttribute('data-wallet-balance');
                        const walletName = this.getAttribute('data-wallet-name');
                
                        // Store the current wallet name and balance for later use
                        currentWalletName = walletName;
                        currentWalletBalance = parseFloat(walletBalance);
                
                        // Update modal title and balance display
                        const modalLabel = document.getElementById('walletTransferModalLabel');
                        const balanceDisplay = document.getElementById('sourceWalletBalance');
                        if (modalLabel) modalLabel.textContent = `${walletName} → USDT Wallet`;
                        if (balanceDisplay) balanceDisplay.textContent = `${walletName} Balance: ${currentWalletBalance.toFixed(2)} USDT`;
                
                        // Set hidden transfer type input
                        const hiddenInput = document.getElementById('transferTypeHidden');
                        if (hiddenInput) hiddenInput.value = transferType;
                    });
                });
            
                var walletTransferForm = document.querySelector('#walletTransferModal form');
                if (walletTransferForm) {
                    walletTransferForm.addEventListener('submit', function(event) {
                        event.preventDefault();
                        var amountInput = document.getElementById('transferAmountDynamic');
                        var amount = parseFloat(amountInput.value);
                        if (isNaN(amount) || amount <= 0) {
                            alert('Please enter a valid amount.');
                            return;
                        }
                        var newBalance = currentWalletBalance - amount;
                        var confirmMessage = "You are about to transfer your " + currentWalletName + " wallet balance of $" +
                                             amount.toFixed(2) + " to Cash wallet. Your balance will reduce from $" +
                                             currentWalletBalance.toFixed(2) + " to $" + newBalance.toFixed(2) + ". Do you wish to proceed?";
                        if (confirm(confirmMessage)) {
                            walletTransferForm.submit();
                        }
                    });
                }
            
                var walletBalanceCash = parseFloat("{{ $wallets->cash_wallet }}");
                
                var packageForms = document.querySelectorAll('.package-form');
                packageForms.forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        event.preventDefault();
                        var packageName = form.getAttribute('data-package-name');
                        // This value could represent the minimum value for the range; however, the actual amount is what the user inputs.
                        var input = form.querySelector('input[name="activation_amount"], input[name="topup_amount"]');
                        var inputAmount = parseFloat(input.value);
                        var newBalance = walletBalanceCash - inputAmount;
                        var confirmMessage = "You are about to subscribe to our \"" + packageName + "\" range with an amount of $" + inputAmount.toFixed(2) +
                                             ". Following our Terms and Conditions, your wallet balance will be reduced from $" + walletBalanceCash.toFixed(2) +
                                             " to $" + newBalance.toFixed(2) + ". Do you wish to proceed?";
                        if (confirm(confirmMessage)) {
                            form.submit();
                        }
                    });
                });

            
                var tradingTransferForm = document.querySelector('.trading-transfer-form');
                if (tradingTransferForm) {
                    tradingTransferForm.addEventListener('submit', function(event) {
                        event.preventDefault();
                        var amountInput = document.getElementById('tradingTransferAmount');
                        var amount = parseFloat(amountInput.value);
                        if (isNaN(amount) || amount <= 0) {
                            alert('Please enter a valid amount.');
                            return;
                        }
                        var fee = amount * 0.2;
                        var confirmMessage = "You are about to transfer your trading balance of $" + amount.toFixed(2) +
                                             " to Cash wallet with a charges of 20% ($" + fee.toFixed(2) + "). Do you wish to proceed?";
                        if (confirm(confirmMessage)) {
                            tradingTransferForm.submit();
                        }
                    });
                }
            
                
                // Object to store the latest market data per symbol
                const lastMarketData = {};
                
                // Function to update the displayed data for a given symbol
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
                
                // Function to fetch fresh market data from the API
                function fetchMarketData() {
                  fetch('https://app.moonexe.com/api/market-data')
                    .then(response => response.json())
                    .then(data => {
                      // Update our cache with fresh data and update the display
                      for (const symbol in data) {
                        lastMarketData[symbol] = {
                          mid: parseFloat(data[symbol].mid),
                          bid: parseFloat(data[symbol].bid),
                          ask: parseFloat(data[symbol].ask)
                        };
                        updateSymbolDisplay(symbol, lastMarketData[symbol]);
                      }
                    })
                    .catch(error => console.error('Error fetching market data:', error));
                }
                
                // Function to simulate minor random adjustments to the cached data
                function adjustMarketData() {
                  for (const symbol in lastMarketData) {
                    // Generate a random delta between 0.0001 and 0.0005
                    const randomDelta = (Math.random() * (0.0005 - 0.0001) + 0.0001) * (Math.random() < 0.5 ? -1 : 1);
                    
                    // Adjust the mid price (you can also adjust bid and ask similarly if desired)
                    lastMarketData[symbol].mid += randomDelta;
                    
                    // Optionally, update bid and ask based on your own logic:
                    // e.g., bid = mid - 0.0002, ask = mid + 0.0002 (or with minor random tweaks)
                    lastMarketData[symbol].bid = lastMarketData[symbol].mid - 0.0002;
                    lastMarketData[symbol].ask = lastMarketData[symbol].mid + 0.0002;
                    
                    updateSymbolDisplay(symbol, lastMarketData[symbol]);
                  }
                }
                
                // Initial API fetch
                fetchMarketData();
                
                // Fetch fresh data every 20 seconds
                setInterval(fetchMarketData, 20000);
                
                // Simulate adjustments every second
                setInterval(adjustMarketData, 1000);
                
                // ApexCharts Integration for Chart Modal with Real Data
                let chartInstance;
                let currentSymbol = '';
            
                async function fetchHistoricalData(symbol, startDate, endDate, interval = 'daily', period = 1) {
                    const apiKey = 'ubDGPHnQR8C0jfyTfzp4'; // Use your API key if needed
                    let url = `https://marketdata.tradermade.com/api/v1/timeseries?currency=${symbol}&api_key=${apiKey}&start_date=${startDate}&end_date=${endDate}&format=records&interval=${interval}`;
                    if (interval !== 'daily') {
                        url += `&period=${period}`;
                    }
                    
                    try {
                        const response = await fetch(url);
                        if (!response.ok) {
                            throw new Error('Failed to fetch historical data.');
                        }
                        const data = await response.json();
                        return data;
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
                    const interval = document.getElementById('intervalSelect').value;
                    const now = new Date();
                    let startDate, endDate, period = 1;
                    
                    if (interval === 'daily') {
                        endDate = now.toISOString().slice(0, 10);
                        const pastDate = new Date(now.getTime() - (10 * 24 * 60 * 60 * 1000));
                        startDate = pastDate.toISOString().slice(0, 10);
                    } else {
                        endDate = now.toISOString().slice(0,16).replace("T", "-");
                        const pastDate = new Date(now.getTime() - (2 * 60 * 60 * 1000));
                        startDate = pastDate.toISOString().slice(0,16).replace("T", "-");
                        if(interval === 'minute'){
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
                        chart: {
                            height: 350,
                            type: 'area',
                            toolbar: { show: false }
                        },
                        dataLabels: { enabled: false },
                        stroke: { curve: 'smooth' },
                        series: [{ name: currentSymbol, data: prices }],
                        xaxis: { type: 'datetime', categories: labels },
                        tooltip: { x: { format: 'dd/MM/yy HH:mm' } }
                    };
                
                    if (chartInstance) {
                        chartInstance.destroy();
                    }
                
                    chartInstance = new ApexCharts(document.querySelector("#s-line-area"), sLineArea);
                    chartInstance.render();
                }
                document.querySelectorAll('.card-clickable').forEach(card => {
                    card.addEventListener('click', async function() {
                        currentSymbol = this.getAttribute('data-symbol');
                        document.getElementById('chartModalLabel').textContent = currentSymbol + " Chart Data";
                        await loadApexChart();
                        const modal = new bootstrap.Modal(document.getElementById('chartModal'));
                        modal.show();
                    });
                });
                document.getElementById('reloadChartBtn').addEventListener('click', loadApexChart);
            });
        </script>
        
        <!-- BS5 Intro Tour Initialization -->
        <script>
            document.addEventListener("DOMContentLoaded", function() {
            // Check if the tour has already been shown
            if (!localStorage.getItem('introTourShown')) {
                // Define the steps for your tour
                var steps = [
                    {
                        title: "Welcome to MoonExe",
                        content: "<p>Welcome to your Moonexe Dashboard! Here you can view your balances, track your trades, and manage your assets. Let us show you around.</p>"
                    },
                    {
                        id: "depositButton",
                        title: "<p><strong>Step One</strong></p>",
                        content: "<p>Make your first deposit into your USDT wallet.</p>"
                    },
                    {
                        id: "activateTradingAccount",
                        title: "<p><strong>Step Two</strong></p>",
                        content: "<p>Activate your trading account.</p>"
                    },
                    {
                        id: "tradeButton",
                        title: "<p><strong>Step Three</strong></p>",
                        content: "<p>Start your trade.</p>"
                    },
                    {
                        id: "tradingWalletCard",
                        title: "<p><strong>Step Four</strong></p>",
                        content: "<p>Claim your trading profit once an order closes</p>"
                    },
                    {
                        id: "affiliatesWalletCard",
                        title: "<p><strong>Step Five</strong></p>",
                        content: "<p>Grow more revenue with your affiliate incentive!</p>"
                    }
                ];
                
                // Initialize the tour
                var tour = new Tour(steps, {
                    onShowStep: function(step) {
                        if (step.id) {
                            var targetElem = document.getElementById(step.id);
                            if (targetElem) {
                                targetElem.classList.add('tour-active-element');
                            }
                        }
                    },
                    onHideStep: function(step) {
                        if (step.id) {
                            var targetElem = document.getElementById(step.id);
                            if (targetElem) {
                                targetElem.classList.remove('tour-active-element');
                            }
                        }
                    }
                });
            
                tour.show();
                
                // Set the flag so that the tour isn't shown again in this login/session
                localStorage.setItem('introTourShown', 'true');
            }
        });
        </script>
    </x-slot:footerFiles>
</x-base-layout>
