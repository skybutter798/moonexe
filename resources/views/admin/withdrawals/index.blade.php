<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>
        Withdrawal Requests
    </x-slot>

    <x-slot:headerFiles>
        @vite(['resources/scss/light/assets/components/modal.scss'])
    </x-slot>

    <div class="row layout-top-spacing">
        <div class="col-12 layout-spacing">
            <div class="widget p-4">
                <div class="widget-header d-flex align-items-center">
                    <h2 class="mb-2">Withdrawal Requests</h2>
                </div>
                <div class="widget-content">
                    <!-- Display flash messages -->
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    
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
                      <div class="col-auto">
                        <input type="date" name="date" class="form-control form-control-sm"
                               value="{{ request('date') }}">
                      </div>
                      <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                        <a href="{{ route('admin.withdrawals.index') }}"
                           class="btn btn-secondary btn-sm ms-1">Reset</a>
                      </div>
                    </form>

                    <div class="table-responsive">
                      <table class="table table-bordered">
                        <thead class="bg-dark text-white">
                          <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>TXID</th>
                            <th>Amount</th>
                            <th>Fee</th>
                            <th>TRC20 Address</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th class="text-center">Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          @forelse($withdrawals as $w)
                            <tr>
                              <td>{{ $w->id }}</td>
                              <td>{{ $w->user->name }}</td>
                              <td>{{ $w->txid }}</td>
                              <td>{{ number_format($w->amount, 2) }}</td>
                              <td>{{ number_format($w->fee, 2) }}</td>
                              <td>{{ $w->trc20_address }}</td>
                              <td>
                                <span class="badge
                                  {{ $w->status=='Completed' ? 'badge-light-success'
                                     : ($w->status=='Rejected' ? 'badge-light-danger'
                                       : 'badge-light-warning') }}">
                                  {{ $w->status }}
                                </span>
                              </td>
                              <td>{{ $w->created_at->format('d M Y H:i') }}</td>
                              <td class="text-center">
                                @if($w->status === 'Pending')
                                  <form action="{{ route('admin.withdrawals.approve', $w->id) }}"
                                        method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-success btn-sm">Approve</button>
                                  </form>
                                  <form action="{{ route('admin.withdrawals.reject', $w->id) }}"
                                        method="POST" class="d-inline">
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
                              <td colspan="8" class="text-center">No withdrawals found.</td>
                            </tr>
                          @endforelse
                        </tbody>
                      </table>
                    </div>
            
                    {{-- Pagination --}}
                    <div class="mt-3">
                      {{ $withdrawals->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot:footerFiles>
        {{-- Add any specific scripts if required --}}
    </x-slot>
</x-base-layout>
