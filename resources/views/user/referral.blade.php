<x-base-layout :scrollspy="false">
  <x-slot:pageTitle>
    Referral
  </x-slot:pageTitle>

  <x-slot:headerFiles>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
      .my-referrals table thead {
        background-color: #343a40; /* Dark header */
        color: #ffffff;
      }
      @media (max-width: 576px) {
        .referral-banner {
          height: 100% !important;
        }
      }
      .summary-card {
        border: 1px solid #dee2e6;
        padding: 20px;
        border-radius: 5px;
        text-align: center;
        background: #f8f9fa;
      }
      .filter-form .form-control,
      .filter-form .form-select {
        max-width: 150px;
        display: inline-block;
      }
      .filter-form .btn {
        max-width: 100px;
        display: inline-block;
      }
      
      @media (min-width: 576px) {
        .date-range,
        .apply-reset {
          flex: 0 0 auto;
          order: 1;
          margin-top: 0;
        }
        .date-range {
          margin-right: 10px;
        }
        .apply-reset {
          margin-right: auto;
        }
        .quick-filters {
          flex: 0 0 auto;
          order: 2;
          margin-top: 0;
        }
        
        .d-flex.flex-sm-nowrap {
          flex-wrap: nowrap !important;
        }
      }
      
      .custom-table thead th {
          white-space: nowrap;
        }
        
    .layout-dark .summary-card {
      background: #191e3a;
    }
    
    .form-control:disabled:not(.flatpickr-input),
    .form-control[readonly]:not(.flatpickr-input) {
        color: black;
    }
    </style>
  </x-slot:headerFiles>

  <div class="container py-4">
    <!-- Referral Banner -->
    <div class="referral-banner p-4 mb-4 rounded" style="background: url('{{ asset('img/referral_bg.png') }}'); background-size: cover; height:350px">
      <div class="row align-items-center">
        <!-- Banner Text -->
        <div class="col-md-8">
          <h2 class="mb-3 text-white">Refer Friends.<br>Get Equivalent Trading Referral Fee Credit Each.</h2>
          <p class="text-white-50 mb-0">
            You can earn extra credits every time your referred friend completes a trade.
            Share your referral link and watch your rewards grow!
          </p>
        </div>
        <!-- Referral Box -->
        <div class="col-md-4">
          <div class="referral-box p-4 bg-dark rounded">
            <label for="ref-id" class="fw-bold mb-1 text-white">MY REFERRAL ID</label>
            <div class="input-group mb-3">
              <input type="text" id="ref-id" class="form-control" value="{{ auth()->user()->referral_code }}" readonly>
              <button class="btn btn-primary" onclick="copyToClipboard('ref-id')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="bi bi-copy" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z"/>
                  </svg>
              </button>
            </div>
            
            <label for="ref-link" class="fw-bold mb-1 text-white">MY REFERRAL LINK</label>
            <div class="input-group mb-3">
              <input type="text" id="ref-link" class="form-control" value="{{ auth()->user()->referral_link }}" readonly>
              <button class="btn btn-primary" onclick="copyToClipboard('ref-link')">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="bi bi-copy" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z"/>
                  </svg>
                </button>
            </div>

            <button class="btn btn-primary btn-sm" onclick="shareReferral()">Invite Friends</button>
          </div>
        </div>
      </div>
    </div>
    
    <hr>

    <div class="mb-2">
      <h4 class="mb-4">My Communities</h4>
      <form method="GET" action="{{ route('user.referral') }}">
        <div class="container mb-3">
          <!-- Mobile layout: three rows -->
          <div class="d-block d-sm-none">
            <!-- Row 0: Title -->
            <div class="date-range-title mb-2">
              <h6>From Date - To Date</h6>
            </div>
            <!-- Row 1: Date Range Section -->
            <div class="date-range d-flex align-items-center gap-2 mb-2">
              <input type="date" name="from" class="form-control form-control-sm" placeholder="dd/mm/yy" value="{{ request('from') }}">
              <input type="date" name="to" class="form-control form-control-sm" placeholder="dd/mm/yy" value="{{ request('to') }}">
            </div>
            <!-- Row 2: Apply & Reset Buttons -->
            <div class="apply-reset d-flex align-items-center gap-2 mb-2">
              <button type="submit" class="btn btn-primary btn-sm">Apply</button>
              <a href="{{ route('user.referral') }}" class="btn btn-primary btn-sm">Refresh</a>
            </div>
            <!-- Row 3: Quick Filters -->
            <div class="quick-filters d-flex align-items-center gap-2">
              <a href="{{ route('user.referral', ['filter' => 'today']) }}" class="btn btn-dark btn-sm btn-today">Today</a>
              <a href="{{ route('user.referral', ['filter' => 'weekly']) }}" class="btn btn-dark btn-sm btn-weekly">This Week</a>
              <a href="{{ route('user.referral', ['filter' => 'monthly']) }}" class="btn btn-dark btn-sm btn-monthly">This Month</a>
            </div>
          </div>
    
          <!-- Desktop layout: original single-row layout -->
          <div class="d-none d-sm-flex align-items-center justify-content-between flex-wrap flex-sm-nowrap gap-2">
            <!-- Date Range Section -->
            <div class="date-range d-flex align-items-center gap-2">
              <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
              <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
            </div>
            <!-- Apply & Reset Buttons -->
            <div class="apply-reset d-flex align-items-center gap-2">
              <button type="submit" class="btn btn-primary btn-sm">Apply</button>
              <a href="{{ route('user.referral') }}" class="btn btn-primary btn-sm">Refresh</a>
            </div>
            <!-- Quick Filters -->
            <div class="quick-filters d-flex align-items-center gap-2">
              <a href="{{ route('user.referral', ['filter' => 'today']) }}" class="btn btn-dark btn-sm btn-today">Today</a>
              <a href="{{ route('user.referral', ['filter' => 'weekly']) }}" class="btn btn-dark btn-sm btn-weekly">This Week</a>
              <a href="{{ route('user.referral', ['filter' => 'monthly']) }}" class="btn btn-dark btn-sm btn-monthly">This Month</a>
            </div>
          </div>
        </div>
      </form>
    </div>

    <div class="container py-4">
        <div class="row mb-4">
          <!-- Card: Group Trading Margin -->
          <div class="col-12 col-md-4 mb-3 d-flex">
            <div class="summary-card flex-fill">
              <h6>Group Trading Margin</h6>
              <p class="fs-4">{{ number_format($groupTradingMargin, 4) }}</p>
            </div>
          </div>
          
          <!-- Card: Group Community -->
          <div class="col-4 col-md-4 mb-3 d-flex">
            <div class="summary-card flex-fill">
              <h6>Group Community</h6>
              <p class="fs-4">{{ $totalCommunity }}</p>
            </div>
          </div>
          
          <!-- Card: Direct & Matching Percentages -->
          <div class="col-8 col-md-4 mb-3 d-flex">
            <div class="summary-card flex-fill p-3">
              <div class="d-flex align-items-center justify-content-around pt-2">
                <!-- Direct Column -->
                <div class="text-center">
                  <p class="mb-1 h6">Referral</p>
                  <p class="mb-0 fs-4">
                    <span class="badge bg-primary">
                      {{ number_format($directPercentage * 100, 2) }}%
                    </span>
                  </p>
                </div>
                <!-- Vertical Separator -->
                <div class="mx-3" style="border-left: 1px solid #dee2e6; height: 50px;"></div>
                <!-- Matching Column -->
                <div class="text-center">
                  <p class="mb-1 h6">Matching</p>
                  <p class="mb-0 fs-4">
                    <span class="badge bg-primary">
                      {{ number_format($matchingPercentage * 100, 2) }}%
                    </span>
                  </p>
                </div>
              </div>
            </div>
          </div>
        
          <!-- Card: My Trading Profit -->
        <div class="col-12 col-md-4 mb-3 d-flex">
          <div class="summary-card flex-fill" style="cursor: pointer;" onclick="showBreakdown('tradingProfit')">
            <h6>My Trading Profit</h6>
            <p class="fs-4">{{ number_format($myTotalEarning, 4) }}</p>
          </div>
        </div>
        
        <!-- Card: My Total Referral Profit -->
        <div class="col-12 col-md-4 mb-3 d-flex">
          <div class="summary-card flex-fill" style="cursor: pointer;" onclick="showBreakdown('referralProfit')">
            <h6>My Total Referral Profit</h6>
            <p class="fs-4">{{ number_format($myTotalDirect, 4) }}</p>
          </div>
        </div>
        
        <!-- Card: My Total Matching -->
        <div class="col-12 col-md-4 mb-3 d-flex">
          <div class="summary-card flex-fill" style="cursor: pointer;" onclick="showBreakdown('matchingProfit')">
            <h6>My Total Matching</h6>
            <p class="fs-4">{{ number_format($myTotalMatching, 4) }}</p>
          </div>
        </div>

        
        <hr>
      <!-- Downline Table Section -->
      <div class="my-referrals">
        <div class="table-responsive">
          <table class="table table-bordered custom-table">
            <thead>
              <tr>
                <th>User ID</th>
                <th>Username</th>
                <th>Registered Date</th>
                <th>Status</th>
                <th>Total Community</th>
                <th>Trading Margin</th>
                <th>Referral %</th>
                <th>Group Referral Profit</th>
                <th>Matching %</th>
                <th>Group Matching</th>
                <th>Group Trading Profit</th>
              </tr>
            </thead>

            <tbody>
              @forelse ($firstLevelReferrals as $referral)
                <tr>
                  <td>{{ $referral->referral_code }}</td>
                  <td>{{ $referral->name }}</td>
                  <td>{{ $referral->created_at->format('m/d/Y') }}</td>
                  <td>{{ $referral->status == 1 ? 'Active' : 'Inactive' }}</td>
                  <td>{{ $referral->downline_count }}</td>
                  <td>{{ number_format($referral->trading_margin, 4) }}</td>
                  <td>{{ number_format($referral->direct_percentage * 100, 2) }}%</td>
                  <td>{{ number_format($referral->total_direct, 4) }}</td>
                  <td>{{ number_format($referral->matching_percentage * 100, 2) }}%</td>
                  <td>{{ number_format($referral->group_matching, 4) }}</td>
                  <td>{{ number_format($referral->group_roi, 4) }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="11" class="text-center">No referrals found.</td>
                </tr>
              @endforelse
              <!-- Totals Row -->
              <tr class="fw-bold bg-primary text-white">
                  <td colspan="4" class="text-end"></td>
                  <td>{{ $firstLevelReferrals->sum('downline_count') }}</td>
                  <td>{{ number_format($firstLevelReferrals->sum('trading_margin'), 4) }}</td>
                  <td> - </td>
                  <td>{{ number_format($firstLevelReferrals->sum('total_direct'), 4) }}</td>
                  <td> - </td>
                  <td>{{ number_format($firstLevelReferrals->sum('group_matching'), 4) }}</td>
                  <td>{{ number_format($firstLevelReferrals->sum('group_roi'), 4) }}</td>
                </tr>

            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Tips Section -->
    <div class="tips-section my-4">
      <h3 class="mb-3">Tips</h3>
      <div class="row">
        <!-- Tip Card 1 -->
        <div class="col-md-4 mb-3">
          <div class="card tip-card h-100">
            <div class="card-body text-center">
              <i class="bi bi-share-fill fs-1 mb-2 text-dark"></i>
              <h5 class="card-title">Step 1</h5>
              <p class="card-text">Share your referral link with friends.</p>
              <button class="btn btn-primary btn-sm" onclick="copyToClipboard('ref-link')">
                Copy Referral Link
              </button>
            </div>
          </div>
        </div>
        <!-- Tip Card 2 -->
        <div class="col-md-4 mb-3">
          <div class="card tip-card h-100">
            <div class="card-body text-center">
              <i class="bi bi-person-plus-fill fs-1 mb-2 text-dark"></i>
              <h5 class="card-title">Step 2</h5>
              <p class="card-text">Invite friends to sign up and deposit more than $100.</p>
            </div>
          </div>
        </div>
        <!-- Tip Card 3 -->
        <div class="col-md-4 mb-3">
          <div class="card tip-card h-100">
            <div class="card-body text-center">
              <i class="bi bi-cash-stack fs-1 mb-2 text-dark"></i>
              <h5 class="card-title">Step 3</h5>
              <p class="card-text">Receive a percentage of cashback for each eligible referral.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dark Section (Rules & FAQ) -->
    <div class="dark-section mb-4">
      <div class="card bg-dark text-white">
        <div class="card-body">
          <h3 class="card-title text-white">Rules & FAQ</h3>
          <p class="card-text">
            Share your Referral ID / link with a friend who does not have a MoonExe account.
          </p>
          <h5 class="mt-4 text-white">Regular Task:</h5>
          <p class="card-text">
            Referees must accumulatively deposit more than $100 within 14 days of registration.
            Both the referrer and the referee will be rewarded with a trading fee rebate voucher.
          </p>
          <h5 class="mt-4 text-white">Disclaimer:</h5>
          <p class="card-text">
            Each referral qualifies for one reward only. MoonExe does not offer additional or tiered bonuses 
            beyond this. Please ensure your referral meets the eligibility criteria to receive your reward.
          </p>
        </div>
      </div>
    </div>
    
    <!-- Calculation Breakdown Modal -->
    <div class="modal fade" id="breakdownModal" tabindex="-1" aria-labelledby="breakdownModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-white">
          <div class="modal-header">
            <h5 class="modal-title" id="breakdownModalLabel">Calculation Breakdown</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- Content is updated dynamically -->
            <div id="breakdownContent">
              <p>Loading details...</p>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <x-slot:footerFiles>
    <style>
      .copy-notification {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background-color: #28a745;
        color: #fff;
        padding: 10px 20px;
        border-radius: 5px;
        z-index: 9999;
        opacity: 0.9;
        transition: opacity 0.5s ease-out;
      }
    </style>
    <script>
      function copyToClipboard(elementId) {
        var copyText = document.getElementById(elementId);
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        document.execCommand("copy");

        let message = '';
        if (elementId === 'ref-id') {
          message = "Referral code copied: " + copyText.value;
        } else if (elementId === 'ref-link') {
          message = "Referral link copied: " + copyText.value;
        } else {
          message = "Copied: " + copyText.value;
        }

        let notification = document.createElement('div');
        notification.className = 'copy-notification';
        notification.innerText = message;
        document.body.appendChild(notification);

        setTimeout(() => {
          notification.style.opacity = '0';
          setTimeout(() => {
            notification.remove();
          }, 500);
        }, 2000);
      }

      function shareReferral() {
        let referralLink = document.getElementById('ref-link').value;
        let shareText = "Join me on this amazing platform and get rewards! Use my referral link: " + referralLink;

        if (navigator.share) {
          navigator.share({
            title: 'Join Now',
            text: shareText,
            url: referralLink
          }).then(() => {
            console.log('Successful share');
          }).catch((error) => {
            console.log('Error sharing:', error);
          });
        } else {
          copyToClipboard('ref-link');
        }
      }
      
      var downlineBreakdown = @json($firstLevelReferrals);
      var referralBreakdown = @json($referralBreakdown);
      var matchingBreakdown = @json($matchingBreakdown);
      
      var breakdownData = {
        tradingProfit: {
          total: "{{ number_format($myTotalEarning, 4) }}",
          orders: "{{ $totalOrders }}",
          dateRange: "{{ $dateRange }}",
          type: "Trading ROI",
          wallet: "Trading Profit"
        },
        referralProfit: {
          total: "{{ number_format($myTotalDirect, 4) }}",
          // We no longer need "User" and "Status" for the top summary.
          dateRange: "{{ $dateRange }}",
          type: "direct",
          wallet: "affiliates"
        },
        matchingProfit: {
          total: "{{ number_format($myTotalMatching, 4) }}",
          dateRange: "{{ $dateRange }}",
          type: "payout",
          wallet: "affiliates"
        }
      };
      
      function showBreakdown(type) {
          let title = '';
          let content = '';
          let data = breakdownData[type];
        
          if (!data) {
            title = 'Calculation Breakdown';
            content = '<p>Details not available.</p>';
          } else {
            switch (type) {
              case 'tradingProfit':
                // (Trading profit code remains unchanged)
                title = 'My Trading Profit Breakdown';
                content = `
                  <p><strong>Total Trading Profit:</strong> $${data.total}</p>
                  <p>This value is calculated by summing <strong>actual</strong> amounts from your <strong>${data.wallet}</strong> payouts.</p>
                  <ul>
                    <li><strong>Total Orders:</strong> ${data.orders}</li>
                    <li><strong>Date Range:</strong> ${data.dateRange}</li>
                  </ul>
                  <p><em>Formula: Sum(actual) from all eligible "earning" payouts.</em></p>
                `;
                break;
              case 'referralProfit':
                title = 'My Total Referral Profit Breakdown';
                // Group referralBreakdown items by referral_name.
                let grouped = {};
                referralBreakdown.forEach(function(item) {
                  let referral = item.referral_name;
                  if (!grouped[referral]) {
                    // Directly assign the count from the item rather than incrementing.
                    grouped[referral] = { 
                      total: parseFloat(item.total), 
                      count: item.count 
                    };
                  } else {
                    grouped[referral].total += parseFloat(item.total);
                    // Do not change count since it represents the unique deposit count.
                  }
                });
                
                // Build table rows.
                let referralRows = "";
                for (let referral in grouped) {
                  referralRows += `<tr>
                      <td>${referral}</td>
                      <td>$${grouped[referral].total.toFixed(4)}</td>
                      <td>${grouped[referral].count}</td>
                    </tr>`;
                }
                let referralTable = `
                  <table class="table table-bordered">
                    <thead>
                      <tr>
                        <th>Referral Group</th>
                        <th>Total Contribute</th>
                        <th>Total Deposit</th>
                      </tr>
                    </thead>
                    <tbody>
                      ${referralRows}
                    </tbody>
                  </table>
                `;
                content = `
                  <p><strong>Total Referral Profit:</strong> $${data.total}</p>
                  <p>This value is derived by summing amounts from your <strong>Direct Referral</strong> payouts into your <strong>Affiliate Incentive</strong> balance</p>
                  <p><strong>Date Range:</strong> ${data.dateRange}</p>
                  <p><em>Formula: Sum(actual) from all eligible "direct" payouts.</em></p>
                  <hr>
                  <p><strong>Referral Contribution Breakdown:</strong></p>
                  ${referralTable}
                `;
                break;
              
              case 'matchingProfit':
              title = 'My Total Matching Breakdown';
              
              // Directly iterate over the matchingBreakdown array.
              let matchingRows = "";
              matchingBreakdown.forEach(function(item) {
                matchingRows += `<tr>
                                  <td>${item.referral_name}</td>
                                  <td>$${parseFloat(item.total).toFixed(4)}</td>
                                  <td>${item.count}</td>
                                </tr>`;
              });
              
              let matchingTable = `
                <table class="table table-bordered">
                  <thead>
                    <tr>
                      <th>Referral Group</th>
                      <th>Total Contribute</th>
                      <th>Total Trade</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${matchingRows}
                  </tbody>
                </table>
              `;
              
              content = `
                <p><strong>Total Matching Profit:</strong> $${data.total}</p>
                <p>This value is derived by summing amounts from your <strong>Matching</strong> payouts into your <strong>Affiliate Incentive</strong> balance</p>
                <p><strong>Date Range:</strong> ${data.dateRange}</p>
                <p><em>Formula: Sum(actual) from all eligible "payout" matching records based on orders.</em></p>
                <hr>
                <p><strong>Matching Contribution Breakdown:</strong></p>
                ${matchingTable}
              `;
              break;

              default:
                title = 'Calculation Breakdown';
                content = '<p>Details not available.</p>';
            }
          }
        
          document.getElementById('breakdownModalLabel').innerText = title;
          document.getElementById('breakdownContent').innerHTML = content;
        
          let breakdownModal = new bootstrap.Modal(document.getElementById('breakdownModal'));
          breakdownModal.show();
        }
    </script>
  </x-slot:footerFiles>
</x-base-layout>
