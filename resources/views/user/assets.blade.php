@extends('layouts.users.app')

@section('title', $title ?? 'User Assets')

@section('content')
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

  <!-- Custom style to make all table header titles the same size -->
  <style>
    .custom-table thead th {
      font-size: 1rem; /* Adjust the value as needed */
    }
  </style>

  <!-- Estimated Balance + PnL + Actions -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center flex-wrap">
        <!-- Left side: Balance and PnL -->
        <div class="mb-3 mb-md-0">
          <h2 class="mb-1">
            {{ number_format($total_balance, 2) }} <small>USDT</small>
          </h2>
          <small class="text-white">≈ ${{ number_format($total_balance, 2) }}</small>
          <div>
            <span class="text-danger">
              Today’s PnL (Estimate): - $58.59 (-17.54%)
            </span>
          </div>
        </div>
        <!-- Right side: Buttons -->
        <div>
          <button class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#depositModal">Deposit</button>
          <button class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#withdrawalModal">Withdrawal</button>
          <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#transferModal">Transfer</button>
        </div>
      </div>
    </div>
  </div>
  <!-- End Estimated Balance + PnL + Actions -->

  <!-- Sub-Wallets (Cash, Trading, Earning, Affiliates) -->
  <div class="row">
    <!-- USDT Wallet -->
    <div class="col-sm-6 col-md-3 mb-3">
      <div class="card text-center p-2 bg-panel">
        <div class="card-body">
          <h5 class="card-title text-white">USDT Wallet</h5>
          <p class="sub-wallet-amount mb-0 text-white">
            {{ number_format($wallets->cash_wallet, 2) }}
          </p>
        </div>
      </div>
    </div>
    <!-- Trading Wallet -->
    <div class="col-sm-6 col-md-3 mb-3">
      <div class="card text-center p-2 bg-panel">
        <div class="card-body">
          <h5 class="card-title text-white">Trading Wallet</h5>
          <p class="sub-wallet-amount mb-0 text-white">
            {{ number_format($wallets->trading_wallet, 2) }}
          </p>
        </div>
      </div>
    </div>
    <!-- Earning Wallet -->
    <div class="col-sm-6 col-md-3 mb-3">
      <div class="card text-center p-2 bg-panel">
        <div class="card-body">
          <h5 class="card-title text-white">Earning Wallet</h5>
          <p class="sub-wallet-amount mb-0 text-white">
            {{ number_format($wallets->earning_wallet, 2) }}
          </p>
        </div>
      </div>
    </div>
    <!-- Affiliates Wallet -->
    <div class="col-sm-6 col-md-3 mb-3">
      <div class="card text-center p-2 bg-panel">
        <div class="card-body">
          <h5 class="card-title text-white">Affiliates Wallet</h5>
          <p class="sub-wallet-amount mb-0 text-white">
            {{ number_format($wallets->affiliates_wallet, 2) }}
          </p>
        </div>
      </div>
    </div>
  </div>
  <!-- End Sub-Wallets -->

  <!-- Assets Section (Dark-Themed Card, Replacing the old Spot section) -->
<div class="card shadow-sm mb-4 bg-panel">
  <!-- Darker header row -->
  <div class="card-header d-flex justify-content-between align-items-center" style="background: #2F2F45;">
    <h5 class="mb-0 text-white">Assets</h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table custom-table table-sm mb-0">
        <thead>
          <tr>
            <th>Currency</th>
            <th>Amount</th>
            <th>Price/Cost</th>
            <th>24H Change</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @if($assets->count())
            @foreach($assets as $asset)
              <tr>
                <td>{{ $asset->currency }}</td>
                <td>{{ number_format($asset->amount, 2) }}</td>
                <!-- If you have price/cost or change information, display them here.
                     Otherwise, use placeholders such as "N/A" -->
                <td>N/A</td>
                <td>N/A</td>
                <td>{{ ucfirst($asset->status) }}</td>
              </tr>
            @endforeach
          @else
            <tr>
              <td colspan="5" class="text-center">No assets found.</td>
            </tr>
          @endif
        </tbody>
      </table>
    </div>
  </div>
</div>
<!-- End Assets Section -->

  <!-- (Optional) Recent Transactions & Payout Record Sections -->
  <div class="row">
    <div class="col-lg-6 mb-4">
      <!-- Recent Transactions Section -->
