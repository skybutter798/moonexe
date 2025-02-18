@extends('layouts.users.app')

@section('title', 'User Order')
<script>
    window.orderStoreRoute = "{{ route('user.order.store') }}";
</script>

@section('content')
  <h2 class="mb-3">Currency Exchange</h2>
  <p class="text-white-50 mb-4">
    Convert your USDT into other currencies before the gate closes or volume is reached.
  </p>

  <!-- Trading Table: List of Gates -->
  <div class="table-responsive mb-5">
    <table class="table custom-table text-white-50">
      <thead>
        <tr>
          <th>Currency Pair</th>
          <th>Merchant</th>
          <th>Rate (1 MYR = ? USDT)</th>
          <th>Total Volume</th>
          <th>Remaining Volume</th>
          <th>ROI</th>
          <th>Gate Closes</th>
          
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
  @foreach($pairs as $pair)
    @php
      // Build a pair name like "MYR / USD"
      $pairName = $pair->currency->c_name . ' / ' . $pair->pairCurrency->c_name;
      
      // Use the gate_time from the pair for the countdown.
      // (Assumes $pair->gate_time is a Carbon instance via casting.)
      $closingSeconds = ($pair->gate_time && $pair->gate_time->isFuture())
            ? $pair->gate_time->diffInSeconds(now())
            : 0;
      
      // Calculate the traded (bought) volume from buy orders.
      $tradedVolume = $pair->orders->whereNotNull('buy')->sum('receive');
      
      // Remaining volume = total volume minus traded volume.
      $remainingVolume = $pair->volume - $tradedVolume;
      
      // Calculate progress percentage (make sure to avoid division by zero).
      $progressPercent = $pair->volume > 0 ? ($tradedVolume / $pair->volume) * 100 : 0;
      
      // For previous rate, try to get the second-most recent buy order.
      $buyOrders = $pair->orders->whereNotNull('buy')->sortByDesc('created_at');
      $previousBuyOrder = $buyOrders->skip(1)->first();
      $previousRate = $previousBuyOrder ? number_format($previousBuyOrder->buy / $previousBuyOrder->receive, 3) : "No data found";
    @endphp
    <tr class="gateRow" data-closing-seconds="{{ $closingSeconds }}">
      <td>{{ $pairName }}</td>
      <td>MoonEXE</td>
      <td>{{ number_format($pair->rate, 2) }}</td>
      <td>{{ number_format($pair->volume, 2) }} {{ $pair->currency->c_name }}</td>
      <td>{{ number_format($remainingVolume, 2) }} {{ $pair->currency->c_name }}</td>
      <td><span class="roi">1%</span></td>
      <td><span class="gateCloseTimer"></span></td>
      
     
      <td>
        <button class="btn btn-primary btn-sm"
          data-bs-toggle="offcanvas" 
          data-bs-target="#offcanvasTrade"
          onclick='showTradeDetails(
              {!! json_encode($pairName) !!},
              {{ $pair->id }},
              {{ $pair->rate }},
              {{ $pair->volume }},
              {{ $remainingVolume }},
              {!! json_encode($previousRate) !!},
              {{ $progressPercent }}
          )'>
          Trade
        </button>
      </td>
    </tr>
  @endforeach
  @if($pairs->isEmpty())
    <tr>
      <td colspan="6" class="text-center">No trading pairs available.</td>
    </tr>
  @endif
