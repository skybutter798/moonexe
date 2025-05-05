<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>Wallet Control</x-slot:pageTitle> 

    <x-slot:headerFiles>
        @vite(['resources/scss/light/assets/components/modal.scss'])
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    </x-slot:headerFiles>

    <div class="row layout-top-spacing">
        <div class="col-12">

            {{-- Search Form --}}
            <form method="GET" class="mb-3 d-flex">
                <input
                    type="text"
                    name="search"
                    value="{{ old('search', $search) }}"
                    class="form-control me-2"
                    placeholder="Search by name or email"
                />
                <button type="submit" class="btn btn-primary" aria-label="Search">
                    <i class="bi bi-search"></i>
                </button>
            </form>

            {{-- Results Table --}}
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Cash</th>
                            <th>Trading</th>
                            <th>Earning</th>
                            <th>Affiliates</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ number_format($user->wallet->cash_wallet, 4) }}</td>
                            <td>{{ number_format($user->wallet->trading_wallet, 4) }}</td>
                            <td>{{ number_format($user->wallet->earning_wallet, 4) }}</td>
                            <td>{{ number_format($user->wallet->affiliates_wallet, 4) }}</td>
                            <td class="text-end">
                                <button
                                    class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#walletModal"
                                    data-user-id="{{ $user->id }}"
                                    data-user-name="{{ $user->name }}"
                                    data-cash="{{ $user->wallet->cash_wallet }}"
                                    data-trading="{{ $user->wallet->trading_wallet }}"
                                    data-earning="{{ $user->wallet->earning_wallet }}"
                                    data-affiliates="{{ $user->wallet->affiliates_wallet }}"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    @if($users->isEmpty())
                        <tr>
                            <td colspan="7" class="text-center">No users found.</td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>

            {{ $users->withQueryString()->links() }}
        </div>
    </div>

    {{-- Modal --}}
    <div class="modal fade" id="walletModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <form method="POST" class="modal-content" id="walletForm">
          @csrf @method('PUT')
          <div class="modal-header">
            <h5 class="modal-title">Edit Wallet — <span id="modalUserName"></span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            @foreach (['cash','trading','earning','affiliates'] as $type)
              <div class="mb-3">
                <label class="form-label text-capitalize">{{ $type }} wallet</label>
                <div class="input-group">
                  <button type="button" class="btn btn-outline-secondary decrement" data-field="{{ $type }}_wallet">–</button>
                  <input
                    type="number"
                    step="0.01"
                    name="{{ $type }}_wallet"
                    id="{{ $type }}Wallet"
                    class="form-control text-end"
                  >
                  <button type="button" class="btn btn-outline-secondary increment" data-field="{{ $type }}_wallet">+</button>
                </div>
              </div>
            @endforeach
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-success">Save Changes</button>
          </div>
        </form>
      </div>
    </div>

    <x-slot:footerFiles>
      <script>
        var walletModal = document.getElementById('walletModal');
        walletModal.addEventListener('show.bs.modal', function (evt) {
          var btn = evt.relatedTarget;
          var userId = btn.getAttribute('data-user-id');
          var userName = btn.getAttribute('data-user-name');
          var form = document.getElementById('walletForm');

          // update form action
          form.action = '/admin/wallets/' + userId;

          // set modal title
          document.getElementById('modalUserName').textContent = userName;

          // populate fields
          ['cash','trading','earning','affiliates'].forEach(function(type){
            var val = btn.getAttribute('data-' + type);
            document.getElementById(type + 'Wallet').value = parseFloat(val).toFixed(2);
          });
        });

        // increment/decrement handlers
        document.querySelectorAll('.increment, .decrement').forEach(function(button){
          button.addEventListener('click', function(){
            var field = this.getAttribute('data-field');
            var input = document.querySelector('[name="'+field+'"]');
            var step = parseFloat(input.step) || 1;
            var current = parseFloat(input.value) || 0;
            input.value = ( this.classList.contains('increment')
                            ? current + step
                            : current - step
                          ).toFixed(2);
          });
        });
      </script>
    </x-slot:footerFiles>
</x-base-layout>
