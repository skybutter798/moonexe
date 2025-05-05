{{-- 

/**
*
* Created a new component <x-navbar.style-horizontal-menu/>.
* 
*/

--}}

<!--  BEGIN NAVBAR  -->
<div class="header-container container-xxl desktop-menu">
    <header class="header navbar navbar-expand-sm expand-header">

        <!--<a href="javascript:void(0);" class="sidebarCollapse" data-placement="bottom"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-menu"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg></a>-->
        @php
            // Determine the dashboard URL based on the user's role.
            $dashboardUrl = auth()->check() && auth()->user()->role === 'admin'
                ? route('admin.dashboard') // Or a custom URL like '/admin/dashboard/analytics'
                : route('user.dashboard'); // Or a custom URL like '/user/dashboard/analytics'
        @endphp
        <ul class="navbar-item theme-brand flex-row  text-center">
            <li class="nav-item theme-logo">
                <a href="{{ $dashboardUrl }}">
                    <img src="{{ asset('img/MoonExe_logo_white.png') }}" class="navbar-logo logo-dark" alt="logo">
                    <img src="{{ asset('img/MoonExe_logo_black.png') }}" class="navbar-logo logo-light" alt="logo">
                </a>
            </li>
            <li class="nav-item theme-text">
                <a href="{{ $dashboardUrl }}" class="nav-link"> MoonExe </a>
            </li>
        </ul>

        <ul class="navbar-item flex-row ms-lg-auto ms-0 action-area">
            
            <div id="gtranslate-desktop" class="d-none d-sm-block"></div>
            
            <li class="nav-item theme-toggle-item">
                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="bi bi-box-arrow-right" style="font-size:25px"></i> <span style="font-size:20px"></span>
                </a>
                <!-- Hidden logout form -->
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </li>
        </ul>
        
    </header>
</div>

<!--  END NAVBAR  -->