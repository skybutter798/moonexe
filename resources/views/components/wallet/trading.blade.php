{{-- components/wallet/trading.blade.php --}}
<table class="table table-sm table-bordered">
    <thead>
        <tr>
            <th>Date</th>
            <th>User ID</th>
            <th>Amount</th>
            <th>Remark</th>
        </tr>
    </thead>
    <tbody>
        @forelse($trading as $row)
            <tr>
                <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d M Y H:i') }}</td>
                <td>{{ $row->user_id }}</td>
                <td class="text-primary">+{{ number_format($row->amount, 2) }}</td>
                <td>{{ $row->remark ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center">No trading margin records found.</td></tr>
        @endforelse
    </tbody>
</table>