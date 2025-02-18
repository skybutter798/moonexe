{{-- 

/**
*
* Created a new component <x-menu.horizontal-menu/>.
* This layout is used for regular users.
*
*/

--}}

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Horizontal Menu</title>
  <style>
    /* Basic styles for horizontal menu layout */
    .sidebar-wrapper {
      background: #fff;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .menu-categories {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
    }
    .menu-categories li.menu {
      margin: 0 10px;
    }
    .menu a {
      text-decoration: none;
      color: #333;
      display: flex;
      align-items: center;
      padding: 10px;
    }
    .menu a svg {
      margin-right: 5px;
    }
    .menu.active a {
      font-weight: bold;
      color: var(--accent, #e0f2fe);
    }
    /* Adjust dropdown styles as needed */
    .dropdown-menu {
      position: absolute;
      background: #fff;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
      margin-top: 5px;
      display: none;
    }
    .menu:hover .dropdown-menu {
      display: block;
    }
  </style>
</head>
<body>
  <div class="sidebar-wrapper sidebar-theme">
    <nav id="sidebar">
      <!-- Brand / Logo -->
      <div class="navbar-nav theme-brand flex-row text-center">
        <div class="nav-logo">
          <div class="nav-item theme-logo">
            <a href="{{ getRouterValue() }}/dashboard/analytics">
              <img src="{{ asset('img/dark_logo.png') }}" class="navbar-logo logo-dark" alt="logo">
              <img src="{{ asset('img/logo.png') }}" class="navbar-logo logo-light" alt="logo">
            </a>
          </div>
          <div class="nav-item theme-text">
            <a href="{{ route('dashboard') }}" class="nav-link">MOONEXE</a>
          </div>
        </div>
        <div class="nav-item sidebar-toggle">
          <div class="btn-toggle sidebarCollapse">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" class="feather feather-chevrons-left">
              <polyline points="11 17 6 12 11 7"></polyline>
              <polyline points="18 17 13 12 18 7"></polyline>
            </svg>
          </div>
        </div>
      </div>
      <div class="shadow-bottom"></div>
      <!-- USER MENU ITEMS -->
      <ul class="list-unstyled menu-categories" id="accordionExample">
        <!-- Dashboard (User) -->
        <li class="menu {{ Request::routeIs('dashboard') ? 'active' : '' }}">
          <a href="{{ route('dashboard') }}" aria-expanded="false" class="dropdown-toggle">
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

        <!-- Assets -->
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
                         a1.65 1.65 0 0 0 .33-1.82V9
                         a1.65 1.65 0 0 0 1.51-1H21
                         a2 2 0 1 1 0 4h-.09
                         a1.65 1.65 0 0 0-1.51 1z"></path>
              </svg>
              <span>Setting</span>
            </div>
          </a>
        </li>
      </ul>
    </nav>
  </div>
</body>
</html>