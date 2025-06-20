<x-base-layout :scrollspy="false">

    <x-slot:pageTitle>
        Users List
    </x-slot>

    <x-slot:headerFiles>
        @vite(['resources/scss/light/assets/components/modal.scss'])
    </x-slot>

    <div class="row layout-top-spacing">
        <div class="col-12 layout-spacing">
            <div class="widget p-4">

                {{-- Header --}}
                <div class="widget-header d-flex align-items-center mb-3">
                    <h2 class="mb-0">Users List</h2>
                </div>

                {{-- Flash Messages --}}
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                {{-- Filter Form --}}
                <form method="GET" class="mb-3 row gx-1 gy-1 align-items-end">
                    <div class="col-auto">
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Name" value="{{ request('name') }}">
                    </div>
                    <div class="col-auto">
                        <input type="text" name="email" class="form-control form-control-sm" placeholder="Email" value="{{ request('email') }}">
                    </div>
                    <div class="col-auto">
                        <input type="text" name="upline" class="form-control form-control-sm" placeholder="Upline" value="{{ request('upline') }}">
                    </div>
                    <div class="col-auto">
                        <input type="text" name="bonus" class="form-control form-control-sm" placeholder="Bonus" value="{{ request('bonus') }}">
                    </div>
                    <div class="col-auto">
                        <select name="status" class="form-control form-control-sm bg-dark text-white">
                            <option value="">All Status</option>
                            <option value="1" {{ request('status')==='1' ? 'selected':'' }}>Active</option>
                            <option value="0" {{ request('status')==='0' ? 'selected':'' }}>Disabled</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="package_id" class="form-control form-control-sm bg-dark text-white">
                            <option value="">All Packages</option>
                            @foreach($packages as $id => $name)
                                <option value="{{ $id }}" {{ request('package_id')==$id ? 'selected':'' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="role" class="form-control form-control-sm bg-dark text-white">
                            <option value="">All Roles</option>
                            @foreach($roles as $role)
                                <option value="{{ $role }}" {{ request('role')==$role ? 'selected':'' }}>{{ $role }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="type" class="form-control form-control-sm bg-dark text-white">
                            <option value="">All Types</option>
                            <option value="robot"  {{ request('type')=='robot'  ? 'selected':'' }}>Robot</option>
                            <option value="normal" {{ request('type')=='normal' ? 'selected':'' }}>Normal</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date') }}">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-dark btn-sm">Filter</button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-dark btn-sm ms-1">Reset</a>
                    </div>
                </form>

                {{-- Users Table --}}
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th>Avatar</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Upline</th>
                                <th>Package</th>
                                <th>Status</th>
                                <th>Bonus</th>
                                <th>Role</th>
                                <th>Type</th>
                                <th>Last Login</th>
                                <th>Created At</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td class="text-center">
                                        @if($user->avatar)
                                            <img src="{{ asset('storage/'.$user->avatar) }}" class="rounded-circle" width="40" height="40" alt="avatar">
                                        @else
                                            <svg width="40" height="40" viewBox="0 0 40 40">
                                                <circle cx="20" cy="20" r="20" fill="#000"/>
                                            </svg>
                                        @endif
                                    </td>
                                    <td> <a href="#" onclick="loadWalletBreakdown({{ $user->id }}, '{{ $user->name }}')" data-bs-toggle="modal" data-bs-target="#walletBreakdownModal"> {{ $user->name }} </a> </td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->upline->name ?? '-' }}</td>
                                    <td><span class="badge badge-dark">{{ optional($user->packageModel)->name ?? '-' }}</span></td>
                                    <td>@if($user->status != 0)<span class="badge badge-success">Active</span>@else<span class="badge badge-danger">Deactive</span>@endif</td>
                                    <td>{{ $user->bonus ?? '-' }}</td>
                                    <td><span class="badge badge-dark">{{ $user->role }}</span></td>
                                    <td><span class="badge badge-dark">@switch($user->status)@case(2) Robot @break @case(1) Normal @break @default None @endswitch</span></td>
                                    <td>{{ optional($user->last_login)->format('d M Y') ?? '-' }}</td>
                                    <td>{{ $user->created_at->format('d M Y') }}</td>
                                    <td class="text-center">
                                    @if(in_array($user->status, [1, 2]) && auth()->id() != $user->id && !$user->is_admin)
                                        <a href="{{ route('admin.users.impersonate', $user->id) }}" class="btn btn-sm btn-warning">Login</a>
                                    @endif

                                    </td>
                                </tr>
                                
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center">No users found.</td>
                                </tr>
                            @endforelse
                            
                        </tbody>
                    </table>
                    
                    <div class="modal fade" id="walletBreakdownModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content p-3">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="walletModalTitle">Wallet Breakdown</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="btn-group mb-3" role="group">
                                            <button type="button" class="btn btn-outline-dark" onclick="showBreakdown('usdt')">USDT Wallet</button>
                                            <button type="button" class="btn btn-outline-dark" onclick="showBreakdown('trading')">Trading Margin</button>
                                            <button type="button" class="btn btn-outline-dark" onclick="showBreakdown('earning')">Earning ROI</button>
                                            <button type="button" class="btn btn-outline-dark" onclick="showBreakdown('affiliate')">Affiliate</button>
                                            {{--<button type="button" class="btn btn-outline-dark" onclick="showBreakdown('topups')">Topups</button>--}}

                                        </div>
                                        <div id="breakdown-usdt" class="wallet-breakdown-table"></div>
                                        <div id="breakdown-trading" class="wallet-breakdown-table d-none"></div>
                                        <div id="breakdown-earning" class="wallet-breakdown-table d-none"></div>
                                        <div id="breakdown-affiliate" class="wallet-breakdown-table d-none"></div>
                                        <div id="breakdown-topups" class="wallet-breakdown-table d-none"></div>

                                    </div>
                                </div>
                            </div>
                        </div>
                </div>

                {{-- Pagination --}}
                <div class="mt-3">
                    {{ $users->appends(request()->query())->links('vendor.pagination.bootstrap-5') }}
                </div>

            </div>
        </div>
    </div>

    <x-slot:footerFiles>
        @vite(['resources/assets/js/custom.js'])
        <script src="{{ asset('plugins/global/vendors.min.js') }}"></script>
        <script src="{{ asset('plugins/jquery-ui/jquery-ui.min.js') }}"></script>
        @vite(['resources/assets/js/apps/contact.js'])
        <script>
            function loadWalletBreakdown(userId, userName) {
                document.getElementById('walletModalTitle').innerText = 'Wallet Breakdown for ' + userName + ' (ID: ' + userId + ')';
                
                // Show loading
                ['usdt', 'trading', 'earning', 'affiliate'].forEach(type => {
                    document.getElementById('breakdown-' + type).innerHTML = '<div>Loading...</div>';
                    document.getElementById('breakdown-' + type).classList.add('d-none');
                });
                document.getElementById('breakdown-usdt').classList.remove('d-none');
            
                fetch('/admin/users/' + userId + '/wallet-breakdown')
                    .then(res => res.json())
                    .then(data => {
                        ['usdt', 'trading', 'earning', 'affiliate', 'topups'].forEach(type => {
                            document.getElementById('breakdown-' + type).innerHTML = data[type];
                        });
                    });

            }
            
            function showBreakdown(type) {
                ['usdt', 'trading', 'earning', 'affiliate', 'topups'].forEach(t => {
                    document.getElementById('breakdown-' + t).classList.add('d-none');
                });
                document.getElementById('breakdown-' + type).classList.remove('d-none');
            }

        </script>
    </x-slot:footerFiles>
</x-base-layout>