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
      height: 45px;
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
  <script src="{{ asset('js/users/intro-steps.js') }}"></script>

  <!-- Top Row: Logo & Burger -->
  <div class="header-top">
    <div></div>
    <div class="header-logo">
      <a href="{{ route('user.dashboard') }}">
        <img src="{{ asset('img/moon_logo.png') }}" alt="Main Logo">
      </a>
    </div>

    
    <div class="icon-row">
      <div id="gtranslate-mobile" class="d-block d-sm-none"></div>
            
            <a href="javascript:void(0);" onclick="openSupportModal()" class="dropdown-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-headset" viewBox="0 0 20 20">
                    <path d="M8 1a5 5 0 0 0-5 5v1h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a6 6 0 1 1 12 0v6a2.5 2.5 0 0 1-2.5 2.5H9.366a1 1 0 0 1-.866.5h-1a1 1 0 1 1 0-2h1a1 1 0 0 1 .866.5H11.5A1.5 1.5 0 0 0 13 12h-1a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1h1V6a5 5 0 0 0-5-5"/>
                </svg>
            </a>
          
            <a href="{{ route('user.annoucement') }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-megaphone-fill" viewBox="0 0 20 20">
                  <path d="M13 2.5a1.5 1.5 0 0 1 3 0v11a1.5 1.5 0 0 1-3 0zm-1 .724c-2.067.95-4.539 1.481-7 1.656v6.237a25 25 0 0 1 1.088.085c2.053.204 4.038.668 5.912 1.56zm-8 7.841V4.934c-.68.027-1.399.043-2.008.053A2.02 2.02 0 0 0 0 7v2c0 1.106.896 1.996 1.994 2.009l.496.008a64 64 0 0 1 1.51.048m1.39 1.081q.428.032.85.078l.253 1.69a1 1 0 0 1-.983 1.187h-.548a1 1 0 0 1-.916-.599l-1.314-2.48a66 66 0 0 1 1.692.064q.491.026.966.06"/>
                </svg>
            </a>
            
            <a href="{{ route('user.faq') }}" id="faqLinkTour">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 20 20">
                  <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                  <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
                </svg>
            </a>
            
            <a href="{{ route('user.faq') }}" id="faqLinkTour">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 20 20">
                  <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                  <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
                </svg>
            </a>
    
            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/>
                    <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/>
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
