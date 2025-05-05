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
    @media (min-width: 992px) {
        /* Hide mobile header on desktop screens */
        .mobile-header {
            display: none;
        }
    }

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
            <a href="{{ route('user.dashboard') }}">
              <img src="{{ asset('img/MoonExe_logo_white.png') }}" class="navbar-logo logo-dark" alt="logo">
              <img src="{{ asset('img/MoonExe_logo_black.png') }}" class="navbar-logo logo-light" alt="logo">
            </a>
          </div>
          <div class="nav-item theme-text">
            <a href="{{ route('user.dashboard') }}" class="nav-link">MOONEXE</a>
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
        <li class="menu {{ Request::routeIs('user.dashboard') ? 'active' : '' }}">
          <a href="{{ route('user.dashboard') }}" aria-expanded="false" class="dropdown-toggle">
            <div>
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                   stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                   class="feather feather-home">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
              </svg>
              <span>Home</span>
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

        <li class="menu {{ Request::routeIs('user.order') ? 'active' : '' }}">
          <a href="{{ route('user.order') }}" aria-expanded="false" class="dropdown-toggle">
            <div>
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                   stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                   class="feather feather-trending-up">
                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                <polyline points="17 6 23 6 23 12"></polyline>
              </svg>
              <span>Trade</span>
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
              <span>Profile</span>
            </div>
          </a>
        </li>
        
        <!-- Annoucement -->
        <li class="menu {{ Request::routeIs('user.annoucement') ? 'active' : '' }}">
          <a href="{{ route('user.annoucement') }}" aria-expanded="false" class="dropdown-toggle">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-volume">
                  <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                </svg>
              <span>Annoucement</span>
            </div>
          </a>
        </li>
        
        <!-- Tutorial 
        <li class="menu">
          <a href="" aria-expanded="false" class="dropdown-toggle">
            <div>
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                   stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                   class="feather feather-book-open">
                <path d="M2 7a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7z"></path>
                <path d="M22 7a2 2 0 0 0-2-2h-6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V7z"></path>
              </svg>
              <span>Tutorial</span>
            </div>
          </a>
        </li>
        
        <!-- Media 
        <li class="menu">
          <a href="" aria-expanded="false" class="dropdown-toggle">
            <div>
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                   stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                   class="feather feather-video">
                <polygon points="23 7 16 12 23 17 23 7"></polygon>
                <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
              </svg>
              <span>Media</span>
            </div>
          </a>
        </li>-->

        <!-- Setting 
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
        </li>-->
      </ul>
    </nav>
  </div>
</body>
</html>