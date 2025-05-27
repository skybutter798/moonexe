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
        
        .bannerbg {
              background-image: none !important;
              background-size: contain;
              background-repeat: no-repeat;
              background-color: #faf9f9;
          }
      }
      .summary-card {
        border: 1px solid #4d80b5;
        padding: 20px;
        text-align: center;
      }
      
      .card {
        border: 1px solid #4d80b5;
        padding: 5px;
        border-radius:0px;
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
    
    .value {
        padding: 8px 60px;
        background-color: #e1e1e1;
        color: black;
        border-radius: 10px;
        font-weight: bolder;
    }
    
    .tip-card {
      border: 1px solid #4d80b5;
      border-radius: 0; 
      background-color: white;
    }
    
    .tip-card .card-body i {
      color: #333;
    }
    
    .tip-card .card-title {
      font-weight: bold;
    }
    
    .referral-box {
        background-color: #4d80b5;
        padding: 10px 20px;
        border-radius: 25px;
    }
    
    .custom-input {
      border-radius: 25px;
      padding: 5px 15px;
    }
    
    .bannerbg {
        background: url('{{ asset('img/referral_bg.png') }}');
        background-size: contain;
        background-repeat: no-repeat;
        background-color: #faf9f9;
    }
    
    .tier-details {
        font-size: 0.95rem;
        line-height: 1.5;
      }
      .tier-details section + section {
        margin-top: 1.5rem;
        border-top: 1px solid #dee2e6;
        padding-top: 1rem;
      }
      .tier-details h6 {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: white;
            background-color: black;
            padding: 10px;
        }
      .tier-details ul {
        list-style: none;
        padding-left: 0;
        color: black;
      }
      .tier-details ul li {
        position: relative;
        padding-left: 1.5rem;
        margin-bottom: 0.5rem;
        color: black;
      }
      .tier-details ul li:before {
        content: "•";
        position: absolute;
        left: 0;
        color: black;
      }
      
      .btn-question {
        background: none !important;
        color: red !important;
        border: none;
        padding: 0px;
        font-size: 25px;
      }


    </style>
  </x-slot:headerFiles>

  <div class="container py-4">
    <!-- Referral Banner -->
    <div class="referral-banner bannerbg p-4 mb-4">
      <div class="row justify-content-end">
        <div class="col-md-4">
          <!-- Banner Text -->
          <div class="text-start">
            <h1 class="text-primary">
              <strong>Refer Friends.</strong>
            </h1>
            <p class="h5 text-primary">Get Equivalent Trading Referral Fee Credit Each.</p>
          </div>
            <p class="mb-0 text-default">
              You can earn extra credits every time your referred friend completes a trade.
              Share your referral link and watch your rewards grow!
            </p>
        <!-- Referral Box for Referral ID -->
        <div class="referral-box mt-3">
          <label for="ref-id" class="fw-bold mb-1 text-white" style="padding-left: 5px;">MY REFERRAL ID</label>
          <div class="position-relative">
            <input type="text" id="ref-id" class="form-control custom-input" value="{{ auth()->user()->referral_code }}" readonly>
            <button class="btn btn-link p-0 position-absolute" 
                    style="top: 50%; right: 10px; transform: translateY(-50%);" 
                    onclick="copyToClipboard('ref-id')">
              <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="bi bi-copy" viewBox="0 0 16 16" style="width: 1.2rem; height: 1.2rem;">
                <path fill-rule="evenodd" d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z"/>
              </svg>
            </button>
          </div>
        </div>
        
        <!-- Referral Box for Referral Link -->
        <div class="referral-box mt-3">
          <label for="ref-link" class="fw-bold mb-1 text-white" style="padding-left: 5px;">MY REFERRAL LINK</label>
          <div class="position-relative">
            <input type="text" id="ref-link" class="form-control custom-input" value="{{ auth()->user()->referral_link }}" readonly>
            <button class="btn btn-link p-0 position-absolute" 
                    style="top: 50%; right: 10px; transform: translateY(-50%);" 
                    onclick="copyToClipboard('ref-link')">
              <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="bi bi-copy" viewBox="0 0 16 16" style="width: 1.2rem; height: 1.2rem;">
                <path fill-rule="evenodd" d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z"/>
              </svg>
            </button>
          </div>
        </div>
        
        <!-- Centered Invite Friends Button -->
        <button class="btn btn-primary mx-auto d-block mt-3" style="border-radius: 15px; width: 80%;" onclick="shareReferral()">Invite Friends</button>
        </div>
      </div>
    </div>

    
    <hr>

    <div class="mb-2">
      <h4 class="text-primary fw-bold mb-4">My Communities</h4>
      <form method="GET" action="{{ route('user.referral') }}">
        <div class="container mb-3 p-0">
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
              <a href="{{ route('user.referral') }}" class="btn btn-danger btn-sm">Refresh</a>
              <button type="button" class="btn btn-question btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#referralInfoModal"> <i class="bi bi-question-square-fill"></i> </button>
            </div>
            <!-- Row 3: Quick Filters -->
            <div class="quick-filters d-flex align-items-center gap-2">
              <a href="{{ route('user.referral', ['filter' => 'today']) }}" class="btn btn-primary btn-sm btn-today">Today</a>
              <a href="{{ route('user.referral', ['filter' => 'weekly']) }}" class="btn btn-primary btn-sm btn-weekly">This Week</a>
              <a href="{{ route('user.referral', ['filter' => 'monthly']) }}" class="btn btn-primary btn-sm btn-monthly">This Month</a>
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
              <a href="{{ route('user.referral') }}" class="btn btn-danger btn-sm">Refresh</a>
              <button type="button" class="btn btn-question btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#referralInfoModal"> <i class="bi bi-question-square-fill"></i> </button>
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
    
    <div class="row mb-4">
          <!-- Card: Group Trading Margin -->
          <div class="col-12 col-md-4 mb-3 d-flex">
            <div class="summary-card flex-fill">
              <h6><strong>Group Trading Margin</strong></h6>
              <p class="value">{{ number_format($groupTradingMargin, 4) }}</p>
            </div>
          </div>
          
          <div class="col-12 col-md-4 mb-3 d-flex">
            <div class="summary-card flex-fill">
              <h6><strong>Group Community</strong></h6>
              <p class="value">{{ $totalCommunity }}</p>
            </div>
          </div>
          
          <!-- Card: Direct & Matching Percentages -->
            <div class="col-12 col-md-4 mb-3 d-flex position-relative">
              <div class="summary-card flex-fill p-3">
                <div class="d-flex align-items-center justify-content-around pt-2">
                  <!-- Direct Column -->
                  <div class="text-center">
                    <p class="mb-1 h6"><strong>Referral</strong></p>
                    <p class="mb-0 fs-4">
                      <span class="badge badge-dark">
                        {{ number_format($directPercentage * 100, 2) }}%
                      </span>
                    </p>
                  </div>
                  <!-- Vertical Separator -->
                  <div class="mx-3" style="border-left: 1px solid #dee2e6; height: 50px;"></div>
                  <!-- Matching Column -->
                  <div class="text-center">
                    <p class="mb-1 h6"><strong>Matching</strong></p>
                    <p class="mb-0 fs-4">
                      <span class="badge badge-dark">
                        {{ number_format($matchingPercentage * 100, 2) }}%
                      </span>
                    </p>
                  </div>
                </div>
              </div>
              <button type="button"
                      class="btn btn-sm btn-question position-absolute"
                      style="top: 50%; left: 50%; transform: translate(-80%, -50%);"
                      data-bs-toggle="modal"
                      data-bs-target="#referralInfoModal">
                <i class="bi bi-question-square-fill"></i>
              </button>
            </div>

        
          <!-- Card: My Trading Profit -->
        <div class="col-12 col-md-4 mb-3 d-flex">
          <div class="summary-card flex-fill" style="cursor: pointer;" onclick="showBreakdown('tradingProfit')">
            <h6><strong>My Trading Profit</strong></h6>
            <p class="value">{{ number_format($myTotalEarning, 4) }}</p>
          </div>
        </div>
        
        <!-- Card: My Total Referral Profit -->
        <div class="col-12 col-md-4 mb-3 d-flex">
          <div class="summary-card flex-fill" style="cursor: pointer;" onclick="showBreakdown('referralProfit')">
            <h6><strong>My Total Referral Profit</strong></h6>
            <p class="value">{{ number_format($myTotalDirect, 4) }}</p>
          </div>
        </div>
        
        <!-- Card: My Total Matching -->
        <div class="col-12 col-md-4 mb-3 d-flex">
          <div class="summary-card flex-fill" style="cursor: pointer;" onclick="showBreakdown('matchingProfit')">
            <h6><strong>My Total Matching</strong></h6>
            <p class="value">{{ number_format($myTotalMatching, 4) }}</p>
          </div>
        </div>

        
        <hr>
        
      <div class="my-referrals">
        <div class="row">
            @forelse ($firstLevelReferrals as $referral)
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <strong>Username:</strong> {{ $referral->name }}
                            </h5>
                            <p class="card-text text-dark">
                                <strong>User ID:</strong> {{ $referral->referral_code }}
                            </p>
                            <p class="card-text text-dark">
                                <strong>Registered Date:</strong> {{ $referral->created_at->format('m/d/Y') }}
                            </p>
                            <p class="card-text text-dark">
                                <strong>Status:</strong> {{ $referral->status == 1 ? 'Active' : 'Inactive' }}
                            </p>
                            <p class="card-text text-dark">
                                <strong>Total Community:</strong> {{ $referral->downline_count }}
                            </p>
                            <!-- Horizontal rule after Total Community -->
                            <hr>
                            <p class="card-text text-dark">
                                <strong>Trading Margin:</strong> {{ number_format($referral->trading_margin, 4) }}
                            </p>
                            <p class="card-text text-dark">
                                <strong>Referral Percentage:</strong> {{ number_format($referral->direct_percentage * 100, 2) }}%
                            </p>
                            <p class="card-text text-dark">
                                <strong>Group Referral Profit:</strong> {{ number_format($referral->total_direct, 4) }}
                            </p>
                            <p class="card-text text-dark">
                                <strong>Matching Percentage:</strong> {{ number_format($referral->matching_percentage * 100, 2) }}%
                            </p>
                            <p class="card-text text-dark">
                                <strong>Group Matching:</strong> {{ number_format($referral->group_matching, 4) }}
                            </p>
                            <p class="card-text text-dark">
                                <strong>Group Trading Profit:</strong> {{ number_format($referral->group_roi, 4) }}
                            </p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <strong>No referrals found.</strong>
                    </div>
                </div>
            @endforelse
        </div>
    
        <!-- Totals Card (Only if there is at least one referral) -->
        @if ($firstLevelReferrals->count() > 0)
            <div class="card mt-3">
                <div class="card-body bg-dark text-white">
                    <p class="card-text  text-white">
                        <strong>Total Community:</strong> {{ $firstLevelReferrals->sum('downline_count') }}
                    </p>
                    <!-- Horizontal rule after Total Community -->
                    <hr class="bg-white">
                    <p class="card-text  text-white">
                        <strong>Trading Margin:</strong> {{ number_format($firstLevelReferrals->sum('trading_margin'), 4) }}
                    </p>
                    <p class="card-text  text-white">
                        <strong>Group Referral Profit:</strong> {{ number_format($firstLevelReferrals->sum('total_direct'), 4) }}
                    </p>
                    <p class="card-text  text-white">
                        <strong>Group Matching:</strong> {{ number_format($firstLevelReferrals->sum('group_matching'), 4) }}
                    </p>
                    <p class="card-text  text-white">
                        <strong>Group Trading Profit:</strong> {{ number_format($firstLevelReferrals->sum('group_roi'), 4) }}
                    </p>
                </div>
            </div>
        @endif
    </div>

    </div>

    <!-- Tips Section -->
    <div class="tips-section my-4">
      <h3 class="mb-3">Tips</h3>
      <div class="row">
        <!-- Tip Card 1 -->
        <div class="col-md-4 mb-3">
          <div class="card tip-card h-100">
            <div class="card-body d-flex align-items-center justify-content-center p-3" style="background-color:#f7f7f7">
              <!-- Icon on the left -->
              <img src="/img/share_1.png" alt="Share 1" class="img-fluid me-3" style="max-width: 50px;">
              
              <!-- Text in the middle -->
              <div class="text-center">
                <h6 class="card-title text-primary">Step 1</h6>
                <p class="card-text text-dark">Share your referral link with friends.</p>
                <button class="btn btn-primary btn-sm" onclick="copyToClipboard('ref-link')">
                  Copy Referral Link
                </button>
              </div>
            </div>
          </div>
        </div>
    
        <!-- Tip Card 2 -->
        <div class="col-md-4 mb-3">
          <div class="card tip-card h-100">
            <div class="card-body d-flex align-items-center justify-content-center p-3" style="background-color:#f7f7f7">
              <img src="/img/share_2.png" alt="Share 1" class="img-fluid me-3" style="max-width: 50px;">
              <div class="text-center">
                <h5 class="card-title text-primary">Step 2</h5>
                <p class="card-text text-dark">Invite friends to sign up and deposit more than $100.</p>
              </div>
            </div>
          </div>
        </div>
    
        <!-- Tip Card 3 -->
        <div class="col-md-4 mb-3">
          <div class="card tip-card h-100">
            <div class="card-body d-flex align-items-center justify-content-center p-3" style="background-color:#f7f7f7">
              <img src="/img/share_3.png" alt="Share 1" class="img-fluid me-3" style="max-width: 50px;">
              <div class="text-center">
                <h5 class="card-title text-primary">Step 3</h5>
                <p class="card-text text-dark">Receive a percentage of cashback for each eligible referral.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div> 

   <!-- Dark Section (Rules & FAQ) -->
    <div class="mb-4">
      <h3 class="text-primary"><strong>Rules & FAQ</strong></h3>
      <p>
        Share your Referral ID / link with a friend who does not have a MoonExe account.
      </p>
      <h5 class="mt-4">Regular Task:</h5>
      <p>
        Referees must accumulatively deposit more than $100 within 14 days of registration.
        Both the referrer and the referee will be rewarded with a trading fee rebate voucher.
      </p>
      <h5 class="mt-2">Disclaimer:</h5>
      <p>
        Each referral qualifies for one reward only. MoonExe does not offer additional or tiered bonuses 
        beyond this. Please ensure your referral meets the eligibility criteria to receive your reward.
      </p>
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
    
    <!-- Tier Modal -->
    <div class="modal fade" id="referralInfoModal" tabindex="-1" aria-labelledby="referralInfoModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content bg-white">
          <div class="modal-header">
            <h5 class="modal-title" id="referralInfoModalLabel">Tier & Task Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body tier-details">
    
            <section>
              <h5>DEPOSIT TIER TASKS</h5>
    
              <h6 class="mt-3">Task 1: Deposit 100–990 USDT (Entry Tier)</h6>
              <ul>
                <li>You receive: 40% of daily exchange spread profits</li>
                <li>Platform receives: 60% of profits</li>
                <li>Unlocks:
                  <ul>
                    <span class="text-danger">- 3% of total cumulative volume (Development Reward)<br>
                    - 5% of team's daily spread profits (Matching Reward)</span>
                  </ul>
                </li>
              </ul>
    
              <h6 class="mt-3">Task 2: Deposit 1,000–9,990 USDT (Mid Tier)</h6>
              <ul>
                <li>You receive: 45% of daily exchange spread profits</li>
                <li>Platform receives: 55% of profits</span>
                <li>Unlocks:
                  <ul>
                    <span class="text-danger">- 5% of total cumulative volume (Development Reward)<br>
                    - 10% of team's daily spread profits (Matching Reward)</span>
                  </ul>
                </li>
              </ul>
    
              <h6 class="mt-3">Task 3: Deposit 10,000–99,990 USDT (Advanced Tier)</h6>
              <ul>
                <li>You receive: 50% of daily exchange spread profits</li>
                <li>Platform receives: 50% of profits</li>
                <li>Unlocks:
                  <ul>
                    <span class="text-danger">- 8% of total cumulative volume (Development Reward)<br>
                    - 20% of team's daily spread profits (Matching Reward)</span>
                  </ul>
                </li>
              </ul>
    
              <h6 class="mt-3">Task 4: Team Cumulative Deposit 100,000+ USDT</h6>
              <ul>
                <li>Earn:
                  <ul>
                    <span class="text-danger">- 10% of total cumulative volume (Development Reward)<br>
                    - 30% of team's daily spread profits (Matching Reward)</span>
                  </ul>
                </li>
              </ul>
            </section>
    
            <section class="mt-4">
              <h5>TEAM DEPOSIT TIER TASKS</h5>
    
              <h6 class="mt-3">Task 5: Team Cumulative Deposit 100,000+ USDT</h6>
              <ul>
                <li>Earn:
                  <ul>
                    <li>10% of total cumulative volume (Development Reward)</li>
                    <li>30% of team's daily spread profits (Matching Reward)</li>
                  </ul>
                </li>
              </ul>
    
              <h6 class="mt-3">Task 6: Team Cumulative Deposit 1,000,000+ USDT</h6>
              <ul>
                <li>Earn:
                  <ul>
                    <li>12% of total cumulative volume (Development Reward)</li>
                    <li>40% of team's daily spread profits (Matching Reward)</li>
                  </ul>
                </li>
              </ul>
    
              <h6 class="mt-3">Task 7: Team Cumulative Deposit 5,000,000+ USDT</h6>
              <ul>
                <li>Earn:
                  <ul>
                    <li>15% of total cumulative volume (Development Reward)</li>
                    <li>50% of team's daily spread profits (Matching Reward)</li>
                  </ul>
                </li>
              </ul>
    
              <h6 class="mt-3">Task 8: Team Cumulative Deposit 10,000,000+ USDT</h6>
              <ul>
                <li>Earn:
                  <ul>
                    <li>18% of total cumulative volume (Development Reward)</li>
                    <li>60% of team's daily spread profits (Matching Reward)</li>
                  </ul>
                </li>
              </ul>
            </section>
    
            <hr>
    
            <section>
              <h5 class="mt-4">TEAM REWARD SCENARIO</h5>
            
              <h6 class="mt-3">TEAM DEVELOPMENT UPGRADE REWARDS</h6>
              <p><em>(Permanent cumulative calculation - differential tiered percentage model)</em></p>
              <ul>
                <li><strong>Scenario 1:</strong> Achieve 100,000 USDT Lifetime Team Volume<br>
                  <span class="text-danger">Earn: 10% of total cumulative volume<br>
                  Example: 1,000,000 team = 100,000 bonus</span>
                </li>
                <li><strong>Scenario 2:</strong> Achieve 1,000,000 USDT Lifetime Team Volume<br>
                  <span class="text-danger">Earn: 12% of total cumulative volume<br>
                  Example: 1,000,000 team = 120,000 bonus</span>
                </li>
                <li><strong>Scenario 3:</strong> Achieve 5,000,000 USDT Lifetime Team Volume<br>
                  <span class="text-danger">Earn: 15% of total cumulative volume<br>
                  Example: 5,000,000 team = 750,000 bonus</span>
                </li>
                <li><strong>Scenario 4:</strong> Achieve 10,000,000 USDT Lifetime Team Volume<br>
                  <span class="text-danger">Earn: 18% of total cumulative volume<br>
                  Example: 10,000,000 team = 1,800,000 bonus</span>
                </li>
              </ul>
            
              <h6 class="mt-4">TEAM MATCHING REWARDS</h6>
              <p><em>(Daily calculation - tiered matching percentage model)</em></p>
              <ul>
                <li><strong>Scenario 5:</strong> Reach 100,000 USDT Team Volume<br>
                  <span class="text-danger">Daily earn: 30% of team's total spread profits<br>
                  Example: 5,000 daily profit = 1,500 reward</span>
                </li>
                <li><strong>Scenario 6:</strong> Reach 1,000,000 USDT Team Volume<br>
                  <span class="text-danger">Daily earn: 40% of team's total spread profits<br>
                  Example: 5,000 daily profit = 2,000 reward</span>
                </li>
                <li><strong>Scenario 7:</strong> Reach 5,000,000 USDT Team Volume<br>
                  <span class="text-danger">Daily earn: 50% of team's total spread profits<br>
                  Example: 20,000 daily profit = 10,000 reward</span>
                </li>
                <li><strong>Scenario 8:</strong> Reach 10,000,000 USDT Team Volume<br>
                  <span class="text-danger">Daily earn: 60% of team's total spread profits<br>
                  Example: 50,000 daily profit = 30,000 reward</span>
                </li>
              </ul>
            </section>

    
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
                      <td>${Math.floor(grouped[referral].count)}</td>
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
                                  <td>${parseInt(item.count)}</td>
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
