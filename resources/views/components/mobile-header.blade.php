<!-- /resources/views/components/mobile-header.blade.php -->
<div class="mobile-header">
  <style>
    /* ===========================
       BASIC STYLES FOR HEADER
       =========================== */
    .mobile-header {
      background-color: #fff;
      color: #000;
      padding: 30px 10px 10px;
      font-family: Arial, sans-serif;
    }
    .header-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .header-top .d-flex a {
      margin-right: 10px;
    }

    .header-logo img {
      max-height: 24px;
    }
    
    .header-logo {
      text-align:left;
    }

    /* ===========================
       BURGER MENU
       =========================== */
    .burger-menu {
      width: 2.5rem;              /* fixed square */
      height: 2.5rem;
      border: none;
      border-radius: 0.5rem;
      background-color: #c5c5c5;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
    }
    .burger-menu i {
      font-size: 1.5rem;          /* icon size */
      line-height: 1;
    }

    /* ===========================
       DROPDOWN MENU
       =========================== */
    .dropdown-menu {
      display: none;
      background-color: #f8f9fa;
      position: absolute;
      right: 0;
      border-radius: 0.25rem;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
      padding: 0.5rem 0;
      z-index: 999;
      min-width: 8rem;
    }
    .dropdown-menu.show {
      display: block;
    }
    .dropdown-menu a,
    .dropdown-menu li > a {
      display: flex;
      align-items: center;
      font-size: 1rem;            /* unified font size */
      padding: 0.75rem 1rem;
      color: #000;
      text-decoration: none;
    }
    .dropdown-menu a i,
    .dropdown-menu li > a i {
      font-size: 1.25rem;         /* unified icon size */
      margin-right: 0.5rem;
    }
    .dropdown-menu a:hover {
      background-color: #e2e6ea;
    }
    
    .gt_container--d8uhf3 a.glink span {
        font-size:14px !important;
    }
    
    .glink span:nth-of-type(2) {
  display: none;
}

    
    .glink img {
      width: 18px;
      height: 18px;
    }
    
    .icon-row {
      display: flex;
      align-items: center;
      gap: 12px; /* adjust spacing as needed */
    }


  </style>

  <!-- Top Row: Logo & Burger -->
  <div class="header-top">
    <div></div>
    <div class="header-logo">
      <a href="{{ route('user.dashboard') }}">
        <img src="{{ asset('img/main_logo.png') }}" alt="Main Logo">
      </a>
    </div>

    
    <div class="icon-row">
      <div id="gtranslate-mobile" class="d-block d-sm-none"></div>
    
      <a href="{{ route('user.annoucement') }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="feather feather-volume">
          <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
          <polyline points="16 17 21 12 16 7"></polyline>
        </svg>
      </a>
    
      <a href="javascript:void(0);" onclick="openSupportModal()" class="dropdown-toggle">
        <i class="bi bi-headset" style="font-size: 1.5rem;"></i>
      </a>
    
      <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="feather feather-log-out">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
          <polyline points="16 17 21 12 16 7"></polyline>
          <line x1="21" y1="12" x2="9" y2="12"></line>
        </svg>
      </a>
    </div>

    
    <!--<button class="burger-menu" onclick="toggleMenu()">
      <i class="bi bi-list"></i>
    </button>-->
  </div>
    
  
  
  <!--<div class="dropdown-menu" id="mobileDropdown" onclick="toggleMenu()">
    <hr>
  </div>-->

  <!-- Hidden logout form -->
  <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
  </form>

    <!-- Bottom Row: Currency Marquee -->
    <div class="header-marquee mt-4">
        <marquee behavior="scroll" direction="left" scrollamount="3">
            <div class="currency-item">
                <img src="https://app.moonexe.com/img/1x1/gb.svg" alt="GB Flag" class="flag-icon">
                <span class="pair">USDT / GBP</span>
                <span class="price">1.69</span>
                <span class="arrow up">▲</span>
            </div>

            <div class="currency-item">
                <img src="https://app.moonexe.com/img/1x1/us.svg" alt="US Flag" class="flag-icon">
                <span class="pair">USDT / USD</span>
                <span class="price">1.00</span>
                <span class="arrow up">▲</span>
            </div>

            <div class="currency-item">
                <img src="https://app.moonexe.com/img/1x1/th.svg" alt="TH Flag" class="flag-icon">
                <span class="pair">USDT / THB</span>
                <span class="price">33.5</span>
                <span class="arrow up">▲</span>
            </div>

            <div class="currency-item">
                <img src="https://app.moonexe.com/img/1x1/au.svg" alt="AU Flag" class="flag-icon">
                <span class="pair">USDT / AUD</span>
                <span class="price">1.45</span>
                <span class="arrow up">▲</span>
            </div>
        </marquee>
    </div>
</div>

<!--<script>
  function toggleMenu() {
    document.getElementById('mobileDropdown').classList.toggle('show');
  }
</script>-->
