{{-- 
/**
*
* Created a new component <x-rtl.widgets._w-hybrid-one/>.
* 
*/
--}}

<div class="row widget-statistic">
    <!-- Registered Users Widget -->
    <div class="col-xl-4 col-lg-4 col-md-4 col-sm-4 col-12 layout-spacing">
        <div class="widget widget-one_hybrid widget-registered-users">
            <div class="widget-heading">
                <div class="w-title">
                    <div class="w-icon">
                        <!-- Using Feather "users" icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" 
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                             class="feather feather-users">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="">
                        <!-- Replace or bind your dynamic value here -->
                        <p class="w-value">31.6K</p>
                        <h5 class="">Registered Users</h5>
                    </div>
                </div>
            </div>
            <div class="widget-content">    
                <div class="w-chart">
                    <!-- Sample chart container; update the ID as needed -->
                    <div id="{{$chartId}}"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Payout Widget -->
    <div class="col-xl-4 col-lg-4 col-md-4 col-sm-4 col-12 layout-spacing">
        <div class="widget widget-one_hybrid widget-daily-payout">
            <div class="widget-heading">
                <div class="w-title">
                    <div class="w-icon">
                        <!-- Using Feather "dollar-sign" icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" 
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                             class="feather feather-dollar-sign">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <div class="">
                        <!-- Replace with your daily payout value -->
                        <p class="w-value">1,900</p>
                        <h5 class="">Daily Payout</h5>
                    </div>
                </div>
            </div>
            <div class="widget-content">    
                <div class="w-chart">
                    <!-- Sample chart container; update the ID as needed -->
                        <div id="hybrid_followers1"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Deposit & Withdrawal Widget -->
    <div class="col-xl-4 col-lg-4 col-md-4 col-sm-4 col-12 layout-spacing">
        <div class="widget widget-one_hybrid widget-deposit-withdrawal">
            <div class="widget-heading">
                <div class="w-title">
                    <div class="w-icon">
                        <!-- Using Feather "credit-card" icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" 
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                             class="feather feather-credit-card">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                    </div>
                    <div class="">
                        <!-- Update this value with your deposit & withdrawal metric -->
                        <p class="w-value">$25,000</p>
                        <h5 class="">Deposit & Withdrawal</h5>
                    </div>
                </div>
            </div>
            <div class="widget-content">    
                <div class="w-chart">
                    <!-- Sample chart container; update the ID as needed -->
                    <div id="hybrid_followers3"></div>
                </div>
            </div>
        </div>
    </div>
</div>
