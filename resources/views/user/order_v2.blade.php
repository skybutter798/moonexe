<x-base-layout :scrollspy="false">
  <x-slot:pageTitle>
    Trading
  </x-slot:pageTitle>

  <x-slot:headerFiles>
    <!-- Chart.js is loaded from CDN -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Your trading script -->
    <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/laravel-echo/1.11.3/echo.iife.js"></script>
    <script src="{{ asset('js/users/trading.js') }}"></script>
    <script>
      window.orderStoreRoute = "{{ route('user.order.store') }}";
      window.orderClaimRoute = "{{ route('user.order.claim') }}";
      window.tradingBalance = {{ $tradingBalance }};
      window.hasOrderToday = {{ $hasOrderToday ? 'true' : 'false' }};
    </script>
    <style>
        
        body {
          background: #ffffff !important;
        }
        
        body.layout-dark {
          background: #060818 !important;
        }
        
        .bg-grey {
          background-color: #e0e6ed;
        }
        .custom-modal {
          max-width: 30%;
        }
        
        @media (max-width: 768px) {
          .custom-modal {
            max-width: 100%;
          }
        }
        
        .btn-secondary.disabled,
        .btn-secondary.btn[disabled],
        .btn-secondary:disabled {
            background-color: grey;
            border-color: transparent;
        }
        
        .badge-custom {
          padding: 0.3em 0.6em;
          font-size: 0.9em;
          border-radius: 0.25rem; 
        }
        
        .bg-secondary {
          background-color: #6c757d !important;
        }
        
        .page-link {
          color:#888ea8;
        }
        
        #spinnerContainer {
            position: absolute;
            padding: 50px;
            left: 33%;
            top: 33%;
            z-index: 2;
            border-radius: 25px;
        }
        
        .card .card-header {
            background-color: var(--bg-color);
            color:white;
            border-radius :0px;
        }
        
        .card {
            border-radius :0px;
            border: 1px solid #4d80b5;
        }
        
        .modal-header {
            background-color: var(--bg-color);
            color:white;
            border-radius :0px;
        }
        

    </style>
  </x-slot:headerFiles>
  
    <div id="tradePopup" class="position-fixed top-50 start-50 translate-middle bg-white text-dark p-4 rounded shadow-lg d-none" style="z-index: 9999; min-width: 300px; text-align: center;">
        <div id="tradePopupContent">
            <div id="tradePopupSpinner" class="spinner-border text-secondary mb-2" role="status"></div>
            <div id="tradePopupText">Processing...</div>
            <div id="tradePopupButtons" class="mt-3 d-none">
                <div id="tradeOrderDetails" class="mt-3 text-start small d-none"></div>
                <button id="goToWalletBtn" class="btn btn-dark me-2">Go to Record</button>
                <button id="stayHereBtn" class="btn btn-dark">Claim Next</button>
            </div>
        </div>
    </div>



  <div id="tradeNotification" style="position: fixed; bottom: 20px; background: #333; color: #fff; padding: 10px; border-radius: 5px; opacity: 0; transition: opacity 0.5s; z-index: 1;"></div>
  <div id="orderNotification" style="position: fixed; bottom: 20px; background: #333; color: #fff; padding: 10px; border-radius: 5px; opacity: 0; transition: opacity 0.5s; z-index: 1;"></div>
  
    <!-- Upcoming Cards -->
    @php
    // Retrieve active currencies (excluding id 1).
    $currencies = \App\Models\Currency::where('status', 1)
                        ->where('id', '>', 1)
                        ->get();
    
    // Current Malaysia time.
    $nowMYT = \Carbon\Carbon::now('Asia/Kuala_Lumpur');
    
    // Define a helper to calculate trigger time from the stored timezone value.
    $getTriggerTimestamp = function($currency) {
        $timezoneValue = (string) $currency->timezone;
        $hour = 0;
        $minute = 0;
        if (strpos($timezoneValue, '.') !== false) {
            $parts = explode('.', $timezoneValue);
            $hour = (int)$parts[0];
            if (strlen($parts[1]) == 2) {
                $minute = (int)$parts[1];
            } else {
                $minute = (int) round(((float)('0.' . $parts[1])) * 60);
            }
        } else {
            $hour = (int)$timezoneValue;
            $minute = 0;
        }
        // Create the trigger time for today.
        return \Carbon\Carbon::today('Asia/Kuala_Lumpur')->setTime($hour, $minute, 0);
    };
    
    // Filter currencies with a trigger time in the future and sort by trigger time.
    $upcomingCurrencies = $currencies->filter(function ($currency) use ($nowMYT, $getTriggerTimestamp) {
        return $getTriggerTimestamp($currency)->gt($nowMYT);
    })->sortBy(function ($currency) use ($getTriggerTimestamp) {
        return $getTriggerTimestamp($currency)->getTimestamp();
    });
    @endphp
    
    <h2 class="mb-3 mt-5 text-primary"><strong>Upcoming Pair</strong></h2>
    <div class="d-flex overflow-auto" style="white-space: nowrap;">
    @foreach($upcomingCurrencies as $currency)
    @php
        $triggerMYT = $getTriggerTimestamp($currency);
        $triggerTimestamp = $triggerMYT->getTimestamp() * 1000;
    @endphp
    
    <div class="card mb-3 me-3" style="min-width: 200px;"
         data-trigger-timestamp="{{ $triggerTimestamp }}">
      <div class="card-header py-2">
        <span class="h6">USDT/{{ $currency->c_name }}/USDT</span>
      </div>
      <div class="card-body py-2">
        <div class="">
            <p class="mb-1"><strong>Upcoming :</strong> 
              <span class="upcoming-countdown badge badge-dark">--:--:--</span>
            </p>
        </div>
      </div>
    </div>
    @endforeach
    </div>
    
  <div class="container py-4">
    <h2 class=" text-primary"><strong>Open Order</strong></h2>
    <p class="text-dark-50 mb-4">
      Make trade and exchange with currencies pairs before the gate closes or volume is reached.
    </p>
    
    <hr>

    @if($pairs->isEmpty())
      <div class="alert alert-info text-center">
        No trading pairs available.
      </div>
    @endif

    <!-- Trading Cards -->
    <div class="row">
      @foreach($pairs as $pair)
        @php
            $parts = explode('/', $pair->pairName);
            $parts = array_map('trim', $parts);
            $currency = $parts[0] ?? $pair->pairName;
            $displayPair = "USDT / {$currency} / USDT";
        @endphp
          <div class="col-12 col-sm-6 col-lg-3 mb-3">
            <!-- Pass all necessary data attributes for countdown and progress -->
            <div class="card gateRow"
               data-pair-id="{{ $pair->id }}"
               data-gate-close="{{ $pair->closingTimestamp }}"
               data-gate-start="{{ $pair->created_at->getTimestamp() * 1000 }}"
               data-gate-duration="{{ $pair->closingTimestamp - ($pair->created_at->getTimestamp() * 1000) }}"
               data-total-volume="{{ $pair->volume }}"
               data-remaining-volume="{{ $pair->remainingVolume }}">
            <div class="card-header">
              <div class="d-flex flex-column">
                <span class="h5">{{ $displayPair }}</span>
                <div class="d-flex align-items-center">
                    <span>Gatetime Remaining:</span>
                    <span class="gateCloseTimer text-white ms-2">00:00:00</span>
                  </div>
              </div>
            </div>
              <div class="card-body">
                <p class="mb-1">
                  <strong>Exchange Rate:</strong>
                  <span id="price-{{ str_replace(' ', '', str_replace('/', '', $pair->pairName)) }}" class="exchangeRate"></span>
                </p>
                <p class="mb-1">
                  <strong>Est.Profit:</strong>
                  <span class="roi" data-rate="{{ $pair->rate }}">
                    {{ number_format($pair->rate ?? 0, 4) }}%
                  </span>
                </p>
                
                <p class="mb-1 volume-info">
                  <strong>Volume:</strong>
                  <span class="volume-text">
                    {{ number_format($pair->remainingVolume, 4) }} / {{ number_format($pair->totalVolume, 4) }}
                  </span>
                  {{$currency}}
                </p>
                
              </div>
              <div class="card-footer">
                <!-- Progress bar with larger size, striped effect -->
                <div class="row mt-2">
                  <div class="col-12">
                      
                    <div class="progress br-30 progress-lg position-relative">
                      <div class="progress-bar progress-bar-striped" role="progressbar"
                           style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                      </div>
                      <div class="progress-text position-absolute w-100 text-center text-white" style="top: 0;font-weight: bold;">
                        0%
                      </div>
                    </div>

                  </div>
                </div>
                <!-- Trade button as a full-width block triggering the modal popout -->
                <div class="row mt-2">
                  <div class="col-12">
                    @if(is_null($package))
                      <a href="{{ route('user.dashboard') }}" class="btn btn-primary w-100">Deposit</a>
                    @else
                      <button class="btn btn-primary w-100"
                              data-bs-toggle="modal" 
                              data-bs-target="#tradeModal"
                              onclick="showTradeDetails('trade', '{{ $pair->pairName }}', '{{ $pair->id }}', this, {{ $pair->rawVolume }}, {{ $pair->remainingVolume }}, {{ $pair->min_rate }}, {{ $pair->progressPercent }}, {{ $pair->closingTimestamp }})">
                        Trade
                      </button>
                    @endif
                  </div>
                </div>
              </div>
            </div>
          </div>
        @endforeach
    </div>

    <hr>
    
    <!-- My Exchange Orders (unchanged) -->
    <div class="mb-4">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h4 class="mb-0 col-8">My Exchange Orders</h4>
      </div>
      <div class="table-responsive">
        <table class="table table-bordered" id="ordersTable">
          <thead class="bg-dark text-white">
            <tr>
              <th>Order ID</th>
              <th>Date</th>
              <th>Pair</th>
              <th>Trade</th>
              <th>Est. | Actual Profit (%)</th>
              <th>Return Profit</th>
              <th>Progress</th>
              <th>Status</th>
            </tr>
          </thead>
             <tbody>
                @forelse($userOrders as $order)
                  @php
                    $pairCreatedAt = $order->pair->created_at;
                    $pairEndTimestamp = $order->pair->created_at->copy()->addHours($order->pair->end_time)->getTimestamp() * 1000;
                    $pairStartTimestamp = $pairCreatedAt->getTimestamp() * 1000;
                    $baseMatching = $order->receive && $order->buy ? $order->receive / $order->buy : 0;
                  @endphp
                  <tr data-order-id="{{ $order->id }}" data-pair-start="{{ $pairStartTimestamp }}" data-pair-end="{{ $pairEndTimestamp }}" data-order-status="{{ $order->status }}">
                    <td>{{ $order->txid }}</td>
                    <td>{{ $order->created_at->format('d M Y H:i') }}</td>
                    <td>
                      @php
                        $baseCurrency = $order->pair->currency->c_name;
                        $displayPair = "USDT / {$baseCurrency} / USDT";
                      @endphp
                      {{ $displayPair }}
                    </td>
        
                    <td>{{ number_format($order->buy, 4) }} USDT</td>
                    
                    <td class="est-roi" data-roi="{{ number_format($order->pair->rate, 2) }} | {{ number_format($order->est_rate, 2) }}">
                        <div id="rateDisplay" class="align-self-center">
                            {{ number_format($order->pair->rate, 2) }} | {{ number_format($order->est_rate, 2) }}
                        </div>
                    </td>
                    
                    <td class="order-buy" 
                        data-buy="{{ $order->buy }}" 
                        data-est-rate="{{ $order->est_rate }}">
                        <span style="font-size:0.9em">{{ number_format($order->receive, 4) }} {{ $baseCurrency }}</span> ➜ 
                        <span id="buyDisplay">
                          <span class="computed-value badge badge-dark">
                              {{ number_format($order->buy * (1 + $order->est_rate/100), 4) }}
                          </span>
                        </span>
                    </td>
                    
                    <!-- Status column with progress bar -->
                    <td>
                      <div class="progress" style="height: 20px;border-radius: 16px;">
                        <div class="progress-bar status-progress" role="progressbar" style="width: 0%;" aria-valuemin="0" aria-valuemax="100"></div>
                      </div>
                    </td>
                    
                    <td class="pairing-cell">
                        <span class="matching-rate" data-symbol="{{ str_replace(' ', '', str_replace('/', '', $order->pair->currency->c_name . $order->pair->pairCurrency->c_name)) }}">
                          0.000000
                        </span>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="10" class="text-center">No exchange orders found.</td>
                  </tr>
                @endforelse
            </tbody>
        </table>
        
        <div class="mt-3">
            {{ $userOrders->links('vendor.pagination.bootstrap-5') }}
        </div>
      </div>
    </div>

    <!-- Modal Popout for Trade Details -->
    <div class="modal fade" id="tradeModal" tabindex="-1" aria-labelledby="tradeModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg custom-modal">
        <div class="modal-content bg-white">
        <div id="spinnerContainer"></div>
          <div class="modal-header py-4">
            <h5 class="modal-title text-white" id="tradeModalLabel">Trade Details</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body py-2">
            <!-- Trading Balance Card -->
            <div class="card mb-2 shadow-sm">
              <div class="card-body py-4">
                <h6 class="card-title mb-1">Your Trading Wallet Balance</h6>
                <h3 class="card-text mb-0 text-dark">$<span id="walletBalanceDisplay">{{ number_format($tradingBalance, 4) }}</span></h3>
              </div>
            </div>
            <!-- Trade Overview Card -->
            <div class="card mb-2 shadow-sm">
              <div class="card-body py-4">
                <h6 class="card-title mb-4">Trade Overview</h6>
                <div id="tradeSummary">
                  <!-- Dynamic content injected by JS -->
                </div>
                <!-- Volume Progress Bar & Combined Volume Display -->
                <div id="volumeDisplay">REMAIN VOL: </div>
                <div class="mt-4">
                  <div class="progress position-relative">
                      <div class="progress-bar bg-info progress-bar-striped progress-bar-animated" 
                           role="progressbar" 
                           id="volumeProgressBar" 
                           style="width: 0%;" 
                           aria-valuenow="0" 
                           aria-valuemin="0" 
                           aria-valuemax="100">
                      </div>
                      <span class="progress-text position-absolute w-100 text-center text-white" style="top: 0;">0%</span>
                    </div>

                  
                </div>
              </div>
            </div>
            <!-- Auto Trade Settings Card (Single Buy Form) -->
            <div class="card mb-2 shadow-sm">
              <div class="card-body py-4">
                <h6 class="card-title mb-2">Auto Trade Settings</h6>
                <div id="tradeTabsContainer">
                  <!-- Dynamic trade form will be injected here -->
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- End Modal Popout -->
    
  </div>

  <x-slot:footerFiles>
    <script>
        function updateCountdowns() {
          var now = new Date().getTime();
          var rows = document.querySelectorAll('.gateRow');
        
          rows.forEach(function(row) {
            // Retrieve data attributes
            var gateClose = parseInt(row.dataset.gateClose);
            var gateStart = parseInt(row.dataset.gateStart);
            var totalVol = parseFloat(row.dataset.totalVolume.toString().replace(/,/g, ''));
            var remainingVol = parseFloat(row.dataset.remainingVolume.toString().replace(/,/g, ''));
        
            // Update Countdown (time based)
            var countdownEl = row.querySelector('.gateCloseTimer');
            var diffInSeconds = Math.floor((gateClose - now) / 1000);
            if (diffInSeconds > 0) {
              var hours   = Math.floor(diffInSeconds / 3600);
              var minutes = Math.floor((diffInSeconds % 3600) / 60);
              var seconds = diffInSeconds % 60;
              countdownEl.innerText =
                ("0" + hours).slice(-2) + ':' +
                ("0" + minutes).slice(-2) + ':' +
                ("0" + seconds).slice(-2);
            } else {
              countdownEl.innerText = 'Gate Closed';
              var actionBtn = row.querySelector("button");
              if (actionBtn && !actionBtn.disabled) {
                actionBtn.disabled = true;
                actionBtn.classList.add('btn-secondary');
                actionBtn.classList.remove('btn-primary');
                actionBtn.textContent = 'Gate Closed';
              }
            }
        
            // Assume totalVol and remainingVol are already defined
            var progressContainer = row.querySelector('.progress');
            var progressBar = row.querySelector('.progress-bar');
            var progressText = row.querySelector('.progress-text');
            
            var progress = 0;
            if (totalVol > 0) {
              progress = ((totalVol - remainingVol) / totalVol) * 100;
            }
            if (progressBar) {
              progressBar.style.width = progress.toFixed(2) + "%";
              if (progress >= 100) {
                progressBar.style.backgroundColor = "#343a40";
              } else {
                const lightness = 80 - (progress / 100) * 30;
                progressBar.style.backgroundColor = `hsl(210, 100%, ${lightness}%)`;
              }
            }

        
            if (progressText) {
              progressText.innerText = progress.toFixed(2) + "%";
            }
            
            // Update the volume text live
            var volumeText = row.querySelector('.volume-text');
            if(volumeText) {
              volumeText.innerText = `${remainingVol.toFixed(2)} / ${totalVol.toFixed(2)}`;
            }
          });
        }
        
        updateCountdowns();
        setInterval(updateCountdowns, 1000);
    </script>
  </x-slot:footerFiles>
</x-base-layout>
