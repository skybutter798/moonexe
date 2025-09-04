<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>Wallet History</x-slot:pageTitle>
    
    <x-slot:headerFiles>
        @vite(['resources/scss/light/assets/components/modal.scss'])
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    </x-slot:headerFiles>

    <div class="container mt-4">
        <h1 class="h4 mb-4">Wallet Transaction History</h1>
        <p>User: <strong>{{ $user->name }}</strong> | Wallet: <strong>{{ ucfirst(str_replace('_', ' ', $walletType)) }}</strong></p>

        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>TXID</th>
                        <th>Direction</th>
                        <th>Amount</th>
                        <th>Fee</th>
                        <th>Method</th>
                        <th>Remark</th> <!-- New -->
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($history as $row)
                        <tr>
                            <td>{{ $row['date'] }}</td>
                            <td>{{ $row['txid'] }}</td>
                            <td> @if ($row['direction'] === 'In' || $row['direction'] === 'Out') <span class="badge {{ $row['direction'] === 'In' ? 'bg-success' : 'bg-danger' }}"> {{ $row['direction'] }} </span> @else <span class="badge bg-secondary">{{ $row['direction'] }}</span> @endif </td>
                            <td> @if ($row['direction'] === 'In' || $row['direction'] === 'Out') <span class="badge {{ $row['direction'] === 'In' ? 'bg-success' : 'bg-danger' }} text-white"> {{ $row['amount'] }} </span> @else <span class="badge bg-secondary text-white">{{ $row['amount'] }}</span> @endif </td>
                            <td>{{ $row['fee'] }}</td>
                            <td>{{ $row['method'] }}</td>
                            <td>{{ $row['remark'] ?? '-' }}</td> <!-- New -->
                            <td>{{ $row['balance'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No transactions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <a href="{{ route('admin.tools.index') }}" class="btn btn-secondary mt-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Tools
        </a>
    </div>
    
    <x-slot:footerFiles>
    </x-slot:footerFiles>
</x-base-layout>