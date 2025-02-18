<x-base-layout :scrollspy="false">

    <x-slot:pageTitle>
        Users List
    </x-slot:pageTitle>

    <!-- BEGIN GLOBAL MANDATORY STYLES -->
    <x-slot:headerFiles>
        <!--  BEGIN CUSTOM STYLE FILE  -->
        @vite(['resources/scss/light/assets/components/modal.scss'])
        @vite(['resources/scss/light/assets/apps/contacts.scss'])
        @vite(['resources/scss/dark/assets/components/modal.scss'])
        @vite(['resources/scss/dark/assets/apps/contacts.scss'])
        <!--  END CUSTOM STYLE FILE  -->
        <style>
            /* Custom styles for our list items */
            .user-email p.info-title,
            .user-location p.info-title,
            .user-phone p.info-title {
                font-weight: bold;
                margin-bottom: 5px;
            }
            .user-meta-info p.user-name {
                font-size: 16px;
                font-weight: bold;
            }
            .user-meta-info p.user-work {
                font-size: 14px;
                color: #6c757d;
            }
            /* Optionally adjust the SVG icon size */
            .action-btn svg {
                cursor: pointer;
                width: 24px;
                height: 24px;
            }
            /* Header section styles (for alignment and equal gaps) */
            .items-header-section .item-content {
                display: flex;
                align-items: center;
                padding: 10px 15px;
                border-bottom: 1px solid #e0e0e0;
                background: #f8f9fa; /* optional light background for header */
            }
            /* Ensure each header cell takes up equal space */
            .items-header-section .item-content > div,
            .items-header-section .item-content > .d-inline-flex {
                flex: 1;
                text-align: center;
                margin: 0;
            }
            /* Additional header styling for checkbox column */
            .items-header-section .n-chk {
                margin-right: 15px;
            }
            .items-header-section h4 {
                font-size: 14px;
                font-weight: bold;
                margin: 0;
            }
            /* Activity column styling */
            .user-activity {
                flex: 1;
                text-align: left;
                margin: 0;
            }
        </style>
    </x-slot:headerFiles>
    <!-- END GLOBAL MANDATORY STYLES -->

    <div class="row layout-spacing layout-top-spacing" id="cancel-row">
        <div class="col-lg-12">
            <div class="widget-content searchable-container list">

                <!-- Top Title -->
                <div class="row">
                    <div class="col-12">
                        <h2 class="mb-4">Users List</h2>
                    </div>
                </div>

                <!-- Search & Action Buttons -->
                <div class="row">
                    <div class="col-xl-4 col-lg-5 col-md-5 col-sm-7 filtered-list-search layout-spacing align-self-center">
                        <form class="form-inline my-2 my-lg-0">
                            <div class="input-group">
                                <!-- Search Icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round" class="feather feather-search me-2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <input type="text" class="form-control product-search" id="input-search"
                                       placeholder="Search Users...">
                            </div>
                        </form>
                    </div>

                    <!--<div class="col-xl-8 col-lg-7 col-md-7 col-sm-5 text-sm-right text-center layout-spacing align-self-center">
                        <div class="d-flex justify-content-sm-end justify-content-center">
                            <svg id="btn-add-user" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round" class="feather feather-user-plus me-2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <line x1="20" y1="8" x2="20" y2="14"></line>
                                <line x1="23" y1="11" x2="17" y2="11"></line>
                            </svg>
                        </div>
                    </div>-->
                </div>
                <!-- End Search & Action Buttons -->

                <!-- Header Section (matches your contacts header sample with added Activity column) -->
                <div class="searchable-items list">
                    <div class="items items-header-section">
                        <div class="item-content">
                            <div class="d-inline-flex">
                                <div class="n-chk align-self-center text-center">
                                    <div class="form-check form-check-primary">
                                        <input class="form-check-input inbox-chkbox" id="user-check-all" type="checkbox">
                                    </div>
                                </div>
                                <h4>Name</h4>
                            </div>
                            <div class="user-email">
                                <h4>Email</h4>
                            </div>
                            <div class="user-location">
                                <h4>Upline</h4>
                            </div>
                            <div class="user-phone">
                                <h4>Status</h4>
                            </div>
                            <div class="user-activity">
                                <h4>Activity</h4>
                            </div>
                            <div class="action-btn">
                                <h4>Actions</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Users List -->
                    @foreach ($users as $user)
                    <div class="items">
                        <div class="item-content">

                            <!-- User Profile Section -->
                            <div class="user-profile d-flex align-items-center">
                                <div class="n-chk align-self-center text-center me-2">
                                    <div class="form-check form-check-primary">
                                        <input class="form-check-input inbox-chkbox" type="checkbox">
                                    </div>
                                </div>

                                @php
                                    // Define an array of dummy avatar URLs.
                                    $dummyAvatars = [
                                        'https://designreset.com/cork/laravel/build/assets/profile-5.61b8f5d5.jpeg',
                                        'https://designreset.com/cork/laravel/build/assets/profile-11.5872df84.jpeg',
                                        'https://designreset.com/cork/laravel/build/assets/profile-12.cd334ada.jpeg',
                                        'https://designreset.com/cork/laravel/build/assets/profile-3.a0d4af19.jpeg',
                                        'https://designreset.com/cork/laravel/build/assets/profile-15.875e870e.jpeg',
                                    ];
                                    // Pick one randomly.
                                    $randomAvatar = $dummyAvatars[array_rand($dummyAvatars)];
                                @endphp
                                
                                @if(isset($user->avatar) && $user->avatar)
                                    <img src="{{ asset($user->avatar) }}" alt="avatar" class="rounded-circle me-2" width="50" height="50">
                                @else
                                    <!-- If no avatar exists, show the random dummy avatar.
                                         "Leave dark for the rest" can be interpreted as keeping the dark theme elsewhere;
                                         here, only the dummy avatar is used when available. -->
                                    <img src="{{ $randomAvatar }}" alt="avatar" class="rounded-circle me-2" width="50" height="50">
                                @endif


                                <div class="user-meta-info">
                                    <p class="user-name" data-name="{{ $user->name }}">{{ $user->name }}</p>
                                    <p class="user-work" data-occupation="{{ $user->role }}">{{ $user->role }}</p>
                                </div>
                            </div>
                            <!-- End User Profile Section -->

                            <!-- Email -->
                            <div class="user-email mt-2">
                                <p class="info-title">Email:</p>
                                <p class="usr-email-addr" data-email="{{ $user->email }}">{{ $user->email }}</p>
                            </div>

                            <!-- Upline / Referral -->
                            <div class="user-location mt-2">
                                <p class="info-title">Upline:</p>
                                <p class="usr-location" data-location="{{ $user->upline->name ?? 'N/A' }}">
                                    {{ $user->upline->name ?? 'N/A' }}
                                </p>
                            </div>

                            <!-- Status -->
                            <div class="user-phone mt-2">
                                <p class="info-title">Status:</p>
                                <p class="usr-ph-no">
                                    <span class="badge {{ $user->status ? 'badge-light-success' : 'badge-light-danger' }}">
                                        {{ $user->status ? 'Active' : 'Disabled' }}
                                    </span>
                                </p>
                            </div>

                            <!-- Last Login and Created At -->
                            <div class="mt-2">
                                <small>Last Login: {{ $user->last_login ? $user->last_login->format('d M Y, h:i A') : 'Never' }}</small>
                                <br>
                                <small>Created At: {{ $user->created_at->format('d M Y') }}</small>
                            </div>

                            <!-- Actions (Edit Button as SVG Icon from Template) -->
                            <div class="action-btn mt-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round" class="feather feather-edit-2 edit"
                                     data-bs-toggle="modal" data-bs-target="#editUserModal{{ $user->id }}">
                                    <path d="M17 3a2.828 2.828 0 0 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Edit User Modal -->
                    <div class="modal fade" id="editUserModal{{ $user->id }}" tabindex="-1"
                         aria-labelledby="editUserModalLabel{{ $user->id }}" aria-hidden="true">
                        <div class="modal-dialog">
                            <form action="{{ route('users.update', $user->id) }}" method="POST">
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
                                                   value="{{ $user->email }}" disabled>
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
                                        <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- End Edit User Modal -->

                    @endforeach
                </div>
                <!-- End Users List -->

            </div>
        </div>
    </div>

    <!-- BEGIN CUSTOM SCRIPTS FILE -->
    <x-slot:footerFiles>
        @vite(['resources/assets/js/custom.js'])
        <script src="{{ asset('plugins/global/vendors.min.js') }}"></script>
        <script src="{{ asset('plugins/jquery-ui/jquery-ui.min.js') }}"></script>
        @vite(['resources/assets/js/apps/contact.js'])
    </x-slot:footerFiles>
    <!-- END CUSTOM SCRIPTS FILE -->

</x-base-layout>