<div class="card recent-transactions shadow-sm mb-4">
  <div class="card-header">
    <h4 class="mb-0">Recent Transactions</h4>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table custom-table table-sm mb-0">
        <thead>
          <tr>
            <th>Transaction</th>
            <th>Txid</th>
            <th>Amount</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          @forelse($transactions as $transaction)
            <tr>
              <td>{{ $transaction->transaction_description }}</td>
              <td>{{ $transaction->txid }}</td>
              <td>{{ $transaction->transaction_amount }}</td>
              <td>{{ $transaction->created_at->format('Y-m-d') }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center">No transactions found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

    </div>
    
    <!-- Payout Record Section -->
    <div class="col-lg-6 mb-4">
      <div class="card payout-record shadow-sm">
        <div class="card-header">
          <h4 class="mb-0">Payout Record</h4>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table custom-table table-sm mb-0">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Amount</th>
                  <th>Date</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>#1001</td>
                  <td>+250</td>
                  <td>2025-01-25</td>
                  <td>Completed</td>
                </tr>
                <tr>
                  <td>#1002</td>
                  <td>+300</td>
                  <td>2025-01-23</td>
                  <td>Processing</td>
                </tr>
                <tr>
                  <td>#1003</td>
                  <td>+150</td>
                  <td>2025-01-21</td>
                  <td>Failed</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- End Recent Transactions & Payout Record Sections -->

  <!-- Deposit/Withdrawal Request Section -->
  <div class="card shadow-sm mb-4 bg-panel">
    <div class="card-header d-flex justify-content-between align-items-center" style="background: #2F2F45;">
      <h5 class="mb-0 text-white">Deposit/Withdrawal Request</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table custom-table table-sm mb-0">
          <thead>
            <tr>
              <th>Type</th>
              <th>Transaction ID</th>
              <th>Amount</th>
              <th>TRC20 Address</th>
              <th>Status</th>
              <th>Created At</th>
            </tr>
          </thead>
          <tbody>
            <!-- Display Deposit Requests -->
            @foreach($depositRequests as $deposit)
              <tr>
                <td>Deposit</td>
                <td>{{ $deposit->txid }}</td>
                <td>{{ number_format($deposit->amount, 2) }}</td>
                <td>{{ $deposit->trc20_address }}</td>
                <td>{{ $deposit->status }}</td>
                <td>{{ $deposit->created_at->format('Y-m-d H:i') }}</td>
              </tr>
            @endforeach
            <!-- Display Withdrawal Requests -->
            @foreach($withdrawalRequests as $withdrawal)
              <tr>
                <td>Withdrawal</td>
                <td>{{ $withdrawal->txid }}</td>
                <td>{{ number_format($withdrawal->amount, 2) }}</td>
                <td>{{ $withdrawal->trc20_address }}</td>
                <td>{{ $withdrawal->status }}</td>
                <td>{{ $withdrawal->created_at->format('Y-m-d H:i') }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <!-- End Deposit/Withdrawal Request Section -->

  <!-- Deposit Modal -->
  <div class="modal fade" id="depositModal" tabindex="-1" aria-labelledby="depositModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content bg-dark">
        <div class="modal-header">
          <h5 class="modal-title" id="depositModalLabel">Deposit</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="{{ route('user.deposit') }}" method="POST">
          @csrf
          <div class="modal-body">
            <p>Current USDT Wallet Balance: {{ number_format($wallets->cash_wallet, 2) }} USDT</p>
            <div class="mb-3">
              <label for="depositAmount" class="form-label">Amount</label>
              <input type="number" step="0.01" name="amount" class="form-control" id="depositAmount" required>
            </div>
            <div class="mb-3">
              <label for="depositTRC20" class="form-label">TRC20 Address</label>
              <!-- The sample TRC20 address is shown here and cannot be edited -->
              <input type="text" name="trc20_address" class="form-control" id="depositTRC20" value="TR9wHy8rF89a59gD3dmMPhrtPhtu6n5U5H" disabled>
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
      <div class="modal-content bg-dark">
        <div class="modal-header">
          <h5 class="modal-title" id="withdrawalModalLabel">Withdrawal</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="{{ route('user.withdrawal') }}" method="POST">
          @csrf
          <div class="modal-body">
            <p>Current USDT Wallet Balance: {{ number_format($wallets->cash_wallet, 2) }} USDT</p>
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
  
  <!-- Transfer Modal -->
    <div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content bg-dark">
          <div class="modal-header">
            <h5 class="modal-title" id="transferModalLabel">Transfer</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form action="{{ route('user.transfer') }}" method="POST">
            @csrf
            <div class="modal-body">
              <!-- Display current wallet balances -->
              <p>USDT Wallet: {{ number_format($wallets->cash_wallet, 2) }} USDT</p>
              <p>Trading Wallet: {{ number_format($wallets->trading_wallet, 2) }} USDT</p>
              <p>Earning Wallet: {{ number_format($wallets->earning_wallet, 2) }} USDT</p>
              <p>Affiliates Wallet: {{ number_format($wallets->affiliates_wallet, 2) }} USDT</p>
              <hr>
              <!-- Transfer Type Selection -->
              <div class="mb-3">
                <label for="transferType" class="form-label">Transfer Type</label>
                <select class="form-select" id="transferType" name="transfer_type" required>
                  <option value="">Select Transfer Type</option>
                  <option value="earning_to_cash">Earning Wallet → USDT Wallet</option>
                  <option value="affiliates_to_cash">Affiliates Wallet → USDT Wallet</option>
                  <option value="cash_to_trading">USDT Wallet → Trading Wallet</option>
                </select>
              </div>
              <!-- Transfer Amount -->
              <div class="mb-3">
                <label for="transferAmount" class="form-label">Amount</label>
                <input type="number" step="0.01" name="amount" class="form-control" id="transferAmount" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-primary">Submit Transfer</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    

</div>
@endsection
