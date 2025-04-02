<x-base-layout :scrollspy="false">
  <x-slot:pageTitle>
    Profile
  </x-slot:pageTitle>

  <x-slot:headerFiles>
    <style>
        .form-label {
            margin-bottom:0px;
        }
        .form-control:disabled:not(.flatpickr-input), .form-control[readonly]:not(.flatpickr-input) {
            color:black;
        }
    </style>
  </x-slot:headerFiles>

  <div class="container py-4">
    <!-- Account Header -->
    <div class="card mb-4">
      <div class="card-body d-flex align-items-center">
        <div class="me-3">
          <!-- Avatar placeholder -->
          <div class="rounded" style="width:80px; height:80px; background-color:#101012;"></div>
        </div>
        <div>
          @if($user->packageModel)
            <span class="badge bg-primary text-white">{{ $user->packageModel->name }}</span>
          @else
            <span class="badge bg-secondary">None</span>
          @endif
          <h3 class="mb-0">{{ $user->name }}</h3>
          <p class="mb-0">Email: {{ $user->email }}<!-- • <span class="text-success">Verified</span>--></p>
        </div>
      </div>
      <div class="d-block d-md-nonep-3 bg-light text-center">
            <form action="{{ route('logout') }}" method="POST">
              @csrf
              <button type="submit" class="btn btn-danger btn-block w-100">Logout</button>
            </form>
          </div>
    </div>

    <!-- Personal Information (no card) -->
    <div class="mb-4">
      <h4 class="text-primary fw-bold">Personal Information</h4>
      <div class="mb-3">
        <label class="form-label">Status</label>
        <div>
          @if($user->status == 1)
            <span class="badge bg-success w-100 text-center">Active</span>
          @else
            <span class="badge bg-warning w-100 text-center">Holding</span>
          @endif
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Name</label>
        <input type="text" class="form-control form-control-sm" value="{{ $user->name }}" disabled>
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" class="form-control form-control-sm" value="{{ $user->email }}" disabled>
      </div>
      <div class="mb-3">
        <label class="form-label">Last Login</label>
        <input type="text" class="form-control form-control-sm" value="{{ $user->last_login ?? 'Never' }}" disabled>
      </div>
      <div class="mb-3">
        <label class="form-label">Account Created</label>
        <input type="text" class="form-control form-control-sm" value="{{ $user->created_at }}" disabled>
      </div>
      <div class="mb-3">
        <label class="form-label">Last Updated</label>
        <input type="text" class="form-control form-control-sm" value="{{ $user->updated_at }}" disabled>
      </div>
    </div>

    <!-- Change Password Section -->
    <h4 class="text-primary fw-bold mb-3">Change Password</h4>
    <div class="mb-4">
      <div class="card">
        <div class="card-body">
          <form action="{{ route('user.changePassword') }}" method="POST" id="changePasswordForm">
            @csrf
            <div class="mb-3">
              <label for="current_password" class="form-label">Current Password</label>
              <input type="password" class="form-control form-control-sm" id="current_password" name="current_password" required>
            </div>
            <div class="mb-3">
              <label for="new_password" class="form-label">New Password</label>
              <input type="password" class="form-control form-control-sm" id="new_password" name="new_password" required>
            </div>
            <div class="">
              <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
              <input type="password" class="form-control form-control-sm" id="new_password_confirmation" name="new_password_confirmation" required>
            </div>
          </form>
        </div>
        <div class="mb-3 text-center">
            <button type="submit" form="changePasswordForm" class="btn btn-primary">Change Password</button>
          </div>
      </div>
    </div>

    <!-- Subscribe to Newsletter Section -->
    <h4 class="text-primary fw-bold mb-3">Subscribe to our Newsletter</h4>
    <div class="mb-4">
      <div class="card">
        <div class="card-body">
          <form action="" method="POST" id="subscribeForm">
            @csrf
            <div class="">
              <label for="newsletterEmail" class="form-label">Email address</label>
              <input type="email" class="form-control form-control-sm" id="newsletterEmail" name="email" value="{{ $user->email }}" required>
            </div>
          </form>
        </div>
        <div class="mb-3 text-center">
            <button type="submit" form="subscribeForm" class="btn btn-primary">Subscribe</button>
          </div>
      </div>
      
    </div>
  </div>

  <x-slot:footerFiles>
    <!-- Include any additional footer scripts if needed -->
  </x-slot:footerFiles>
</x-base-layout>
