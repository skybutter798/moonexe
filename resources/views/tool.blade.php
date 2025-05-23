<x-base-layout :scrollspy="false">

    {{-- Set page title --}}
    <x-slot:pageTitle>
        Campaign Tool
    </x-slot>

    {{-- Optional custom styles --}}
    <x-slot:headerFiles>
        {{-- Add custom styles here if needed --}}
    </x-slot>
    
    @if(session('success'))
        <div class="alert alert-success mt-3">
            {{ session('success') }}
        </div>
    @endif

    <div class="row layout-top-spacing">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">Campaign Tool</h4>
                <a href="{{ route('tool.logout') }}" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>

            <div class="card p-4 shadow-sm rounded-4">
                <form method="POST" action="{{ route('tool.update') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center bg-light border rounded-3 p-3 mb-4">
                                <h5 class="mb-0 fw-bold">Current Campaign Balance:</h5>
                                <h3 id="campaign-balance-display" class="mb-0 text-danger">${{ number_format($settings['cam_balance'] ?? 0, 0) }} / $3,000,000</h3>
                            </div>
                        </div>


                        <div class="col-md-6">
                            <label for="minTime" class="form-label">Minimum Time (sec)</label>
                            <input type="text" id="minTime" name="cam_min_time" class="form-control" value="{{ $settings['cam_min_time'] ?? '' }}">
                        </div>

                        <div class="col-md-6">
                            <label for="maxTime" class="form-label">Maximum Time (sec)</label>
                            <input type="text" id="maxTime" name="cam_max_time" class="form-control" value="{{ $settings['cam_max_time'] ?? '' }}">
                        </div>

                        <div class="col-md-6">
                            <label for="minBuy" class="form-label">Minimum Buy Amount</label>
                            <input type="text" id="minBuy" name="cam_min_buy" class="form-control" value="{{ $settings['cam_min_buy'] ?? '' }}">
                        </div>

                        <div class="col-md-6">
                            <label for="maxBuy" class="form-label">Maximum Buy Amount</label>
                            <input type="text" id="maxBuy" name="cam_max_buy" class="form-control" value="{{ $settings['cam_max_buy'] ?? '' }}">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="amount" class="form-label">Amount</label>
                            <input type="text" id="amount" name="adjust_amount" class="form-control" placeholder="Enter amount">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label d-block">Action</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="adjust_type" id="buy" value="buy" checked>
                                <label class="form-check-label" for="add">Buy</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="adjust_type" id="return" value="return">
                                <label class="form-check-label" for="subtract">Return</label>
                            </div>
                        </div>

                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>

            {{-- Manual Section --}}
            <div class="card mt-4 p-4 shadow-sm rounded-4">
                <h5 class="fw-bold">Manual / Instructions</h5>
                <ul>
                    <li><strong>Balance</strong>: Total campaign budget.</li>
                    <li><strong>Minimum/Maximum Time</strong>: Duration range for each campaign (in seconds).</li>
                    <li><strong>Minimum/Maximum Buy</strong>: The acceptable range of buy-in per user.</li>
                    <li>Press <strong>Save Settings</strong> to apply your changes.</li>
                </ul>
            </div>
        </div>
    </div>

    <x-slot:footerFiles>

        <!-- Clear amount field after form submit -->
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.querySelector('form');
                form.addEventListener('submit', () => {
                    const amountField = document.getElementById('amount');
                    setTimeout(() => amountField.value = '', 1000);
                });
            });
        </script>
    
        <!-- Load Pusher + Echo -->
        <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/laravel-echo/1.11.3/echo.iife.js"></script>
    
        <!-- Setup Laravel Echo -->
        <script>
            window.Pusher = Pusher;
    
            window.Echo = new Echo({
                broadcaster: 'pusher',
                key: '{{ env("PUSHER_APP_KEY") }}',
                cluster: '{{ env("PUSHER_APP_CLUSTER") }}',
                forceTLS: true
            });
    
            console.log('✅ Laravel Echo initialized');
    
            Echo.channel('campaign-channel')
                .listen('.balance.updated', (e) => {
                    console.log('📡 [Pusher] CampaignBalanceUpdated received:', e);
    
                    const display = document.querySelector('#campaign-balance-display');
                    if (display) {
                        const max = 3000000;
                        const newBalance = Number(e.newBalance);
                        display.innerHTML = `<span class="text-danger">$${newBalance.toLocaleString()}</span> / $${max.toLocaleString()}`;
    
                        // Visual highlight
                        display.style.transition = 'background-color 0.3s ease';
                        display.style.backgroundColor = '#ffe599';
                        setTimeout(() => {
                            display.style.backgroundColor = 'transparent';
                        }, 800);
                    }
                });
        </script>
    
    </x-slot:footerFiles>


</x-base-layout>
