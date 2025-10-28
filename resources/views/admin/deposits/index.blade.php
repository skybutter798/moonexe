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
                    <h2 class="mb-2">Deposit Requests</h2>
                </div>
                <div class="widget-content">
                    <!-- Display flash messages -->
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    {{-- Search form --}}
                    <form method="GET" class="mb-3 row gx-1 gy-1 align-items-end">
                      <div class="col-auto">
                        <input type="text" name="username" class="form-control form-control-sm"
                               placeholder="Username" value="{{ request('username') }}">
                      </div>
                      <div class="col-auto">
                        <input type="text" name="txid" class="form-control form-control-sm"
                               placeholder="TXID" value="{{ request('txid') }}">
                      </div>
                      <div class="col-auto">
                        <input type="text" name="trc20_address" class="form-control form-control-sm"
                               placeholder="TRC20 Addr" value="{{ request('trc20_address') }}">
                      </div>
                      <div class="col-auto">
                        <input type="number" step="0.01" name="amount" class="form-control form-control-sm"
                               placeholder="Amount" value="{{ request('amount') }}">
                      </div>
                      <div class="col-auto">
                        <select name="status" class="form-control form-control-sm">
                          <option value="">All Status</option>
                          <option value="Pending" {{ request('status')=='Pending'?'selected':'' }}>Pending</option>
                          <option value="Completed" {{ request('status')=='Completed'?'selected':'' }}>Completed</option>
                          <option value="Rejected" {{ request('status')=='Rejected'?'selected':'' }}>Rejected</option>
                        </select>
                      </div>
                    
                      {{-- ✅ Date Range Filter --}}
                      <div class="col-auto">
                        <label class="form-label mb-0 small">From</label>
                        <input type="date" name="start_date" class="form-control form-control-sm"
                               value="{{ request('start_date') }}">
                      </div>
                      <div class="col-auto">
                        <label class="form-label mb-0 small">To</label>
                        <input type="date" name="end_date" class="form-control form-control-sm"
                               value="{{ request('end_date') }}">
                      </div>
                    
                      <div class="col-auto">
                          <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                          <a href="{{ route('admin.deposits.index') }}" class="btn btn-secondary btn-sm ms-1">Reset</a>
                          <a href="{{ route('admin.deposits.export', request()->query()) }}" class="btn btn-success btn-sm ms-1">
                              Export Excel
                          </a>
                        </div>

                    </form>

                    {{-- End search form --}}
                    
                    @if($deposits->count())
                      <div class="mt-3 text-end">
                          <span class="badge bg-primary fs-6">
                            Total Deposit: {{ number_format($totalAmount, 2) }}
                          </span>
                        </div>
                    @endif
        
                    <div class="table-responsive">
                      <table class="table table-bordered">
                        <thead class="bg-dark text-white">
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
                            <th class="text-center">Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          @forelse ($deposits as $deposit)
                            <tr>
                              <td>{{ $deposit->id }}</td>
                              <td>{{ $deposit->user_id }}</td>
                              <td>{{ $deposit->user->name ?? '-' }}</td>
                              <td>{{ $deposit->user->email ?? '-' }}</td>
                              <td>{{ $deposit->user->trx_address ?? '-' }}</td>
                              <td>{{ $deposit->txid }}</td>
                              <td>{{ number_format($deposit->amount, 2) }}</td>
                              <td>{{ $deposit->trc20_address }}</td>
                              <td>
                                <span class="badge 
                                  {{ $deposit->status=='Completed' ? 'badge-success' 
                                    : ($deposit->status=='Rejected' ? 'badge-danger' 
                                      : 'badge-dark') }}">
                                  {{ $deposit->status }}
                                </span>
                              </td>
                              <td>{{ $deposit->created_at->format('d M Y') }}</td>
                              <td class="text-center">
                                @if($deposit->status === 'Pending')
                                  <form action="{{ route('admin.deposits.approve', $deposit->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-success btn-sm">Approve</button>
                                  </form>
                                  <form action="{{ route('admin.deposits.reject', $deposit->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-danger btn-sm">Reject</button>
                                  </form>
                                @else
                                  <button class="btn btn-dark btn-sm" disabled>Done</button>
                                @endif
                              </td>
                            </tr>
                          @empty
                            <tr>
                              <td colspan="11" class="text-center">No deposits found.</td>
                            </tr>
                          @endforelse
                        </tbody>
                      </table>
                    </div>

        
                    {{-- Pagination links --}}
                    <div class="mt-3">
                      {{ $deposits->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot:footerFiles>
        {{-- Add any specific scripts if required --}}
    </x-slot>
</x-base-layout>
