<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>User ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>User TRX Address</th>
            <th>TXID</th>
            <th>Amount</th>
            <th>Deposit TRC20 Addr</th>
            <th>Status</th>
            <th>Created At</th>
        </tr>
    </thead>
    <tbody>
        @foreach($deposits as $deposit)
            <tr>
                <td>{{ $deposit->id }}</td>
                <td>{{ $deposit->user_id }}</td>
                <td>{{ $deposit->user->name ?? '-' }}</td>
                <td>{{ $deposit->user->email ?? '-' }}</td>
                <td>{{ $deposit->user->trx_address ?? '-' }}</td>
                <td>{{ $deposit->txid }}</td>
                <td>{{ number_format($deposit->amount, 2) }}</td>
                <td>{{ $deposit->trc20_address }}</td>
                <td>{{ $deposit->status }}</td>
                <td>{{ $deposit->created_at }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
