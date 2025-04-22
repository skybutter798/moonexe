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
          
          .mobile-break {
            display: block;
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
        
        
        .progress {
            background-color: #656565;
            margin-bottom: 10px;
            height: 30px;
            border-radius: 10px;
        }
        
        .progress:not(.progress-bar-stack) .progress-bar {
            border-radius: 5px;
            margin: 5px;
        }
        
        .progress-text_order {
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          display: flex;
          justify-content: center;
          align-items: center;
          pointer-events: none;
          color: white;
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
                  <span class="volume-usdt">—</span>
                  &nbsp;/&nbsp;
                  <span class="volume-base">{{ number_format($pair->volume, 4) }}</span>
                  {{$currency}}
                </p>
                
              </div>
              <div class="card-footer">
                <!-- Progress bar with larger size, striped effect -->
                <div class="row">
                  <div class="col-12">
                      
                    <div class="progress progress-lg position-relative">
                      <div class="progress-bar" role="progressbar"
                           style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                      </div>
                      <div class="progress-text position-absolute w-100 text-center text-white" style="top: 25%;font-weight: bold;">
                        0%
                      </div>
                    </div>

                  </div>
                </div>
                <!-- Trade button as a full-width block triggering the modal popout -->
                <div class="row">
                  <div class="col-12 text-center">
                    @if(is_null($package))
                      <a href="{{ route('user.dashboard_v2') }}" class="btn btn-primary w-100">Deposit</a>
                    @else
                      <button style="border-radius: 10px; width: 50%; background-color: #2e438b; padding: 5px;"
                              class="btn text-white"
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
    
    <!-- My Exchange Orders -->
    <h2 id="myExchangeOrders" class="mb-3 mt-5 text-primary"><strong>My Exchange Orders</strong></h2>
    
    <div class="row">
    @forelse($userOrders as $order)
      @php
        $pairCreatedAt = $order->pair->created_at;
        $pairEndTimestamp = $order->pair->created_at->copy()->addHours($order->pair->end_time)->getTimestamp() * 1000;
        $pairStartTimestamp = $pairCreatedAt->getTimestamp() * 1000;
        $baseCurrency = $order->pair->currency->c_name;
        $displayPair = "USDT / {$baseCurrency} / USDT";
      @endphp
    
        <div class="col-12 col-lg-6">
          <div class="card mb-4" data-order-id="{{ $order->id }}" data-pair-start="{{ $pairStartTimestamp }}" data-pair-end="{{ $pairEndTimestamp }}" data-order-status="{{ $order->status }}" data-order-time="{{ $order->time }}">
            <!-- Card Header 
            <div class="card-header d-flex justify-content-between align-items-center" data-pair="{{ $displayPair }}">
              <h5 class="mb-0 text-white">{{ $displayPair }}</h5>
            </div>-->
        
            <!-- Card Body -->
            <div class="card-body">
                <p><strong>Order ID:  </strong>{{ $order->txid }}</p>
                <p><strong>Date:  </strong><span class="">{{ $order->created_at->format('d M Y H:i') }}</span></p>
                <p><strong>Pair:  </strong><span class="">{{ $displayPair }}</span></p>
                <p><strong>Trade:  </strong>{{ number_format($order->buy, 4) }} USDT</p>
                <hr>
                <p class="est-roi" data-roi="{{ number_format($order->pair->rate, 2) }} / {{ number_format($order->est_rate, 2) }}">
                  <strong>Estimate / Actual Profit (%):  </strong> 
                  <span id="rateDisplay" class="mobile-break">
                    {{ number_format($order->pair->rate, 2) }} / <span class="badge badge-dark">{{ number_format($order->est_rate, 2) }}</span>
                  </span>
                </p>
                <p class="order-buy" data-buy="{{ $order->buy }}" data-est-rate="{{ $order->est_rate }}">
                  <strong class="mobile-break">Return Profit:</strong>
                  <!--<span style="font-size:0.9em">
                    {{ number_format($order->receive, 4) }} {{ $baseCurrency }}
                  </span> ➜ -->
                  <span id="buyDisplay">
                    <span class="computed-value badge badge-dark">
                      {{ number_format($order->buy * (1 + $order->est_rate/100), 4) }}
                    </span>
                  </span>
                </p>
                <div class="mb-2">
                  <strong class="text-dark">Matching Progress: </strong>
                  <div class="progress" style="position: relative; height: 30px; border-radius: 10px;">
                    <div class="progress-bar status-progress" role="progressbar" style="width: 0%;" aria-valuemin="0" aria-valuemax="100"></div>
                    <!-- New overlay element -->
                    <div class="progress-text_order">00:00:00</div>
                  </div>
                </div>

            </div>
        
            <!-- Card Footer -->
            <div class="card-footer">
              <div class="pairing-cell">
                <strong class="text-dark">Matching Rate: </strong>
                <span class="matching-rate btn" 
                      data-symbol="{{ str_replace(' ', '', str_replace('/', '', $order->pair->currency->c_name . $order->pair->pairCurrency->c_name)) }}"
                      data-base-rate="0.000000">
                  0.000000
                </span>
              </div>
            </div>
    
          </div>
        </div>
    @empty
      <div class="text-center">No exchange orders found.</div>
    @endforelse
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
                @if(! is_null($bonusBalance))
                  <small class="card-text mb-0 text-danger">
                    $<span id="bonusBalanceDisplay">Bonus margin: {{ number_format($bonusBalance, 4) }}</span>
                  </small>
                @endif

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
                      <div class="progress-bar progress-bar-animated" 
                           role="progressbar" 
                           id="volumeProgressBar" 
                           style="width: 0%;" 
                           aria-valuenow="0" 
                           aria-valuemin="0" 
                           aria-valuemax="100">
                      </div>
                      <span class="progress-text position-absolute w-100 text-center text-white" style="top: 25%;">0%</span>
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
          const reversedSymbols = ['LKR', 'VND', 'IDR', 'COP'];
          const now = Date.now();
        
          document.querySelectorAll('.gateRow').forEach(row => {
            // 1) Time countdown (unchanged)
            const gateClose = +row.dataset.gateClose;
            const diff = Math.floor((gateClose - now) / 1000);
            const countdownEl = row.querySelector('.gateCloseTimer');
            if (diff > 0) {
              const h = Math.floor(diff / 3600),
                    m = Math.floor((diff % 3600) / 60),
                    s = diff % 60;
              countdownEl.innerText =
                `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
            } else {
              countdownEl.innerText = 'Gate Closed';
              const btn = row.querySelector('button');
              if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.classList.add('btn-secondary');
                btn.classList.remove('btn-primary');
                btn.textContent = 'Gate Closed';
              }
            }
        
            // 2) Progress bar (unchanged)
            const totalVol     = parseFloat(row.dataset.totalVolume)   || 0;
            const remainingVol = parseFloat(row.dataset.remainingVolume) || 0;
            const pct = totalVol > 0
              ? ((totalVol - remainingVol) / totalVol * 100)
              : 0;
            const bar = row.querySelector('.progress-bar');
            const txt = row.querySelector('.progress-text');
            if (bar) {
              bar.style.width = pct.toFixed(2) + '%';
              bar.style.backgroundColor = pct >= 100 ? '#343a40' : '#2e438b';
            }
            if (txt) txt.innerText = pct.toFixed(2) + '%';
        
            // 3) Volume: compute Remaining USDT with reversed logic
            const rateEl = row.querySelector('.exchangeRate');
            const rate = rateEl ? parseFloat(rateEl.innerText) : 0;
        
            // determine if this pair is quoted “reversed”
            const symbol = rateEl ? rateEl.id.replace('price-', '') : '';
            const base = symbol.slice(0, 3);
            const isReversed = reversedSymbols.includes(base);
        
            // divide if reversed, otherwise multiply
            const remUSDT = isReversed
              ? remainingVol / rate
              : remainingVol * rate;
        
            // 3a) update the data- attribute
            row.dataset.remainingVolumeUsdt = remUSDT.toFixed(4);
        
            // 3b) write into the .volume-usdt span
            const usdtEl = row.querySelector('.volume-usdt');
            if (usdtEl) {
              usdtEl.innerText = remUSDT.toFixed(2) + ' USDT';
            }
            // (the .volume-base span stays static)
          });
        }
        
        updateCountdowns();
        setInterval(updateCountdowns, 1000);


    </script>
  </x-slot:footerFiles>
</x-base-layout>