</tbody>

    </table>
  </div>

  <!-- My Exchange Orders (with a filter) -->
  <div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h4 class="mb-0 col-8">My Exchange Orders</h4>
      <input type="text" id="orderSearch" class="form-control form-control-sm" placeholder="Filter Orders" onkeyup="filterOrders()">
    </div>
    <div class="table-responsive">
  <table class="table custom-table table-sm text-white-50" id="ordersTable">
    <thead>
      <tr>
        <th>Order ID</th>
        <th>Date</th>
        <th>Pair</th>
        <th>Amount (USDT)</th>
        <th>Rate</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse($userOrders as $order)
        <tr>
          <td>{{ $order->txid }}</td>
          <td>{{ $order->created_at->format('d M Y H:i') }}</td>
          <td>
            {{ $order->pair->currency->c_name }} / {{ $order->pair->pairCurrency->c_name }}
          </td>
          <td>
            @if($order->buy)
              {{ number_format($order->buy, 2) }} USDT
            @else
              {{ number_format($order->sell, 2) }} (Asset)
            @endif
          </td>
          <td>
            @if($order->buy)
              {{ number_format($order->buy / $order->receive, 3) }}
            @else
              {{ number_format($order->receive / $order->sell, 3) }}
            @endif
          </td>
          <td>{{ ucfirst($order->status) }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="text-center">No exchange orders found.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

  </div>

  <!-- Offcanvas for Trade Details -->
  <div class="offcanvas offcanvas-end offcanvas-dark" tabindex="-1" id="offcanvasTrade" aria-labelledby="offcanvasTradeLabel">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="offcanvasTradeLabel">Trade Details</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <!-- Trading Balance Display -->
      <div class="mb-3">
        <p>Your Trading Wallet Balance: <strong>${{ number_format($tradingBalance, 2) }}</strong></p>
      </div>
      <!-- Modern Trade Details Layout -->
      <div class="trade-header">Trade Overview</div>
      <div class="trade-info-card" id="tradeSummary">
        <h6>Pair: MYR/USD</h6>
        <p class="mb-0 text-white-50" style="font-size:0.9rem;">
          Rate: 1 unit = 4.5 USDT<br>
          Total Volume: 10,000 MYR<br>
          Remaining Volume: 8,250 MYR<br>
          <span id="tradeCountdown"></span>
        </p>
      </div>
      <div class="trade-info-card">
        <h6>Volume Progress</h6>
        <div class="progress" style="height: 10px;">
          <div class="progress-bar bg-info" role="progressbar" id="volumeProgressBar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
      </div>
      <div class="trade-info-card">
        <h6>Liquidity Overview</h6>
        <p class="mb-0 text-white-50 text-sm">
          Your Liquidity: ~4125.00 units locked<br>
          Protocol Fees: ~0.0%<br>
        </p>
      </div>

      <!-- Buy & Sell Tabs -->
      <ul class="nav nav-tabs mb-3" id="tradeTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="buy-tab" data-bs-toggle="tab" data-bs-target="#buyTabPane" type="button" role="tab" aria-controls="buyTabPane" aria-selected="true">
            Buy
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="sell-tab" data-bs-toggle="tab" data-bs-target="#sellTabPane" type="button" role="tab" aria-controls="sellTabPane" aria-selected="false">
            Sell
          </button>
        </li>
      </ul>
      <div class="tab-content mb-3" id="tradeTabsContent">
        <!-- BUY FORM -->
        <div class="tab-pane fade show active" id="buyTabPane" role="tabpanel" aria-labelledby="buy-tab">
          <form>
            <div class="mb-3">
              <label class="form-label">Amount in USDT</label>
              <input type="number" class="form-control" id="buyUsdtAmount" placeholder="e.g. 500">
            </div>
            <div class="mb-3">
              <label class="form-label">Estimated Receive (<span id="buyPairName">MYR/USD</span>)</label>
              <input type="text" class="form-control" id="buyEstimatedReceive" readonly>
            </div>
            <button type="button" class="btn btn-primary w-100" onclick="confirmTrade('buy')">Buy Now</button>
          </form>
        </div>
        <!-- SELL FORM -->
        <div class="tab-pane fade" id="sellTabPane" role="tabpanel" aria-labelledby="sell-tab">
          <form>
            <div class="mb-3">
              <label class="form-label">Amount in <span id="sellPairName">MYR/USD</span></label>
              <input type="number" class="form-control" id="sellAmount" placeholder="e.g. 100">
            </div>
            <div class="mb-3">
              <label class="form-label">Estimated Receive in USDT</label>
              <input type="text" class="form-control" id="sellEstimatedReceive" readonly>
            </div>
            <button type="button" class="btn btn-primary w-100" onclick="confirmTrade('sell')">Sell Now</button>
          </form>
        </div>
      </div>
      <!-- Chart Section -->
      <div class="trade-info-card">
        <h6>Live Price Chart</h6>
        <div class="chart-container">
          <canvas id="tradeChart"></canvas>
        </div>
      </div>
      <!-- Recent Trades Section -->
      <div class="trade-info-card">
        <h6>Recent Trades</h6>
        <ul class="list-unstyled recent-trades" id="recentTradesList">
          <!-- dynamically populated -->
        </ul>
      </div>
    </div>
  </div>
@endsection
