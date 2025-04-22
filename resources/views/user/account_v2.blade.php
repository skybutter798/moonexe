<x-base-layout :scrollspy="false">
  <x-slot:pageTitle>
    Profile
  </x-slot:pageTitle>

  <x-slot:headerFiles>
    <style>
      .form-label {
        margin-bottom:0px;
      }
      .form-control:disabled:not(.flatpickr-input), 
      .form-control[readonly]:not(.flatpickr-input) {
        color:black;
      }
      /* Makes the avatar container clickable */
      .avatar-wrapper {
        cursor: pointer;
        position: relative;
        display: inline-block;
      }
      .avatar-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 80px;
        height: 80px;
        background: rgba(0, 0, 0, 0.5);
        color: #fff;
        display: flex;
        justify-content: center;
        align-items: center;
        opacity: 0;
        transition: opacity 0.2s;
        border-radius: 50%;
      }
      .avatar-wrapper:hover .avatar-overlay {
        opacity: 1;
      }
    </style>
  </x-slot:headerFiles>

  <div class="container py-4">
    <!-- Account Header -->
    <div class="card mb-4">
      <div class="card-body d-flex align-items-center">
        <div class="me-3">
          <!-- Wrap avatar container in a label for file input trigger -->
          <label for="avatarInput" class="avatar-wrapper">
            <div id="avatarContainer">
              @if($user->avatar && Storage::disk('public')->exists($user->avatar))
                <img id="avatarPreview" src="{{ asset('storage/' . $user->avatar) }}" 
                     alt="Profile Picture" 
                     class="rounded" 
                     style="width:80px; height:80px; object-fit:cover;">
              @else
                <div id="avatarPreview" class="rounded" 
                     style="width:80px; height:80px; background-color:#101012;"></div>
              @endif
            </div>
            <!-- Overlay that appears on hover -->
            <div class="avatar-overlay">
              Change
            </div>
          </label>
        </div>
        <div>
          @if($user->packageModel)
            <span class="badge bg-primary text-white">{{ $user->packageModel->name }}</span>
          @else
            <span class="badge bg-secondary">None</span>
          @endif
          <h3 class="mb-0">{{ $user->name }}</h3>
          <p class="mb-0">Email: {{ $user->email }}</p>
        </div>
      </div>
      <div class="d-block d-md-nonep-3 bg-light text-center">
        <form action="{{ route('logout') }}" method="POST">
          @csrf
          <button type="submit" class="btn btn-danger btn-block w-100">Logout</button>
        </form>
      </div>
    </div>

    <!-- Update Profile Section -->
    <div class="mb-4">
      <h4 class="text-primary fw-bold">Edit Profile</h4>
      <div class="card">
        <div class="card-body">
          <!-- The form includes the hidden file input for avatar upload -->
          <form action="{{ route('user.updateProfile') }}" method="POST" 
                enctype="multipart/form-data" id="updateProfileForm">
            @csrf
            @method('PUT')
            <!-- Editable Name -->
            <div class="mb-3">
              <label class="form-label">Name</label>
              <input type="text" class="form-control form-control-sm" 
                     value="{{ $user->name }}" disabled>
              <!-- Hidden input to send the value -->
              <input type="hidden" name="name" value="{{ $user->name }}">
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control form-control-sm" 
                     value="{{ $user->email }}" disabled>
              <!-- Hidden input to send the value -->
              <input type="hidden" name="email" value="{{ $user->email }}">
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
            <!-- Hidden Avatar File Upload Field -->
            <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display: none;">
            <div class="text-center"><button type="submit" class="btn btn-primary">Update Profile</button></div>
          </form>
        </div>
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
              <input type="password" class="form-control form-control-sm" 
                     id="current_password" name="current_password" required>
            </div>
            <div class="mb-3">
              <label for="new_password" class="form-label">New Password</label>
              <input type="password" class="form-control form-control-sm" 
                     id="new_password" name="new_password" required>
            </div>
            <div>
              <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
              <input type="password" class="form-control form-control-sm" 
                     id="new_password_confirmation" name="new_password_confirmation" required>
            </div>
          </form>
        </div>
        <div class="mb-3 text-center">
          <button type="submit" form="changePasswordForm" class="btn btn-primary">Change Password</button>
        </div>
      </div>
    </div>
    <button class="btn btn-success btn-block w-100 mb-4" type="button" disabled>KYC</button>

    <!-- Subscribe to Newsletter Section 
    <h4 class="text-primary fw-bold mb-3">Subscribe to our Newsletter</h4>
    <div class="mb-4">
      <div class="card">
        <div class="card-body">
          <form action="" method="POST" id="subscribeForm">
            @csrf
            <div class="">
              <label for="newsletterEmail" class="form-label">Email address</label>
              <input type="email" class="form-control form-control-sm" 
                     id="newsletterEmail" name="email" value="{{ $user->email }}" required>
            </div>
          </form>
        </div>
        <div class="mb-3 text-center">
          <button type="submit" form="subscribeForm" class="btn btn-primary">Subscribe</button>
        </div>
      </div>
    </div>-->
  </div>

  <x-slot:footerFiles>
    <!-- JavaScript to update the avatar preview -->
    <script>
      const avatarInput = document.getElementById('avatarInput');
      avatarInput.addEventListener('change', function(event) {
        const file = event.target.files[0];
    
        if (file) {
          // Client-side size check (2MB = 2 * 1024 * 1024)
          if (file.size > 2 * 1024 * 1024) {
            alert("Avatar file size must be 2MB or less.");
            avatarInput.value = ''; // Clear the input
            return;
          }
    
          const reader = new FileReader();
          reader.onload = function(e) {
            let preview = document.getElementById('avatarPreview');
    
            if (preview.tagName.toLowerCase() === 'div') {
              const img = document.createElement('img');
              img.id = 'avatarPreview';
              img.src = e.target.result;
              img.alt = "Profile Picture";
              img.className = "rounded";
              img.style.width = "80px";
              img.style.height = "80px";
              img.style.objectFit = "cover";
              preview.parentNode.replaceChild(img, preview);
            } else {
              preview.src = e.target.result;
            }
          };
          reader.readAsDataURL(file);
        }
      });
    </script>
  </x-slot:footerFiles>
</x-base-layout>