{{-- 

/**
*
* Created a new component <x-base-layout/>.
* 
*/

--}}

@php
    $isBoxed = layoutConfig()['boxed'];
    $isAltMenu = layoutConfig()['alt-menu']; 
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <meta name="format-detection" content="telephone=no">

    <title>{{ $pageTitle }}</title>
    <!-- Favicon and Touch Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('/css/users/mobile-menu_v2.css') }}">
    <script>
        // Record the start time of loading as soon as possible.
        window.loaderStartTime = performance.now();
    </script>
    @vite(['resources/scss/layouts/modern-light-menu/light/loader.scss'])

    @if (Request::is('modern-light-menu/*'))
        @vite(['resources/layouts/modern-light-menu/loader.js'])
    @elseif ((Request::is('modern-dark-menu/*')))
        @vite(['resources/layouts/modern-dark-menu/loader.js'])
    @elseif ((Request::is('collapsible-menu/*')))
        @vite(['resources/layouts/collapsible-menu/loader.js'])
    @elseif ((Request::is('horizontal-light-menu/*')))
        @vite(['resources/layouts/horizontal-light-menu/loader.js'])
    @elseif ((Request::is('horizontal-dark-menu/*')))
        @vite(['resources/layouts/horizontal-dark-menu/loader.js'])
    @else
        @vite(['resources/layouts/modern-light-menu/loader.js'])
    @endif
    
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('plugins/bootstrap/bootstrap.min.css') }}">
    @vite(['resources/scss/light/assets/main.scss', 'resources/scss/dark/assets/main.scss'])

    @if (
            !Request::routeIs('404') &&
            !Request::routeIs('maintenance') &&
            !Request::routeIs('signin') &&
            !Request::routeIs('signup') &&
            !Request::routeIs('lockscreen') &&
            !Request::routeIs('password-reset') &&
            !Request::routeIs('2Step') &&

            // Real Logins
            !Request::routeIs('login')
        )
        @if ($scrollspy == 1)
            @vite(['resources/scss/light/assets/scrollspyNav.scss', 'resources/scss/dark/assets/scrollspyNav.scss'])
        @endif
        <link rel="stylesheet" type="text/css" href="{{ asset('plugins/waves/waves.min.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('plugins/highlight/styles/monokai-sublime.css') }}">
        @vite([ 'resources/scss/light/plugins/perfect-scrollbar/perfect-scrollbar.scss'])

        @if (Request::is('user-dashboard/*'))
            @vite([
                'resources/scss/layouts/horizontal-dark-menu/light/structure.scss',
                'resources/scss/layouts/horizontal-dark-menu/dark/structure.scss',
            ])
        @elseif (Request::is('modern-light-menu/*'))
            @vite([
                'resources/scss/layouts/modern-light-menu/light/structure.scss',
                'resources/scss/layouts/modern-light-menu/dark/structure.scss',
            ])
        @elseif (Request::is('modern-dark-menu/*'))
            @vite([
                'resources/scss/layouts/modern-dark-menu/light/structure.scss',
                'resources/scss/layouts/modern-dark-menu/dark/structure.scss',
            ])
        @elseif (Request::is('collapsible-menu/*'))
            @vite([
                'resources/scss/layouts/collapsible-menu/light/structure.scss',
                'resources/scss/layouts/collapsible-menu/dark/structure.scss',
            ])
        @elseif (Request::is('horizontal-light-menu/*'))
            @vite([
                'resources/scss/layouts/horizontal-light-menu/light/structure.scss',
                'resources/scss/layouts/horizontal-light-menu/dark/structure.scss',
            ])
        @elseif (Request::is('horizontal-dark-menu/*'))
            @vite([
                'resources/scss/layouts/horizontal-dark-menu/light/structure.scss',
                'resources/scss/layouts/horizontal-dark-menu/dark/structure.scss',
            ])
        @else
            @vite([
                'resources/scss/layouts/modern-light-menu/light/structure.scss',
                'resources/scss/layouts/modern-light-menu/dark/structure.scss',
            ])
        @endif

    @endif
    
    <!-- BEGIN GLOBAL MANDATORY STYLES -->
    {{$headerFiles}}
    <!-- END GLOBAL MANDATORY STYLES -->
    
    <script>
      window.gtranslateSettings = {
        default_language: "en",
        languages: ["en","cs","fr","de","it","pt","ro","es","tr","ja","ko","vi","id","hi"],
        // ← we’ll override this per-wrapper below
      };
    </script>
    <script src="https://cdn.gtranslate.net/widgets/latest/popup.js" defer></script>
    <script>
      // pick desktop vs mobile based on screen width & set wrapper_selector once
      if (window.matchMedia("(min-width: 576px)").matches) {
        window.gtranslateSettings.wrapper_selector = "#gtranslate-desktop";
      } else {
        window.gtranslateSettings.wrapper_selector = "#gtranslate-mobile";
      }
    </script>

    
    <link rel="stylesheet" href="{{ asset('css/custom_v2.css') }}">
