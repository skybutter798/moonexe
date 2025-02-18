<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>
        Deposit Requests
    </x-slot>

    <x-slot:headerFiles>
        @vite(['resources/scss/light/assets/components/modal.scss'])
    </x-slot>

    <div class="row layout-top-spacing">
        <div class="col-12 layout-spacing">
            <div class="widget p-4">
                <div class="widget-header d-flex align-items-center">
                    <h4>Deposit Requests</h4>
                </div>
                <div class="widget-content">
                    <!-- Display flash messages -->
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>TXID</th>
                                    <th>Amount</th>
                                    <th>TRC20 Address</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($deposits as $deposit)
                                    <tr>
                                        <td>{{ $deposit->user_id }}</td>
                                        <td>{{ $deposit->txid }}</td>
                                        <td>{{ number_format($deposit->amount, 2) }}</td>
                                        <td>{{ $deposit->trc20_address }}</td>
                                        <td>
                                            <span class="badge 
                                                @if($deposit->status == 'Completed')
                                                    badge-light-success
                                                @elseif($deposit->status == 'Rejected')
                                                    badge-light-danger
                                                @else
                                                    badge-light-warning
                                                @endif">
                                                {{ $deposit->status }}
                                            </span>
                                        </td>
                                        <td>{{ $deposit->created_at->format('d M Y') }}</td>
                                        <td class="text-center">
                                            @if($deposit->status == 'Pending')
                                                <form action="{{ route('admin.deposits.approve', $deposit->id) }}" method="POST" style="display:inline-block;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                                </form>
                                                <form action="{{ route('admin.deposits.reject', $deposit->id) }}" method="POST" style="display:inline-block;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                                </form>
                                            @else
                                                <span>N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot:footerFiles>
        {{-- Add any specific scripts if required --}}
    </x-slot>
</x-base-layout>
