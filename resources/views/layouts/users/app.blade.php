<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'User Dashboard')</title>
    
    <!-- External Theme CSS -->
    <link rel="stylesheet" href="{{ asset('css/users/trading.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- Bootstrap 5 CSS (from CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Chart.js (if needed) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    
    <!-- Navbar -->
    <nav class="navbar navbar-dark bg-panel px-3">
        <span class="navbar-brand mb-0 h1">MOONPAY</span>
        <!-- Right-aligned container for avatar and logout -->
        <div class="d-flex align-items-center ms-auto">
            <!-- Logout Form (using POST method for Laravel's logout route) -->
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-light">Logout</button>
            </form>
            
            <!-- Avatar: Use your own image or a placeholder -->
            <img src="{{ asset('images/avatar-black.png') }}" alt="Avatar" class="rounded-circle me-3" style="width: 40px; height: 40px; margin-left:20px;">
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row" style="min-height: calc(100vh - 56px);">
            <!-- Sidebar: Include your menu partial -->
            <div class="col-12 col-md-2 bg-panel p-3" style="min-height:100%;">
                @include('layouts.users.partials.menu')
            </div>
            <!-- Main Content -->
            <div class="col p-4">
                @yield('content')
            </div>
        </div>
    </div>

    <!-- Footer: For example, your trade notification pop-up -->
    @include('layouts.users.partials.footer')

    <!-- Bootstrap Bundle JS (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- External Theme JS -->
    <script src="{{ asset('js/users/trading.js') }}"></script>
</body>
</html>
