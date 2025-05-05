<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>Wallet Control — Edit {{ $user->name }}</x-slot:pageTitle> 

    <x-slot:headerFiles>
        @vite(['resources/scss/light/assets/components/modal.scss'])
    </x-slot:headerFiles>

    <div class="row layout-top-spacing">
        <div class="col-lg-6 mx-auto">
            <form
                action="{{ route('admin.wallets.update', $user) }}"
                method="POST"
                class="widget p-4"
            >
                @csrf
                @method('PUT')

                <h4 class="mb-4">Adjust Balances</h4>

                @foreach (['cash', 'trading', 'earning', 'affiliates'] as $type)
                    <div class="mb-3">
                        <label class="form-label text-capitalize">{{ $type }} wallet</label>
                        <input
                            type="number"
                            step="0.01"
                            name="{{ $type }}_wallet"
                            class="form-control @error($type.'_wallet') is-invalid @enderror"
                            value="{{ old($type.'_wallet', $user->wallet->{$type . '_wallet'}) }}"
                        >
                        @error($type.'_wallet')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach

                <div class="d-flex justify-content-between">
                    <a href="{{ route('admin.wallets.index') }}" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                    <button class="btn btn-success">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <x-slot:footerFiles></x-slot:footerFiles>
</x-base-layout>
