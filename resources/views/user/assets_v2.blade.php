<x-base-layout :scrollspy="false">
  <x-slot:pageTitle>
    Assets
  </x-slot:pageTitle>

  <x-slot:headerFiles>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
      body {
        background-color: white;
      }
      .table-title {
        background-color: #4d80b5;
        color: #fff;
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
        margin-bottom: 0;
      }
      .custom-table {
        font-size: 0.9rem;
      }
      .custom-table thead th {
        white-space: nowrap;
      }

      .wallet-card .card-title {
        font-size: 0.8rem;
        margin-bottom: 0.25rem;
      }
      .wallet-card .sub-wallet-amount {
        font-size: 1rem;
        font-weight: bold;
      }
      .page-link {
        color: #888ea8;
      }
      table thead tr {
         white-space: nowrap;
         background-color:#4d80b5;
      }
      
      .table:not(.dataTable).table-bordered thead tr th {
        border-top-left-radius: 0px;
      }
      
      .table:not(.dataTable) thead tr th:last-child {
        border-top-right-radius: 0px;
      }
      
      .bg-primary {
          background-color:#4d80b5;
      }
      
      .nav-tabs {
          gap: 0.1rem;
      }
      
      .nav-tabs .nav-link {
        background-color: #dddddd;
        color: dark;
      }
    
      /* Active tab style */
      .nav-tabs .nav-link.active {
        background-color: #4d80b5!important;
        color:white;
      }
      
      @media (max-width: 576px) {
      .nav-tabs {
        flex-wrap: nowrap;
        overflow-x: auto;
      }
      .nav-tabs .nav-item {
        flex: 0 0 auto;
      }
      
      .card {
          border-radius:0px;
      }
      
      .card .card-body {
          padding: 10px 10px;
      }
      
      .card .card-header {
          background-color:#4d80b5;
          color:white;
          padding: 10px;
          border-top-left-radius: 0px;
          border-top-right-radius: 0px;
      }
      
      .card .card-value {
        padding: 8px 60px;
        background-color: #e1e1e1;
        color: black;
        border-radius: 10px;
        font-weight: bolder;
      }
      
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
    
    <!-- Title for Assets Section -->
    <h2 class="mb-3 text-primary"><strong>Assets</strong></h4>

    <!-- Wallet Cards Section -->
    <div class="row mb-4 wallet-card">
        <!-- USDT Wallet -->
        <div class="col-12 col-md-3 mb-3">
            <div class="card h-100">
                <h6 class="card-header">USDT Wallet</h6>
                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                    <p class="mb-0 text-center">
                        <strong>Balance</strong> 
                        <div class="card-value border-radius-25px">{{ number_format($wallets->cash_wallet, 4) }}</div>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Trade Margin -->
        <div class="col-12 col-md-3 mb-3">
            <div class="card h-100">
                <h6 class="card-header">Trade Margin</h6>
                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                    <p class="mb-0 text-center">
                        <strong>Balance</strong> 
                        <div class="card-value border-radius-25px">{{ number_format($wallets->trading_wallet, 4) }}</div>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Trading Profit -->
        <div class="col-12 col-md-3 mb-3">
            <div class="card h-100">
                <h6 class="card-header">Trading Profit</h6>
                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                    <p class="mb-0 text-center">
                        <strong>Balance</strong> 
                        <div class="card-value border-radius-25px">{{ number_format($wallets->earning_wallet, 4) }}</div>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Affiliates -->
        <div class="col-12 col-md-3 mb-3">
            <div class="card h-100">
                <h6 class="card-header">Affiliates</h6>
                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                    <p class="mb-0 text-center">
                        <strong>Balance</strong> 
                        <div class="card-value border-radius-25px">{{ number_format($wallets->affiliates_wallet, 4) }}</div>
                    </p>
                </div>
            </div>
        </div>


    </div>


    <!-- Tabs Navigation for Record Types -->
    <ul class="nav nav-tabs mb-0" role="tablist">
      <li class="nav-item" role="presentation">
        <a class="nav-link active" data-bs-toggle="tab" href="#tradingTab" role="tab" aria-controls="tradingTab" aria-selected="true">Trading Record</a>
      </li>
      <!-- Change the existing payout tab label -->
      <li class="nav-item" role="presentation">
        <a class="nav-link" data-bs-toggle="tab" href="#payoutTab" role="tab" aria-controls="payoutTab" aria-selected="false">Affiliates Payout</a>
      </li>
      <!-- New Direct Payout tab -->
      <li class="nav-item" role="presentation">
        <a class="nav-link" data-bs-toggle="tab" href="#directPayoutTab" role="tab" aria-controls="directPayoutTab" aria-selected="false">Direct Payout</a>
      </li>
      <li class="nav-item" role="presentation">
        <a class="nav-link" data-bs-toggle="tab" href="#transactionTab" role="tab" aria-controls="transactionTab" aria-selected="false">Recent Transactions</a>
      </li>
      <li class="nav-item" role="presentation">
        <a class="nav-link" data-bs-toggle="tab" href="#depositWithdrawalTab" role="tab" aria-controls="depositWithdrawalTab" aria-selected="false">Deposit/Withdrawal Request</a>
      </li>
    </ul>


    <!-- Tabs Content -->
    <div class="tab-content">
      <!-- Trading Record Tab -->
      <div class="tab-pane fade show active" id="tradingTab" role="tabpanel">
        <div class="mb-0">
          <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
              <thead class="text-white">
                <tr>
                  <th>Order Pair</th>
                  <th>Txid</th>
                  <th>Total Payout</th>
                  <th>Actual Earning</th>
                  <th>Spread Profit</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                @forelse($roiRecords as $roi)
                  <tr>
                    <td>{{ $roi->cname }}</td>
                    <td>{{ $roi->txid }}</td>
                    <td>{{ number_format($roi->total, 4) }}</td>
                    <td>{{ isset($roi->actual) ? number_format($roi->actual, 4) : '-' }}</td>
                    <td> @if(isset($roi->actual) && $roi->total > 0) {{ number_format(($roi->actual / $roi->total) * 100, 2) }}% @else - @endif </td>
                    <td>{{ $roi->created_at ? \Carbon\Carbon::parse($roi->created_at)->format('Y-m-d H:i') : '-' }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center">No Trading records found.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
            <!-- Pagination Links -->
            <div class="mt-3">
              {{ $roiRecords->appends(['active_tab' => 'trading'])->links('vendor.pagination.bootstrap-5') }}
            </div>
          </div>
        </div>
      </div>

      <!-- Affiliates Payout Tab -->
        <div class="tab-pane fade" id="payoutTab" role="tabpanel">
          <div class="mb-4">
            <div class="table-responsive">
              <table class="table table-bordered table-sm mb-0">
                <thead class="bg-primary text-white">
                  <tr>
                    <th>Order ID</th>
                    <th>Total Payout</th>
                    <th>Actual Earning</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($payoutRecords as $payout)
                    <tr>
                      <td>
                        <a href="#"
                           class="payout-detail-link"
                           data-bs-toggle="modal"
                           data-bs-target="#payoutDetailModal"
                           {{-- Since these are non-direct payouts, you can use the regular details --}}
                           data-payout-type="{{ $payout->type }}"
                           data-txid="{{ $payout->txid }}"
                           data-buy="{{ $payout->buy }}"
                           data-earning="{{ $payout->earning }}"
                           data-actual="{{ $payout->total }}"
                           data-profit-sharing="{{ $payout->profit_sharing }}"
                           data-own-order="false"
                           data-wallet="{{ $payout->wallet }}"
                        >
                          {{ $payout->txid }}
                        </a>
                      </td>
                      <td>{{ number_format($payout->total, 4) }}</td>
                      <td>{{ isset($payout->actual) ? number_format($payout->actual, 4) : '-' }}</td>
                      <td>Affiliates</td>
                      <td>
                        @if($payout->status == 1)
                          <span class="badge bg-success">Completed</span>
                        @else
                          <span class="badge bg-danger">Failed</span>
                        @endif
                      </td>
                      <td>{{ $payout->created_at ? \Carbon\Carbon::parse($payout->created_at)->format('Y-m-d H:i') : '-' }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="6" class="text-center">No affiliates payout records found.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
              <div class="mt-3">
                {{ $payoutRecords->appends(['active_tab' => 'payout'])->links('vendor.pagination.bootstrap-5') }}
              </div>
            </div>
          </div>
        </div>
        
        <!-- Direct Payout Tab -->
        <div class="tab-pane fade" id="directPayoutTab" role="tabpanel">
          <div class="mb-4">
            <div class="table-responsive">
              <table class="table table-bordered table-sm mb-0">
                <thead class="bg-primary text-white">
                  <tr>
                    <th>Topup ID</th>
                    <th>Total Payout</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($directPayoutRecords as $payout)
                    <tr>
                      <td>
                        <a href="#"
                           class="payout-detail-link"
                           data-bs-toggle="modal"
                           data-bs-target="#payoutDetailModal"
                           data-payout-type="direct"
                           data-deposit-txid="{{ $payout->deposit_txid }}"
                           data-deposit-amount="{{ $payout->deposit_amount }}"
                           data-direct-percentage="{{ $payout->direct_percentage }}"
                           data-total-payout="{{ $payout->total }}"
                        >
                          {{ $payout->deposit_txid }}
                        </a>
                      </td>
                      <td>{{ number_format($payout->total, 4) }}</td>
                      <td>Direct</td>
                      <td>
                        @if($payout->status == 1)
                          <span class="badge bg-success">Completed</span>
                        @else
                          <span class="badge bg-danger">Failed</span>
                        @endif
                      </td>
                      <td>{{ $payout->created_at ? \Carbon\Carbon::parse($payout->created_at)->format('Y-m-d H:i') : '-' }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="5" class="text-center">No direct payout records found.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
              <div class="mt-3">
                {{ $directPayoutRecords->appends(['active_tab' => 'direct_payout'])->links('vendor.pagination.bootstrap-5') }}
              </div>
            </div>
          </div>
        </div>

      <!-- Recent Transactions Tab -->
      <div class="tab-pane fade" id="transactionTab" role="tabpanel">
        <div class="mb-4">
          <div class="table-responsive">
            <table class="table table-bordered table-sm custom-table mb-0">
              <thead class="bg-primary text-white">
                <tr>
                  <th>Order ID</th>
                  <th>Remark</th>
                  <th>Amount</th>
                  <th>Type</th>
                  <th>Remark</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                @forelse($transactions as $transaction)
                  <tr>
                    <td>{{ $transaction->txid }}</td>
                    <td>{{ $transaction->transaction_description }}</td>
                    <td>{{ $transaction->transaction_amount }}</td>
                    <td>
                      @if(isset($transaction->type))
                        @if($transaction->type == 'Deposit')
                          <span class="badge bg-success">Deposit</span>
                        @elseif($transaction->type == 'Withdrawal')
                          <span class="badge bg-danger">Withdraw</span>
                        @elseif($transaction->type == 'Transfer')
                          <span class="badge bg-info">Transfer</span>
                        @else
                          <span class="badge bg-secondary">{{ $transaction->type }}</span>
                        @endif
                      @else
                        <span class="badge bg-secondary">N/A</span>
                      @endif
                    </td>
                    <td>{{ $transaction->remark }}</td>
                    <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center">No transactions found.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Deposit/Withdrawal Request Tab -->
      <div class="tab-pane fade" id="depositWithdrawalTab" role="tabpanel">
        <div class="mb-4">
          <div class="table-responsive">
            <table class="table table-bordered table-sm custom-table mb-0">
              <thead class="bg-primary text-white">
                <tr>
                  <th>Order ID</th>
                  <th>Type</th>
                  <th>Amount</th>
                  <th>TRX Gas</th>
                  <th>TRC20 Address</th>
                  <th>Status</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <!-- Deposit Requests -->
                @foreach($depositRequests as $deposit)
                  <tr>
                    <td>{{ $deposit->txid }}</td>
                    <td>Deposit</td>
                    <td>{{ number_format($deposit->amount, 4) }}</td>
                    <td>{{ number_format($deposit->fee, 4) }}</td>
                    <td>{{ $deposit->trc20_address }}</td>
                    <td>
                      @if($deposit->status == 'Pending')
                        <span class="badge bg-success">Processing</span>
                      @elseif($deposit->status == 'Completed')
                        <span class="badge bg-primary">Completed</span>
                      @elseif($deposit->status == 'Rejected')
                        <span class="badge bg-danger">Rejected</span>
                      @else
                        <span class="badge bg-secondary">{{ $deposit->status }}</span>
                      @endif
                    </td>
                    <td>{{ $deposit->created_at->format('Y-m-d H:i') }}</td>
                  </tr>
                @endforeach
                <!-- Withdrawal Requests -->
                @foreach($withdrawalRequests as $withdrawal)
                  <tr>
                    <td>{{ $withdrawal->txid }}</td>
                    <td>Withdrawal</td>
                    <td>{{ number_format($withdrawal->amount, 4) }}</td>
                    <td>{{ $withdrawal->trc20_address }}</td>
                    <td>
                      @if($withdrawal->status == 'Pending')
                        <span class="badge bg-success">Processing</span>
                      @elseif($withdrawal->status == 'Completed')
                        <span class="badge bg-primary">Completed</span>
                      @elseif($withdrawal->status == 'Rejected')
                        <span class="badge bg-danger">Rejected</span>
                      @else
                        <span class="badge bg-secondary">{{ $withdrawal->status }}</span>
                      @endif
                    </td>
                    <td>{{ $withdrawal->created_at->format('Y-m-d H:i') }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Payout Detail Modal -->
    <div class="modal fade" id="payoutDetailModal" tabindex="-1" aria-labelledby="payoutDetailModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content bg-white">
          <div class="modal-header">
            <h5 class="modal-title" id="payoutDetailModalLabel">Payout Detail</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- For regular payouts -->
            <div id="regularPayout">
              <p><strong>Order ID:</strong> <span id="modalTxid"></span></p>
              <p><strong>Order Buy:</strong> <span id="modalBuy"></span></p>
              <p><strong>Order Earning:</strong> <span id="modalEarning"></span></p>
              <p><strong>Payout Amount:</strong> <span id="modalActual"></span></p>
              <hr>
              <div id="affiliateCalculationBlock">
                <h6><strong>Affiliate Income Share Calculation:</strong></h6>
                <p><strong>Eligible Profit Sharing:</strong> <span id="modalProfitSharing"></span></p>
                <p id="affiliateCalculationLabel" class="small text-danger">(Order Earning/2) x Profit Sharing</p>
              </div>
            </div>
            
            <!-- For direct payouts -->
            <div id="directPayout" style="display:none;">
              <p><strong>Topup ID:</strong> <span id="modalTxidDirect"></span></p>
              <p><strong>Topup Amount:</strong> <span id="modalBuyDirect"></span></p>
              <hr>
              <h6><strong>Affiliate Income Share Calculation:</strong></h6>
              <p><strong>Eligible Profit Sharing:</strong> <span id="modalProfitSharingDirect"></span></p>
              <p><strong>Calculated Affiliate Income:</strong> <span id="modalAffiliateIncomeDirect"></span></p>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <x-slot:footerFiles>
    <script>
      document.addEventListener("DOMContentLoaded", function(){
          
        // Get the URL search parameters
        var urlParams = new URLSearchParams(window.location.search);
        var activeTab = urlParams.get('active_tab');
        
        // If the active_tab parameter is 'payout', activate the payout tab
        if (activeTab === 'payout') {
          var payoutTabEl = document.querySelector('a[href="#payoutTab"]');
          if (payoutTabEl) {
              new bootstrap.Tab(payoutTabEl).show();
          }
        }

        var payoutLinks = document.querySelectorAll('.payout-detail-link');
        payoutLinks.forEach(function(link) {
          link.addEventListener('click', function(){
            var payoutType = this.getAttribute('data-payout-type'); // "direct" or "earning"
            
            if (payoutType === "direct") {
              // Hide regular payout section, show direct payout section.
              document.getElementById('regularPayout').style.display = "none";
              document.getElementById('directPayout').style.display = "block";
              
              // Retrieve deposit details.
              var depositTxid = this.getAttribute('data-deposit-txid') || "N/A";
              var depositAmount = this.getAttribute('data-deposit-amount') || "0.0000";
              var directPercentage = this.getAttribute('data-direct-percentage') || "0";
              var totalPayout = this.getAttribute('data-total-payout') || "0";
              
              // Populate direct payout fields.
              document.getElementById('modalTxidDirect').textContent = depositTxid;
              document.getElementById('modalBuyDirect').textContent = depositAmount;
              document.getElementById('modalProfitSharingDirect').textContent = (parseFloat(directPercentage) * 100).toFixed(2) + '%';
              
              // Calculate affiliate income: Topup Total x directPercentage
              var topupTotal = parseFloat(depositAmount.replace(/,/g, ''));
              var affiliateIncomeDirect = totalPayout;
              document.getElementById('modalAffiliateIncomeDirect').textContent = affiliateIncomeDirect;
            } else {
              // Regular payout section.
              document.getElementById('regularPayout').style.display = "block";
              document.getElementById('directPayout').style.display = "none";
              
              // Retrieve standard payout details.
              var txid = this.getAttribute('data-txid');
              var buy = this.getAttribute('data-buy');
              var earning = this.getAttribute('data-earning');
              var actual = this.getAttribute('data-actual');
              var profitSharing = this.getAttribute('data-profit-sharing'); // e.g., "0.25"
              var ownOrder = this.getAttribute('data-own-order'); // "true" or "false"
              var wallet = this.getAttribute('data-wallet') || "";
              console.log("Wallet attribute:", wallet);
              
              // Populate regular payout fields.
              document.getElementById('modalTxid').textContent = txid;
              document.getElementById('modalBuy').textContent = buy;
              document.getElementById('modalEarning').textContent = earning;
              document.getElementById('modalActual').textContent = actual;
              document.getElementById('modalProfitSharing').textContent = (parseFloat(profitSharing) * 100).toFixed(2) + '%';
              
              // If the order belongs to the current user OR if the wallet is "earning", hide the affiliate calculation block.
              if (ownOrder === "true" || wallet.toLowerCase() === "earning") {
                document.getElementById('affiliateCalculationBlock').style.display = "none";
              } else {
                document.getElementById('affiliateCalculationBlock').style.display = "block";
              }
              
              // Calculate affiliate income for regular payouts.
              var affiliateIncomeElem = document.getElementById('modalAffiliateIncome');
              if (affiliateIncomeElem) {
                var affiliateIncome = ((parseFloat(earning) / 2) * parseFloat(profitSharing)).toFixed(4);
                affiliateIncomeElem.textContent = affiliateIncome;
              }
            }
          });
        });
      });
    </script>
  </x-slot:footerFiles>
</x-base-layout>
