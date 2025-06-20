<table class="table table-sm table-bordered">
    <thead><tr><th>ID</th><th>Txid</th><th>Amount</th><th>Hash</th><th>Date</th></tr></thead>
    <tbody>
        @forelse($usdt as $row)
            <tr>
                <td>{{ $row->id }}</td>
                <td>{{ $row->txid }}</td>
                <td class="text-success">{{ $row->amount }}</td>
                <td>
                    <a href="https://tronscan.io/#/transaction/{{ $row->external_txid }}" target="_blank">
                        {{ Str::limit($row->external_txid, 12, '...') }}
                    </a>
                </td>
                <td>{{ $row->created_at }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-center">No records</td></tr>
        @endforelse
    </tbody>
</table>
