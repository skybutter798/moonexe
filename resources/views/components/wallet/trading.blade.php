{{-- components/wallet/trading.blade.php --}}
<table class="table table-sm table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Txid</th>
            <th>Amount</th>
            <th>Remark</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @forelse($trading as $row)
            <tr>
                <td>{{ $row->id }}</td>
                <td>{{ $row->txid }}</td>
                <td class="text-success">+{{ number_format($row->amount, 2) }}</td>
                <td>{{ $row->remark ?? '-' }}</td>
                <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d M Y H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center">No trading margin records found.</td></tr>
        @endforelse
    </tbody>
</table>