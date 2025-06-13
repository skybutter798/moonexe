{{-- usdt.blade.php --}}
<table class="table table-sm table-bordered">
    <thead><tr><th>Date</th><th>Amount</th><th>TXID</th></tr></thead>
    <tbody>
        @forelse($usdt as $row)
            <tr>
                <td>{{ $row->created_at }}</td>
                <td>{{ $row->amount }}</td>
                <td>{{ $row->external_txid }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-center">No records</td></tr>
        @endforelse
    </tbody>
</table>