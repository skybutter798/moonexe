{{-- components/wallet/earning.blade.php --}}
<table class="table table-sm table-bordered">
    <thead>
        <tr>
            <th>Date</th>
            <th>Order ID</th>
            <th>Amount</th>
            <th>Source</th>
        </tr>
    </thead>
    <tbody>
        @forelse($roi as $row)
            <tr>
                <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d M Y H:i') }}</td>
                <td>{{ $row->order_id ?? '-' }}</td>
                <td class="text-success">+{{ number_format($row->actual, 2) }}</td>
                <td>{{ $row->remark ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center">No earning payouts found.</td></tr>
        @endforelse
    </tbody>
</table>