<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<div class="mobile-footer-menu">
    <ul>
        <!-- Home -->
        <li>
            <a href="{{ route('user.dashboard') }}">
                <i class="bi bi-house-fill bi-primary"></i>
                <span>Home</span>
            </a>
        </li>
        
        <!-- Assets -->
        <li>
            <a href="{{ route('user.assets') }}">
                <i class="bi bi-coin bi-primary"></i>
                <span>Assets</span>
            </a>
        </li>
        
        <!-- Trade (Center) -->
        <li>
            <a href="{{ route('user.order') }}">
                <i class="bi bi-bar-chart-line bi-primary"></i>
                <span>Trade</span>
            </a>
        </li>
        
        <!-- Referral -->
        <li>
            <a href="{{ route('user.referral') }}">
                <img src="{{ asset('img/referral_icon.png') }}" alt="Referral Icon" class="menu-icon" style="margin-bottom 4px;">
                <span>Referral</span>
            </a>
        </li>

        
        <!-- Profile -->
        <li>
            <a href="{{ route('user.account') }}">
                <i class="bi bi-person-fill bi-primary"></i>
                <span>Profile</span>
            </a>
        </li>
    </ul>
</div>
