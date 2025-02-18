<div class="mb-4 fw-bold text-white">Menu</div>
<ul class="nav flex-column">
  <li class="nav-item mb-1">
    <a href="{{ route('dashboard') }}" class="nav-link text-white-50 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
      Dashboard
    </a>
  </li>
  <li class="nav-item mb-1">
    <a href="{{ route('user.assets') }}" class="nav-link text-white-50 {{ request()->routeIs('user.assets') ? 'active' : '' }}">
      Assets
    </a>
  </li>
  <li class="nav-item mb-1">
    <a href="{{ route('user.order') }}" class="nav-link text-white-50 {{ request()->routeIs('user.order') ? 'active' : '' }}">
      Order
    </a>
  </li>
  <li class="nav-item mb-1">
    <a href="{{ route('user.referral') }}" class="nav-link text-white-50 {{ request()->routeIs('user.referral') ? 'active' : '' }}">
      Referral
    </a>
  </li>
  <li class="nav-item mb-1">
    <a href="{{ route('user.account') }}" class="nav-link text-white-50 {{ request()->routeIs('user.account') ? 'active' : '' }}">
      Account
    </a>
  </li>
  <li class="nav-item mb-1">
    <!-- If you have a settings page, include it -->
    <a href="" class="nav-link text-white-50">
      Setting
    </a>
  </li>
</ul>
