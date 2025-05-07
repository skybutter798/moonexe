<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>
        Announcement List
    </x-slot>

    <x-slot:headerFiles>
        @vite(['resources/scss/light/assets/components/modal.scss'])
        <style>
          /* switch styling: unchecked = danger, checked = success */
          .form-switch .form-check-input { background-color: var(--bs-danger); border-color: var(--bs-danger); }
          .form-switch .form-check-input:focus { box-shadow: 0 0 0 .25rem rgba(var(--bs-danger-rgb), .25); }
          .form-switch .form-check-input:checked { background-color: var(--bs-success); border-color: var(--bs-success); }
          .form-switch .form-check-input:checked:focus { box-shadow: 0 0 0 .25rem rgba(var(--bs-success-rgb), .25); }
        </style>
    </x-slot>

    <div class="row layout-top-spacing">
        <div class="col-12 layout-spacing">
          <div class="widget p-4">

            {{-- Title + “New” button --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h2 class="mb-0">Announcements</h2>
              <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#annoucementModal">
                + New
              </button>
            </div>

            {{-- Flash messages --}}
            @if(session('success'))
              <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="table-responsive">
              <table class="table table-bordered">
                <thead class="bg-dark text-white">
                  <tr>
                    <th>ID</th>
                    <th>Banner</th>
                    <th>Name</th>
                    <th>Content</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th class="text-center">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($annoucements as $item)
                    <tr>
                      <td>{{ $item->id }}</td>
                      <td> @if($item->banner_image) <img src="{{ asset('storage/' . $item->banner_image) }}" alt="Banner" style="width: 100px;"> @endif </td>
                      <td>{{ $item->name }}</td>
                      <td>{{ Str::limit($item->content, 50) }}</td>
                      <td class="text-center">
                        <div class="form-check form-switch">
                          <input class="form-check-input" type="checkbox" role="switch"
                                 disabled {{ $item->status ? 'checked' : '' }}>
                        </div>
                      </td>
                      <td>{{ $item->created_at->format('d M Y H:i') }}</td>
                      <td>{{ $item->updated_at->format('d M Y H:i') }}</td>
                        <td class="text-center">
                          <button 
                            class="btn btn-sm btn-outline-primary" 
                            data-bs-toggle="modal" 
                            data-bs-target="#editAnnouncementModal"
                            data-id="{{ $item->id }}"
                            data-name="{{ $item->name }}"
                            data-content="{{ htmlspecialchars($item->content, ENT_QUOTES) }}"
                            data-status="{{ $item->status ? 1 : 0 }}"
                          >
                            Edit
                          </button>
                        </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="7" class="text-center">No announcements found.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>

          </div>
        </div>
    </div>

    {{-- Modal: Create Announcement --}}
    <div class="modal fade" id="annoucementModal" tabindex="-1" aria-labelledby="annoucementModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form action="{{ route('admin.annoucement.store') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="annoucementModalLabel">New Announcement</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label for="ann-name" class="form-label">Name</label>
                <input type="text" name="name" id="ann-name" class="form-control" required>
              </div>

              <div class="mb-3">
                <label for="ann-content" class="form-label">Content</label>
                <textarea name="content" id="ann-content" class="form-control" rows="4" required></textarea>
              </div>

              <label class="form-label d-block">Status</label>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="status" id="status-active" value="1" checked>
                <label class="form-check-label" for="status-active">Active</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="status" id="status-inactive" value="0">
                <label class="form-check-label" for="status-inactive">Inactive</label>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Announcement</button>
            </div>
          </div>
        </form>
      </div>
    </div>
    
    {{-- Edit Announcement Modal --}}
    <div class="modal fade" id="editAnnouncementModal" tabindex="-1" aria-labelledby="editAnnouncementModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form id="editAnnForm" method="POST" action="" enctype="multipart/form-data">
          @csrf 
          @method('PATCH')
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="editAnnouncementModalLabel">Edit Announcement</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label for="banner_image" class="form-label">Banner Image</label>
                <input type="file" class="form-control" name="banner_image" accept="image/*">
              </div>

              <div class="mb-3">
                <label for="edit-ann-name" class="form-label">Name</label>
                <input type="text" name="name" id="edit-ann-name" class="form-control" required>
              </div>
              <div class="mb-3">
                <label for="edit-ann-content" class="form-label">Content</label>
                <textarea name="content" id="edit-ann-content" class="form-control" rows="4" required></textarea>
              </div>
              <label class="form-label d-block">Status</label>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="status" id="edit-status-active" value="1">
                <label class="form-check-label" for="edit-status-active">Active</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="status" id="edit-status-inactive" value="0">
                <label class="form-check-label" for="edit-status-inactive">Inactive</label>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <x-slot:footerFiles>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
          const editModal = document.getElementById('editAnnouncementModal');
          editModal.addEventListener('show.bs.modal', function (event) {
            const btn   = event.relatedTarget;
            const id    = btn.getAttribute('data-id');
            const name  = btn.getAttribute('data-name');
            const content = btn.getAttribute('data-content');
            const status  = btn.getAttribute('data-status');
        
            // set form action URL
            const form = document.getElementById('editAnnForm');
            form.action = `/admin/annoucement/${id}`; // or use a data-route if you need locale/prefix flexibility
        
            // populate fields
            document.getElementById('edit-ann-name').value    = name;
            document.getElementById('edit-ann-content').value = content;
            document.getElementById('edit-status-active').checked   = (status === '1');
            document.getElementById('edit-status-inactive').checked = (status === '0');
          });
        });
        </script>

    </x-slot>
</x-base-layout>
