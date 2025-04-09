<!-- /resources/views/components/mobile-header.blade.php -->
<div class="mobile-header">
    <style>
        /* ===========================
           BASIC STYLES FOR HEADER
           =========================== */
        .mobile-header {
            background-color: #fff;
            color: #000;
            /*border-bottom: 1px solid #ccc;*/
            padding: 30px 10px 10px 10px;
            font-family: Arial, sans-serif;
        }

        /* Top row styles */
        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .header-left {
            width: 10px;
        }
        
        .header-logo {
            text-align:left;
        }
        
        .header-logo img {
            max-height: 20px;
        }
        .burger-menu {
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            background-color: #c5c5c5;
            border-radius: 10px;
            width: inherit;
        }
        
        /* Marquee area */
        .header-marquee {
            margin-top: 0.5rem;
        }
        .currency-item {
            display: inline-flex;
            align-items: center;
            margin-right: 1rem;
        }
        .flag-icon {
            width: 16px;
            height: 16px;
            margin-right: 4px;
        }
        .arrow {
            margin-left: 4px;
            font-weight: bold;
        }
        .arrow.up {
            color: green;
        }
        .arrow.down {
            color: red;
        }

        /* Notice text */
        .text-danger {
            display: block;
            margin-top: 0.5rem;
            color: #dc3545;
            font-size: 0.875rem;
        }

        /* ===========================
           TOGGLE MENU STYLES
           =========================== */
        .dropdown-menu {
            display: none;
            background-color: #f8f9fa;
            position: absolute;
            right: 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            border-radius: 4px;
            padding: 0.5rem 0;
            z-index: 999;
        }
        .dropdown-menu.show {
            display: block;
        }
        .dropdown-menu a {
            display: block;
            padding: 0.5rem 1rem;
            color: #000;
            text-decoration: none;
        }
        
        .dropdown-menu a i {
            margin-right: 0.5rem;
        }
        .dropdown-menu a:hover {
            background-color: #e2e6ea;
        }
    </style>

    <!-- Top Row: Logo in the Middle & Burger Menu on the Right -->
    <div class="header-top">
        <div class="header-left"></div>
        <div class="header-logo">
            <img src="{{ asset('img/main_logo.png') }}" alt="Main Logo">
        </div>
        <div class="header-right">
            <button class="burger-menu" onclick="toggleMenu()">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </div>

    <!-- Dropdown Menu (Hidden by default) -->
    <div class="dropdown-menu" id="mobileDropdown">
        <!-- Other menu items can go here -->
        <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>

    </div>
    
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

    {{--<span class="text-danger mt-3">
        Moonexe is currently undergoing version updates. The layout may change gradually, 
        and an announcement will be made once the update is complete.
    </span>--}}
</div>

<!-- Inline JavaScript to handle toggling of the dropdown -->
<script>
    function toggleMenu() {
        const dropdown = document.getElementById('mobileDropdown');
        dropdown.classList.toggle('show');
    }
</script>
