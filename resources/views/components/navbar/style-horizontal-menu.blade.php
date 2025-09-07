{{-- 

/**
*
* Created a new component <x-navbar.style-horizontal-menu/>.
* 
*/

--}}
<style>
    .gt_container-l20m6l a.glink span {
        font-size: 14px !important;
    }
    
    .gt_container-l20m6l {
        margin-top: 5px;
    }
    
    .theme-logo {
        width: auto;
    }
</style>
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
                    <img src="{{ asset('img/moon_logo.png') }}" class="navbar-logo logo-dark" alt="logo">
                    <img src="{{ asset('img/moon_logo.png') }}" class="navbar-logo logo-light" alt="logo">
                </a>
            </li>
            {{--<li class="nav-item theme-text">
                <a href="{{ $dashboardUrl }}" class="nav-link"> MoonExe </a>
            </li>--}}
        </ul>

        <ul class="navbar-item flex-row ms-lg-auto ms-0 action-area">
            
            <div id="gtranslate-desktop" class="d-none d-sm-block"></div>
            
            <li class="nav-item theme-toggle-item">
                  <a href="javascript:void(0);" onclick="openSupportModal()" class="dropdown-toggle">
                    <div>
                      <i class="bi bi-headset" style="font-size: 1.5rem;"></i>
                    </div>
                  </a>
            </li>
            
            <li class="nav-item theme-toggle-item">
                <a href="{{ route('user.annoucement') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-megaphone-fill" viewBox="0 0 20 20">
                      <path d="M13 2.5a1.5 1.5 0 0 1 3 0v11a1.5 1.5 0 0 1-3 0zm-1 .724c-2.067.95-4.539 1.481-7 1.656v6.237a25 25 0 0 1 1.088.085c2.053.204 4.038.668 5.912 1.56zm-8 7.841V4.934c-.68.027-1.399.043-2.008.053A2.02 2.02 0 0 0 0 7v2c0 1.106.896 1.996 1.994 2.009l.496.008a64 64 0 0 1 1.51.048m1.39 1.081q.428.032.85.078l.253 1.69a1 1 0 0 1-.983 1.187h-.548a1 1 0 0 1-.916-.599l-1.314-2.48a66 66 0 0 1 1.692.064q.491.026.966.06"/>
                    </svg>
                </a>
            </li>
            
            <li class="nav-item theme-toggle-item">
                <a href="{{ route('user.faq') }}" id="faqLinkTour">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 20 20">
                      <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                      <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
                    </svg>
                </a>
            </li>
            
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