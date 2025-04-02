<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<div class="mobile-footer-menu">
    <ul>
        <li><a href="{{ route('user.dashboard') }}"><i class="bi bi-house bi-primary"></i></a></li>
        <li><a href="{{ route('user.assets') }}"><i class="bi bi-wallet2 bi-primary"></i></a></li>
        
        <!-- Custom Order Icon in the Center -->
        <li class="menu-center">
            <a href="{{ route('user.order') }}">
                <div class="order-icon">
                    <img src="{{ asset('img/MoonExe_icon.png') }}" alt="Order" class="custom-order-icon">
                </div>
            </a>
        </li>

        <li><a href="{{ route('user.referral') }}"><i class="bi bi-person-add bi-primary"></i></a></li>
        <li><a href="{{ route('user.account') }}"><i class="bi bi-person bi-primary"></i></a></li>
    </ul>
</div>
