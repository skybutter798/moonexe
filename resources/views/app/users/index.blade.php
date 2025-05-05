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
                {{-- Name --}}
                <div class="col-auto">
                    <input type="text" name="name" class="form-control form-control-sm"
                           placeholder="Name" value="{{ request('name') }}">
                </div>
                {{-- Email --}}
                <div class="col-auto">
                    <input type="text" name="email" class="form-control form-control-sm"
                           placeholder="Email" value="{{ request('email') }}">
                </div>
                {{-- Upline --}}
                <div class="col-auto">
                    <input type="text" name="upline" class="form-control form-control-sm"
                           placeholder="Upline" value="{{ request('upline') }}">
                </div>
                {{-- Bonus --}}
                <div class="col-auto">
                    <input type="text" name="bonus" class="form-control form-control-sm"
                           placeholder="Bonus" value="{{ request('bonus') }}">
                </div>
                {{-- Status --}}
                <div class="col-auto">
                    <select name="status" class="form-control form-control-sm bg-dark text-white">
                        <option value="">All Status</option>
                        <option value="1" {{ request('status')==='1' ? 'selected':'' }}>Active</option>
                        <option value="0" {{ request('status')==='0' ? 'selected':'' }}>Disabled</option>
                    </select>
                </div>
                {{-- Package --}}
                <div class="col-auto">
                    <select name="package_id" class="form-control form-control-sm bg-dark text-white">
                        <option value="">All Packages</option>
                        @foreach($packages as $id => $name)
                            <option value="{{ $id }}" {{ request('package_id')==$id ? 'selected':'' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                {{-- Role --}}
                <div class="col-auto">
                    <select name="role" class="form-control form-control-sm bg-dark text-white">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role }}" {{ request('role')==$role ? 'selected':'' }}>
                                {{ $role }}
                            </option>
                        @endforeach
                    </select>
                </div>
                {{-- Type --}}
                <div class="col-auto">
                    <select name="type" class="form-control form-control-sm bg-dark text-white">
                        <option value="">All Types</option>
                        <option value="robot"  {{ request('type')=='robot'  ? 'selected':'' }}>Robot</option>
                        <option value="normal" {{ request('type')=='normal' ? 'selected':'' }}>Normal</option>
                    </select>
                </div>
                {{-- Created Date --}}
                <div class="col-auto">
                    <input type="date" name="date" class="form-control form-control-sm"
                           value="{{ request('date') }}">
                </div>
                {{-- Submit / Reset --}}
                <div class="col-auto">
                    <button type="submit" class="btn btn-dark btn-sm">Filter</button>
                    <a href="{{ route('admin.users.index') }}"
                       class="btn btn-dark btn-sm ms-1">Reset</a>
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
                                    {{-- Avatar --}}
                                    <td class="text-center">
                                        @if($user->avatar)
                                            <img src="{{ asset('storage/'.$user->avatar) }}"
                                                 class="rounded-circle" width="40" height="40" alt="avatar">
                                        @else
                                            <svg width="40" height="40" viewBox="0 0 40 40">
                                                <circle cx="20" cy="20" r="20" fill="#000"/>
                                            </svg>
                                        @endif
                                    </td>

                                    {{-- Basic info --}}
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->upline->name ?? '-' }}</td>
                                    <td><span class="badge badge-dark">{{ optional($user->packageModel)->name ?? '-' }}</span></td>

                                    {{-- Status --}}
                                    <td>
                                        @if($user->status != 0)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Deactive</span>
                                        @endif
                                    </td>
                                    
                                    <td>{{ $user->bonus ?? '-' }}</td>
                                    <td><span class="badge badge-dark">{{ $user->role }}</span></td>

                                    {{-- Type --}}
                                    <td><span class="badge badge-dark">
                                        @switch($user->status)
                                            @case(2) Robot @break
                                            @case(1) Normal @break
                                            @default  None
                                        @endswitch
                                        </span>
                                    </td>

                                    <td>{{ optional($user->last_login)->format('d M Y') ?? '-' }}</td>
                                    <td>{{ $user->created_at->format('d M Y') }}</td>

                                    {{-- Actions --}}
                                    <td class="text-center">
                                        {{-- Edit --}}
                                        <button class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editUserModal{{ $user->id }}">
                                            Edit
                                        </button>

                                        {{-- Enable / Disable --}}
                                        @if($user->status != 0)
                                            <form action="{{ route('admin.users.disable', $user->id) }}"
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-danger">Disable</button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.users.enable', $user->id) }}"
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-success">Enable</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>

                                <!-- Edit User Modal -->
                                <div class="modal fade" id="editUserModal{{ $user->id }}" tabindex="-1"
                                     aria-labelledby="editUserModalLabel{{ $user->id }}" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editUserModalLabel{{ $user->id }}">Edit User</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <!-- Name -->
                                                    <div class="form-group mb-3">
                                                        <label for="name-{{ $user->id }}">Name</label>
                                                        <input type="text" name="name" id="name-{{ $user->id }}" class="form-control"
                                                               value="{{ $user->name }}" required>
                                                    </div>
                                                    <!-- Email (disabled) -->
                                                    <div class="form-group mb-3">
                                                        <label for="email-{{ $user->id }}">Email</label>
                                                        <input type="email" name="email" id="email-{{ $user->id }}" class="form-control"
                                                               value="{{ $user->email }}" required>
                                                    </div>
                                                    <!-- Role (disabled) -->
                                                    <div class="form-group mb-3">
                                                        <label for="role-{{ $user->id }}">Role</label>
                                                        <input type="text" name="role" id="role-{{ $user->id }}" class="form-control"
                                                               value="{{ $user->role }}" disabled>
                                                    </div>
                                                    <!-- Referral Code -->
                                                    <div class="form-group mb-3">
                                                        <label for="referral_code-{{ $user->id }}">Referral Code</label>
                                                        <input type="text" name="referral_code" id="referral_code-{{ $user->id }}"
                                                               class="form-control" value="{{ $user->referral_code }}">
                                                    </div>
                                                    <!-- Status -->
                                                    <div class="form-group mb-3">
                                                        <label for="status-{{ $user->id }}">Status</label>
                                                        <select name="status" id="status-{{ $user->id }}" class="form-control">
                                                            <option value="1" {{ $user->status ? 'selected' : '' }}>Active</option>
                                                            <option value="0" {{ !$user->status ? 'selected' : '' }}>Disabled</option>
                                                        </select>
                                                    </div>
                                                    <!-- Upline / Referral -->
                                                    <div class="form-group mb-3">
                                                        <label for="upline-{{ $user->id }}">Upline</label>
                                                        <select name="referral" id="upline-{{ $user->id }}" class="form-control">
                                                            <option value="" {{ is_null($user->referral) ? 'selected' : '' }}>No Upline</option>
                                                            @foreach ($users as $upline)
                                                                @if ($upline->id !== $user->id)
                                                                    <option value="{{ $upline->id }}" {{ $user->referral == $upline->id ? 'selected' : '' }}>
                                                                        {{ $upline->name }}
                                                                    </option>
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <!-- Updated Wallet Details Section -->
                                                    <div class="row">
                                                        <div class="col-4">
                                                            <label for="cash_wallet" class="form-label">Cash Wallet</label>
                                                            <input type="text" id="cash_wallet" class="form-control" 
                                                                   value="{{ $user->wallet->cash_wallet ?? '0.00' }}" disabled>
                                                        </div>
                                                        <div class="col-4">
                                                            <label for="register_wallet" class="form-label">Trading Wallet</label>
                                                            <input type="text" id="register_wallet" class="form-control" 
                                                                   value="{{ $user->wallet->register_wallet ?? '0.00' }}" disabled>
                                                        </div>
                                                        <div class="col-4">
                                                            <label for="epoint_wallet" class="form-label">Earning Wallet</label>
                                                            <input type="text" id="epoint_wallet" class="form-control" 
                                                                   value="{{ $user->wallet->epoint_wallet ?? '0.00' }}" disabled>
                                                        </div>
                                                        <div class="col-4">
                                                            <label for="affiliates_wallet" class="form-label">Affiliates Wallet</label>
                                                            <input type="text" id="affiliates_wallet" class="form-control" 
                                                                   value="{{ $user->wallet->affiliates_wallet ?? '0.00' }}" disabled>
                                                        </div>
                                                        <div class="col-4">
                                                            <label for="staking_value" class="form-label">Staking Value</label>
                                                            <input type="text" id="staking_value" class="form-control" 
                                                                   value="{{ $user->wallet->staking_value ?? '0.00' }}" disabled>
                                                        </div>
                                                    </div>
            
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark"
                                                            data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-dark">Save Changes</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <!-- End Edit User Modal -->

                            @empty
                                <tr>
                                    <td colspan="12" class="text-center">No users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
    </x-slot:footerFiles>
</x-base-layout>
