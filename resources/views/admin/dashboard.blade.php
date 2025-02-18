<x-base-layout :scrollspy="false">

    <x-slot:pageTitle>
        {{$title}} 
    </x-slot>

    <!-- BEGIN GLOBAL MANDATORY STYLES -->
    <x-slot:headerFiles>
        <!-- BEGIN CUSTOM STYLE FILE -->
        <link rel="stylesheet" href="{{ asset('plugins/apex/apexcharts.css') }}">

        @vite(['resources/scss/light/assets/components/list-group.scss'])
        @vite(['resources/scss/light/assets/widgets/modules-widgets.scss'])
        @vite(['resources/scss/dark/assets/components/list-group.scss'])
        @vite(['resources/scss/dark/assets/widgets/modules-widgets.scss'])
        <!-- END CUSTOM STYLE FILE -->

        <!-- Custom Shortcut Button Styles -->
        <style>
            /* Flexbox Example */
            .shortcut-buttons {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
            }
            .shortcut-btn {
                flex: 1 1 calc(20% - 1rem);
                margin: 0.5rem;
                text-align: center;
                min-width: 120px;
                background: #fff;
                border: 1px solid #ccc;
                border-radius: 5px;
                padding: 10px;
                text-decoration: none;
                color: #333;
                transition: background-color 0.2s ease-in-out;
            }
            .shortcut-btn:hover {
                background-color: #f2f2f2;
            }
            @media (max-width: 768px) {
                .shortcut-btn {
                    flex: 1 1 calc(50% - 1rem);
                }
            }
            @media (max-width: 480px) {
                .shortcut-btn {
                    flex: 1 1 100%;
                }
            }
        </style>
    </x-slot>
    <!-- END GLOBAL MANDATORY STYLES -->

    <!-- Analytics -->
    <div class="row layout-top-spacing">
        
        <!-- Shortcut Buttons Row -->
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
            <div class="d-flex flex-wrap shortcut-buttons">
                <a href="{{ route('packages.index') }}" class="shortcut-btn">Plans</a>
                <a href="#" class="shortcut-btn">Currencies</a>
                <a href="#" class="shortcut-btn">Payout</a>
                <a href="#" class="shortcut-btn">Deposit &amp; Withdrawal</a>
                <a href="#" class="shortcut-btn">Wallet Control</a>
                <a href="{{ route('users.index') }}" class="shortcut-btn">Staff &amp; Users</a>
                <a href="#" class="shortcut-btn">Transaction</a>
                <a href="#" class="shortcut-btn">Payout</a>
                <a href="#" class="shortcut-btn">P/L</a>
            </div>
        </div>
        
        <div class="col-xl-8 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
            <x-widgets._w-chart-one title="Revenue"/>
        </div>
        
        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
            <x-widgets._w-wallet-one title="Total Balance"/>
        </div>
    
        <div class="col-xl-8 col-lg-12 col-md-12 col-sm-12 col-12">
            <x-widgets._w-hybrid-one title="Registered Users" chart-id="hybrid_followers"/>
        </div>

        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
            <x-widgets._w-activity-five title="Activity Log"/>
        </div>

    </div>
    
    <!-- BEGIN CUSTOM SCRIPTS FILE -->
    <x-slot:footerFiles>
        <script src="{{ asset('plugins/apex/apexcharts.min.js') }}"></script>
        
        {{-- Analytics --}}
        @vite(['resources/assets/js/widgets/_wSix.js'])
        @vite(['resources/assets/js/widgets/_wChartThree.js'])
        @vite(['resources/assets/js/widgets/_wHybridOne.js'])
        @vite(['resources/assets/js/widgets/_wActivityFive.js'])
        @vite(['resources/assets/js/widgets/_wTwo.js'])
        @vite(['resources/assets/js/widgets/_wOne.js'])
        @vite(['resources/assets/js/widgets/_wChartOne.js'])
        @vite(['resources/assets/js/widgets/_wChartTwo.js'])
        @vite(['resources/assets/js/widgets/_wActivityFour.js'])
    </x-slot>
    <!-- END CUSTOM SCRIPTS FILE -->
</x-base-layout>
