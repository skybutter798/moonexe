<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Amount</th>
            <th>Fee</th>
            <th>TRC20 Address</th>
            <th>TXID</th>
            <th>Status</th>
            <th>Created At</th>
        </tr>
    </thead>
    <tbody>
        @foreach($withdrawals as $w)
            <tr>
                <td>{{ $w->id }}</td>
                <td>{{ $w->user->name ?? '-' }}</td>
                <td>{{ $w->user->email ?? '-' }}</td>
                <td>{{ number_format($w->amount, 2) }}</td>
                <td>{{ number_format($w->fee, 2) }}</td>
                <td>{{ $w->trc20_address }}</td>
                <td>{{ $w->txid }}</td>
                <td>{{ $w->status }}</td>
                <td>{{ $w->created_at }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