</head>
<div id="supportModal" style="display:none; position:fixed; z-index:9999; inset:0; background:rgba(0,0,0,0.5);">
  <div style="background:white; max-width:500px; margin:5% auto; padding:20px; border-radius:8px; position:relative;">
    <h5>Contact Support</h5>
    <form method="POST" action="{{ route('user.contact.support') }}">
      @csrf
      <div class="mb-3">
        <label>Username</label>
        <input type="text" name="username" class="form-control" value="{{ Auth::user()->name ?? '' }}" required>
      </div>
      <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" value="{{ Auth::user()->email ?? '' }}" required>
      </div>
      <div class="mb-3">
        <label>Subject</label>
        <input type="text" name="subject" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Question</label>
        <textarea name="question" class="form-control" rows="4" required></textarea>
      </div>
      <small class="text-muted d-block mb-3">
        Thank you for reaching out. Due to high support volume, our response time may take between 24 to 48 hours.
      </small>
      <button type="submit" class="btn btn-primary">Submit</button>
      <button type="button" class="btn btn-secondary" onclick="closeSupportModal()">Cancel</button>
    </form>
  </div>
</div>

<body @class([
        // 'layout-dark' => $isDark,
        'layout-boxed' => $isBoxed,
        'alt-menu' => ($isAltMenu || Request::routeIs('collapsibleMenu') ? true : false),
        'error' => (Request::routeIs('404') ? true : false),
        'maintanence' => (Request::routeIs('maintenance') ? true : false),
    ]) @if ($scrollspy == 1) {{ $scrollspyConfig }} @else {{''}} @endif @if (Request::routeIs('fullWidth')) layout="full-width"  @endif >

    <!-- BEGIN LOADER -->
    <x-layout-loader/>
    <!--  END LOADER -->

    {{--
        
    /*
    *
    *   Check if the routes are not single pages (which do not contain sidebar or topbar) such as:
    *   - 404
    *   - maintenance
    *   - authentication
    *
    */
    --}}

    @if (
            !Request::routeIs('404') &&
            !Request::routeIs('maintenance') &&
            !Request::routeIs('signin') &&
            !Request::routeIs('signup') &&
            !Request::routeIs('lockscreen') &&
            !Request::routeIs('password-reset') &&
            !Request::routeIs('2Step') &&

            // Real Logins
            !Request::routeIs('login')
        )

        @if (!Request::routeIs('blank'))
            @if (Request::is('user-dashboard/*'))
                <!-- Desktop Navbar -->
                <x-navbar.style-horizontal-menu class="desktop-menu"/>
                <!-- Mobile Header -->
                <x-mobile-header/>
                <!-- Mobile Menu -->
                <x-user-mobile-menu_v2/>
            @elseif (Request::is('modern-light-menu/*'))
                <!--  BEGIN NAVBAR  -->
                <x-navbar.style-vertical-menu classes="{{ ($isBoxed ? 'container-xxl' : '') }}"/>
                <!--  END NAVBAR  -->
            @elseif (Request::is('modern-dark-menu/*'))
                <x-navbar.style-vertical-menu classes="{{ ($isBoxed ? 'container-xxl' : '') }}"/>
            @elseif (Request::is('collapsible-menu/*'))
                <x-navbar.style-vertical-menu classes="{{ ($isBoxed ? 'container-xxl' : '') }}"/>
            @elseif (Request::is('horizontal-light-menu/*'))
                <x-navbar.style-horizontal-menu/>
            @elseif (Request::is('horizontal-dark-menu/*'))
                <x-navbar.style-horizontal-menu/>
            @else
                <x-navbar.style-vertical-menu classes="{{ ($isBoxed ? 'container-xxl' : '') }}"/>
            @endif
        
        @endif

        <!--  BEGIN MAIN CONTAINER  -->
        <div class="main-container" id="container">
            
            <!--  BEGIN LOADER  -->
            <x-layout-overlay/>
            <!--  END LOADER  -->

            @if (!Request::routeIs('blank')) 
                @if (Request::is('user-dashboard/*'))
                    <!--  BEGIN SIDEBAR for Horizontal Dark Menu -->
                    <x-menu.horizontal-menu class="desktop-menu"/>
                    <!--  END SIDEBAR  --> 
                @elseif (Request::is('modern-light-menu/*'))
                    <x-menu.vertical-menu/>
                @elseif (Request::is('modern-dark-menu/*'))
                    <x-menu.vertical-menu/>
                @elseif (Request::is('collapsible-menu/*'))
                    <x-menu.vertical-menu/>
                @elseif (Request::is('horizontal-light-menu/*'))
                    <x-menu.horizontal-menu/>
                @elseif (Request::is('horizontal-dark-menu/*'))
                    <x-menu.horizontal-menu/>
                @else
                    <x-menu.vertical-menu/>
                @endif
              
            @endif

            <!--  BEGIN CONTENT AREA  -->
            <div id="content" class="main-content {{ (Request::routeIs('blank') ? 'ms-0 mt-0' : '') }}">

                @if ($scrollspy == 1)
                    <div class="container">
                        <div class="container">
                            {{ $slot }}
                        </div>
                    </div>                
                @else
                    <div class="layout-px-spacing">
                        <div class="middle-content {{ ($isBoxed ? 'container-xxl' : '') }} p-0">
                            {{ $slot }}
                        </div>
                    </div>
                @endif

                <!--  BEGIN FOOTER  -->
                <x-layout-footer/>
                <!--  END FOOTER  -->
                
            </div>
            <!--  END CONTENT AREA  -->

        </div>
        <!--  END MAIN CONTAINER  -->
        
    @else
        {{ $slot }}
    @endif

    @if (
            !Request::routeIs('404') &&
            !Request::routeIs('maintenance') &&
            !Request::routeIs('signin') &&
            !Request::routeIs('signup') &&
            !Request::routeIs('lockscreen') &&
            !Request::routeIs('password-reset') &&
            !Request::routeIs('2Step') &&

            // Real Logins
            !Request::routeIs('login')
        )
        <!-- BEGIN GLOBAL MANDATORY SCRIPTS -->
        <script src="{{ asset('plugins/bootstrap/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('plugins/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>
        <script src="{{ asset('plugins/mousetrap/mousetrap.min.js') }}"></script>
        <script src="{{ asset('plugins/waves/waves.min.js') }}"></script>
        <script src="{{ asset('plugins/highlight/highlight.pack.js') }}"></script>
        @if ($scrollspy == 1)
            @vite(['resources/assets/js/scrollspyNav.js'])
        @endif

        @if (Request::is('modern-light-menu/*'))
            @vite(['resources/layouts/modern-light-menu/app.js'])
        @elseif (Request::is('modern-dark-menu/*'))
            @vite(['resources/layouts/modern-dark-menu/app.js'])
        @elseif (Request::is('collapsible-menu/*'))
            @vite(['resources/layouts/collapsible-menu/app.js'])
        @elseif (Request::is('horizontal-light-menu/*'))
            @vite(['resources/layouts/horizontal-light-menu/app.js'])
        @elseif (Request::is('horizontal-dark-menu/*'))
            @vite(['resources/layouts/horizontal-dark-menu/app.js'])
        @else 
            @vite(['resources/layouts/modern-light-menu/app.js'])
        @endif
        <!-- END GLOBAL MANDATORY SCRIPTS -->

    @endif
         
        {{ $footerFiles }}
</body>

<script>
  function openSupportModal() {
    document.getElementById('supportModal').style.display = 'block';
  }

  function closeSupportModal() {
    document.getElementById('supportModal').style.display = 'none';
  }

  // Optional: close modal when clicking outside
  window.onclick = function(event) {
    const modal = document.getElementById('supportModal');
    if (event.target === modal) {
      closeSupportModal();
    }
  }
</script>

</html>