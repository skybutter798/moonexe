<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sidebar Menu</title>
  <style>
    /* Styling for disabled menu items */
    .disabled-menu {
      color: grey !important;
      pointer-events: none; /* Disables click events */
      cursor: default;
    }
    .disabled-menu svg {
      stroke: #c3c3c3 !important;
    }
    
    .theme-logo {
        width: auto;
    }
  </style>
</head>
<body>
  <div class="sidebar-wrapper sidebar-theme">
    <nav id="sidebar">
      <div class="navbar-nav theme-brand flex-row text-center">
        <div class="nav-logo">
          <div class="nav-item theme-logo">
            <a href="{{ route('admin.dashboard') }}">
              <img src="{{ asset('img/moon_logo.png') }}" class="navbar-logo logo-dark" alt="logo">
              <img src="{{ asset('img/moon_logo.png') }}" class="navbar-logo logo-light" alt="logo">
            </a>
          </div>
          <div class="nav-item theme-text">
            <a href="{{ route('admin.dashboard') }}" class="nav-link">MOONEXE</a>
          </div>
        </div>
        <div class="nav-item sidebar-toggle">
          <div class="btn-toggle sidebarCollapse">
            <!-- Icon for toggling the sidebar (feather-chevrons-left) -->
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" class="feather feather-chevrons-left">
              <polyline points="11 17 6 12 11 7"></polyline>
              <polyline points="18 17 13 12 18 7"></polyline>
            </svg>
          </div>
        </div>
      </div>

      @if (!Request::is('collapsible-menu/*'))
        <!-- Optional user profile info -->
        <div class="profile-info">
          <div class="user-info">
            <div class="profile-img">
              <img src="{{ Vite::asset('resources/images/profile-30.png') }}" alt="avatar">
            </div>
            <div class="profile-content">
              <!-- Display the authenticated user's name and email dynamically -->
              <h6>{{ $user->name ?? 'Guest' }}</h6>
              <p>{{ $user->email ?? 'Not available' }}</p>
            </div>
          </div>
        </div>
      @endif

      <ul class="list-unstyled menu-categories" id="accordionExample">
        
        {{-- **************************************
             ADMIN‐ONLY MENU ITEMS
        ****************************************--}}
        @if (Auth::check() && Auth::user()->is_admin)
          <!-- Dashboard (Admin) -->
          <li class="menu {{ Request::routeIs('admin.dashboard') ? 'active' : '' }}">
            <a href="{{ route('admin.dashboard') }}" aria-expanded="false" class="dropdown-toggle">
              <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-home">
                  <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                  <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                <span>Dashboard</span>
              </div>
            </a>
          </li>

          <!-- Basic Setting Heading -->
          <li class="menu menu-heading">
            <div class="heading">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                   stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                   class="feather feather-minus">
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
              <span>Basic Setting</span>
            </div>
          </li>
          <!-- Plans -->
          <li class="menu {{ Request::routeIs('admin.directranges.index') ? 'active' : '' }}">
            <a href="{{ route('admin.directranges.index') }}" aria-expanded="false" class="dropdown-toggle">
              <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-archive">
                  <polyline points="21 8 21 21 3 21 3 8"></polyline>
                  <rect x="1" y="3" width="22" height="5"></rect>
                  <line x1="10" y1="12" x2="14" y2="12"></line>
                </svg>
                <span>Plans</span>
              </div>
            </a>
          </li>
          <!-- Currencies (Disabled) -->
          <li class="menu {{ Request::routeIs('admin.currencies.index') ? 'active' : '' }}">
            <a href="{{ route('admin.currencies.index') }}" aria-expanded="false" class="dropdown-toggle">
              <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-dollar-sign">
                  <line x1="12" y1="1" x2="12" y2="23"></line>
                  <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                <span>Currencies</span>
              </div>
            </a>
          </li>
          <li class="menu {{ Request::routeIs('admin.pairs.index') ? 'active' : '' }}">
            <a href="{{ route('admin.pairs.index') }}" aria-expanded="false" class="dropdown-toggle">
              <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-dollar-sign">
                  <line x1="12" y1="1" x2="12" y2="23"></line>
                  <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                <span>Pairs</span>
              </div>
            </a>
          </li>
          <!-- Payouts (Disabled) 
          <li class="menu">
            <a href="#" aria-expanded="false" class="dropdown-toggle disabled-menu">
              <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-credit-card">
                  <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                  <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                <span>Payouts</span>
              </div>
            </a>
          </li>-->

          <!-- Finance Section Heading -->
          <li class="menu menu-heading">
            <div class="heading">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                   stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                   class="feather feather-minus">
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
              <span>Finance Section</span>
            </div>
          </li>
          
          <!-- Deposits -->
            <li class="menu {{ Request::routeIs('admin.deposits.index') ? 'active' : '' }}">
              <a href="{{ route('admin.deposits.index') }}" aria-expanded="false" class="dropdown-toggle">
                <div>
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                       stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                       class="feather feather-download">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                  </svg>
                  <span>Deposits</span>
                </div>
              </a>
            </li>
            
            <!-- Withdrawal -->
            <li class="menu {{ Request::routeIs('admin.withdrawals.index') ? 'active' : '' }}">
              <a href="{{ route('admin.withdrawals.index') }}" aria-expanded="false" class="dropdown-toggle">
                <div>
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                       stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                       class="feather feather-upload">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                  </svg>
                  <span>Withdrawal</span>
                </div>
              </a>
            </li>
            
            <!-- Wallets Control -->
            <li class="menu {{ Request::routeIs('admin.wallets.index') ? 'active' : '' }}">
              <a href="{{ route('admin.wallets.index') }}" aria-expanded="false" class="dropdown-toggle">
                <div>
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="feather feather-settings">
                      <circle cx="12" cy="12" r="3"></circle>
                      <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06
                               a2 2 0 1 1-2.83 2.83l-.06-.06
                               a1.65 1.65 0 0 0-1.82-.33
                               1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09
                               a1.65 1.65 0 0 0-1-1.51
                               1.65 1.65 0 0 0-1.82.33l-.06.06
                               a2 2 0 1 1-2.83-2.83l.06-.06
                               a1.65 1.65 0 0 0 .33-1.82
                               1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09
                               a1.65 1.65 0 0 0 1.51-1
                               1.65 1.65 0 0 0-.33-1.82L4.27 7.27
                               a2 2 0 1 1 2.83-2.83l.06.06
                               a1.65 1.65 0 0 0 1.82.33H9
                               a1.65 1.65 0 0 0 1-1.51V3
                               a2 2 0 1 1 4 0v.09
                               a1.65 1.65 0 0 0 1 1.51
                               1.65 1.65 0 0 0 1.82-.33l.06-.06
                               a2 2 0 1 1 2.83 2.83l-.06.06
                               a1.65 1.65 0 0 0-.33 1.82V9
                               a1.65 1.65 0 0 0 1.51 1H21
                               a2 2 0 1 1 0 4h-.09
                               a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                  <span>Wallets Control</span>
                </div>
              </a>
            </li>
          

          <!-- User Section Heading -->
          <li class="menu menu-heading">
            <div class="heading">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                   stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                   class="feather feather-minus">
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
              <span>User Section</span>
            </div>
          </li>
          <!-- Staffs (Disabled) -->
          <li class="menu">
            <a href="#" aria-expanded="false" class="dropdown-toggle disabled-menu">
              <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-user-check">
                  <path d="M16 21v-2a4 4 0 0 0-3-3.87"></path>
                  <circle cx="9" cy="7" r="4"></circle>
                  <polyline points="17 11 19 13 23 9"></polyline>
                </svg>
                <span>Staffs</span>
              </div>
            </a>
          </li>
          <!-- Users -->
          <li class="menu {{ Request::routeIs('admin.users.index') ? 'active' : '' }}">
            <a href="{{ route('admin.users.index') }}" aria-expanded="false" class="dropdown-toggle">
              <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-user">
                  <path d="M20 21v-2a4 4 0 0 0-3-3.87"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Users</span>
              </div>
            </a>
          </li>
          <!-- KYC Manage (Disabled) -->
          <li class="menu">
            <a href="#" aria-expanded="false" class="dropdown-toggle disabled-menu">
              <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-file-text">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                  <polyline points="14 2 14 8 20 8"></polyline>
                  <line x1="16" y1="13" x2="8" y2="13"></line>
                  <line x1="16" y1="17" x2="8" y2="17"></line>
                  <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <span>KYC Manage</span>
              </div>
            </a>
          </li>
          
          <!-- Referral Tree -->
          <li class="menu {{ Request::routeIs('admin.referrals.index') ? 'active' : '' }}">
            <a href="{{ route('admin.referrals.index') }}" aria-expanded="false" class="dropdown-toggle">
              <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-git-branch">
                  <line x1="6" y1="3" x2="6" y2="15"></line>
                  <circle cx="6" cy="18" r="3"></circle>
                  <circle cx="18" cy="6" r="3"></circle>
                  <line x1="18" y1="9" x2="6" y2="15"></line>
                </svg>
                <span>Referral Tree</span>
              </div>
            </a>
          </li>
          
          <!-- Annoucements -->
          <li class="menu {{ Request::routeIs('admin.annoucement.index') ? 'active' : '' }}">
            <a href="{{ route('admin.annoucement.index') }}" aria-expanded="false" class="dropdown-toggle">
              <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-volume">
                  <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                  <path d="M23 9a4 4 0 0 1-4 4"></path>
                  <path d="M23 5a8 8 0 0 1-8 8"></path>
                </svg>

                <span>Annoucements</span>
              </div>
            </a>
          </li>

          <!-- Report Section Heading -->
          <li class="menu menu-heading">
            <div class="heading">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                   stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                   class="feather feather-minus">
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
              <span>Report Section</span>
            </div>
          </li>
          <!-- Reports & Statement (with Submenu) -->
          <li class="menu">
            <a href="#pages" data-bs-toggle="collapse"
               aria-expanded="{{ Request::is('*/page/*') ? 'true' : 'false' }}"
               class="dropdown-toggle">
              <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-pie-chart">
                  <path d="M21.21 15.89A10 10 0 1 1 8 2.79"></path>
                  <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                </svg>
                <span>Reports &amp; Statement</span>
              </div>
              <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-chevron-right">
                  <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
              </div>
            </a>
            <ul class="collapse submenu list-unstyled {{ Request::is('*/page/*') ? 'show' : '' }}"
                id="pages" data-bs-parent="#accordionExample">
              <li class="{{ Request::routeIs('knowledge-base') ? 'active' : '' }}">
                <a href="{{ getRouterValue() }}/page/knowledge-base"> Transaction report </a>
              </li>
              <li class="{{ Request::routeIs('faq') ? 'active' : '' }}">
                <a href="{{ getRouterValue() }}/page/faq"> Payout Statement </a>
              </li>
              <li class="{{ Request::routeIs('contact-us') ? 'active' : '' }}">
                <a href="{{ getRouterValue() }}/page/contact-us"> P/L </a>
              </li>
            </ul>
          </li>
          <!-- Logging (Disabled) -->
          <li class="menu">
            <a href="#" aria-expanded="false" class="dropdown-toggle disabled-menu">
              <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-activity">
                  <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                <span>Logging</span>
              </div>
            </a>
          </li>

        {{-- **************************************
             REGULAR USER MENU ITEMS
        ****************************************--}}
        @else
          <!-- Dashboard (User) -->
          <li class="menu {{ Request::routeIs('user.dashboard') ? 'active' : '' }}">
            <a href="{{ route('user.dashboard') }}" aria-expanded="false" class="dropdown-toggle">
              <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round" class="feather feather-home">
                  <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                  <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                <span>Dashboard</span>
              </div>
            </a>
          </li>

          <li class="menu {{ Request::routeIs('user.assets') ? 'active' : '' }}">
              <a href="{{ route('user.assets') }}" aria-expanded="false" class="dropdown-toggle">
                <div>
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                       stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                       class="feather feather-briefcase">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 3h-4a2 2 0 0 0-2 2v2h8V5a2 2 0 0 0-2-2z"></path>
                  </svg>
                  <span>Assets</span>
                </div>
              </a>
            </li>


          <!-- Orders -->
          <li class="menu {{ Request::routeIs('user.order') ? 'active' : '' }}">
            <a href="{{ route('user.order') }}" aria-expanded="false" class="dropdown-toggle">
              <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-shopping-bag">
                  <path d="M6 2l.01 4"></path>
                  <path d="M18 2l-.01 4"></path>
                  <path d="M2 7h20l-1.34 14.36A2 2 0 0 1 18.67 23H5.33
                           a2 2 0 0 1-1.98-1.64L2 7z"></path>
                  <path d="M16 11a4 4 0 0 1-8 0"></path>
                </svg>
                <span>Orders</span>
              </div>
            </a>
          </li>

          <!-- Referral -->
          <li class="menu {{ Request::routeIs('user.referral') ? 'active' : '' }}">
            <a href="{{ route('user.referral') }}" aria-expanded="false" class="dropdown-toggle">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="feather feather-share-2">
                      <circle cx="18" cy="5" r="3"></circle>
                      <circle cx="6" cy="12" r="3"></circle>
                      <circle cx="18" cy="19" r="3"></circle>
                      <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                      <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                    </svg>
                    <span>Referral</span>
              </div>
            </a>
          </li>

          <!-- Account -->
          <li class="menu {{ Request::routeIs('user.account') ? 'active' : '' }}">
            <a href="{{ route('user.account') }}" aria-expanded="false" class="dropdown-toggle">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="feather feather-user">
                      <path d="M20 21v-2a4 4 0 0 0-3-3.87"></path>
                      <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Account</span>
              </div>
            </a>
          </li>

          <!-- Setting -->
          <li class="menu">
            <a href="#" aria-expanded="false" class="dropdown-toggle">
              <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-settings">
                  <circle cx="12" cy="12" r="3"></circle>
                  <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06
                           a2 2 0 1 1-2.83 2.83l-.06-.06
                           a1.65 1.65 0 0 0-1.82-.33
                           1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09
                           a1.65 1.65 0 0 0-1-1.51
                           1.65 1.65 0 0 0-1.82.33l-.06.06
                           a2 2 0 1 1-2.83-2.83l.06-.06
                           a1.65 1.65 0 0 0 .33-1.82
                           1.65 1.65 0 0 0-1.51-1H3
                           a2 2 0 1 1 0-4h.09
                           a1.65 1.65 0 0 0 1.51-1
                           1.65 1.65 0 0 0-.33-1.82L4.27 7.27
                           a2 2 0 1 1 2.83-2.83l.06.06
                           a1.65 1.65 0 0 0 1.82.33H9
                           a1.65 1.65 0 0 0 1-1.51V3
                           a2 2 0 1 1 4 0v.09
                           a1.65 1.65 0 0 0 1 1.51
                           1.65 1.65 0 0 0 1.82-.33l.06-.06
                           a2 2 0 1 1 2.83 2.83l-.06.06
                           a1.65 1.65 0 0 0-.33 1.82V9
                           a1.65 1.65 0 0 0 1.51 1H21
                           a2 2 0 1 1 0 4h-.09
                           a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
                <span>Setting</span>
              </div>
            </a>
          </li>
        @endif
      </ul>
    </nav>
  </div>
</body>
</html>
