<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>
        Currency List
    </x-slot>

    <x-slot:headerFiles>
        @vite(['resources/scss/light/assets/components/modal.scss'])
        <style>
        /* default (unchecked) = danger */
        .form-switch .form-check-input {
          background-color: var(--bs-danger);
          border-color: var(--bs-danger);
        }
        .form-switch .form-check-input:focus {
          box-shadow: 0 0 0 .25rem rgba(var(--bs-danger-rgb), .25);
        }
        /* checked = success */
        .form-switch .form-check-input:checked {
          background-color: var(--bs-success);
          border-color: var(--bs-success);
        }
        .form-switch .form-check-input:checked:focus {
          box-shadow: 0 0 0 .25rem rgba(var(--bs-success-rgb), .25);
        }
      </style>
    </x-slot>

    <div class="row layout-top-spacing">
        <div class="col-12 layout-spacing">
          <div class="widget p-4">
    
            {{-- Title --}}
            <h2 class="mb-2">Currency List</h2>
    
            {{-- Flash messages --}}
            @if(session('success'))
              <div class="alert alert-success">{{ session('success') }}</div>
            @endif
    
            <div class="table-responsive">
              <table class="table table-bordered">
                <thead class="bg-dark text-white">
                  <tr>
                    <th>ID</th>
                    <th>Currency Name</th>
                    <th>Timezone</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th class="text-center">Action</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($currencies as $currency)
                    <tr>
                      <td>{{ $currency->id }}</td>
                      <td>{{ $currency->c_name }}</td>
                      <td>{{ $currency->timezone }}</td>
                      <td>{{ $currency->status ? 'Active' : 'Disabled' }}</td>
                      <td>{{ $currency->created_at->format('d M Y') }}</td>
                      <td class="text-center">
                        <form action="{{ route('admin.currencies.toggle', $currency->id) }}"
                              method="POST" style="display:inline-block;">
                          @csrf
                          @method('PATCH')
                          <div class="form-check form-switch">
                            <input class="form-check-input"
                                   type="checkbox"
                                   role="switch"
                                   onchange="this.form.submit()"
                                   {{ $currency->status ? 'checked' : '' }}>
                          </div>
                        </form>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="6" class="text-center">No currencies found.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
    
          </div>
        </div>
      </div>

    <x-slot:footerFiles>
        {{-- Additional scripts if required --}}
    </x-slot>
</x-base-layout>
