{{-- components/wallet/affiliate.blade.php --}}
<table class="table table-sm table-bordered">
    <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Amount</th>
            <th>Source</th>
        </tr>
    </thead>
    <tbody>
        @forelse($affiliate as $row)
            <tr>
                <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d M Y H:i') }}</td>
                <td>{{ ucfirst($row->type) }}</td>
                <td class="text-success">+{{ number_format($row->actual, 2) }}</td>
                <td>{{ $row->remark ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center">No affiliate payouts found.</td></tr>
        @endforelse
    </tbody>
</table>