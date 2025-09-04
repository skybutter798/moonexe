<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>Wallet Tools</x-slot:pageTitle>

    <x-slot:headerFiles>
        @vite(['resources/scss/light/assets/components/modal.scss'])
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    </x-slot:headerFiles>

    <div class="container mt-4">
        <h1 class="h4 mb-4">Wallet Tools</h1>

        {{-- Tool 1: Wallet Report --}}
        <form method="POST" action="{{ route('admin.tools.walletReport') }}" class="mb-5 row g-3">
            @csrf
            <div class="col-md-6">
                <label for="user_range" class="form-label fw-semibold">User ID or Name(s) for Wallet Report</label>
                <input type="text" id="user_range" name="user_range" class="form-control" placeholder="e.g. 100,alex,103" required>
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-table me-1"></i> Run Wallet Report
                </button>
            </div>
        </form>

        {{-- Tool 2: Real Wallet Breakdown --}}
        <form method="POST" action="{{ route('admin.tools.realWalletBreakdown') }}" class="mb-5 row g-3">
            @csrf
            <div class="col-md-6">
                <label for="user_key" class="form-label fw-semibold">User ID or Name for Real Wallet Breakdown</label>
                <input type="text" id="user_key" name="user_key" class="form-control" placeholder="e.g. john or 123">
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-graph-up me-1"></i> Run Real Wallet Breakdown
                </button>
            </div>
        </form>
        
        {{-- Tool 3: Wallet Flow Report --}}
        <form method="POST" action="{{ route('admin.tools.walletFlowReport') }}" class="mb-5 row g-3">
            @csrf
            <div class="col-md-6">
                <label for="flow_user" class="form-label fw-semibold">User ID or Name for Wallet Flow</label>
                <input type="text" id="flow_user" name="flow_user" class="form-control" placeholder="e.g. sky798 or 145" required>
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-warning text-dark">
                    <i class="bi bi-cash-stack me-1"></i> Run Wallet Flow Report
                </button>
            </div>
        </form>
        
        {{-- Tool 4: Wallet Transaction History --}}
        <form method="POST" action="{{ route('admin.tools.history') }}" class="mb-5 row g-3">
            @csrf
            <div class="col-md-4">
                <label for="user_key_history" class="form-label fw-semibold">User ID / Name / Email</label>
                <input type="text" id="user_key_history" name="user_key" class="form-control" placeholder="e.g. 102, john, user@example.com" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold d-block">Options</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="group_by_date" id="group_by_date" value="1">
                    <label class="form-check-label" for="group_by_date">Group by Date</label>
                </div>
            </div>
            <div class="col-md-4">
                <label for="wallet_type" class="form-label fw-semibold">Select Wallet</label>
                <select name="wallet_type" id="wallet_type" class="form-select" required>
                    <option value="">-- Choose Wallet --</option>
                    <option value="cash_wallet">Cash Wallet</option>
                    <option value="trading_wallet">Trading Wallet</option>
                    <option value="earning_wallet">Earning Wallet</option>
                    <option value="affiliates_wallet">Affiliates Wallet</option>
                </select>
            </div>
            <div class="col-md-4 align-self-end">
                <button type="submit" class="btn btn-dark w-100">
                    <i class="bi bi-clock-history me-1"></i> Wallet Transaction History
                </button>
            </div>
        </form>

        {{-- Output --}}
        @isset($output)
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Command Output</h5>
                    <pre class="bg-light p-3 rounded text-sm">{!! nl2br(e($output)) !!}</pre>
                </div>
            </div>
        @endisset

    </div>

    <x-slot:footerFiles>
    </x-slot:footerFiles>
</x-base-layout>