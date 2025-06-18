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