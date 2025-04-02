<div class="mobile-header">
    <!-- Top Row: Logo in the Middle & Burger Menu on the Right -->
    <div class="header-top">
        <div class="header-left"></div>
        <div class="header-logo">
            <img src="{{ asset('img/main_logo.png') }}" alt="Main Logo">
        </div>
        <div class="header-right">
            <button class="burger-menu">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </div>

    <!-- Bottom Row: Currency Marquee -->
    <div class="header-marquee mb-3">
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
    
    <span class="text-danger mt-3">Moonexe is currently undergoing version updates. The layout may change gradually, and an announcement will be made once the update is complete.</span>
</div>
