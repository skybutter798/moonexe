<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>
        Currencies
    </x-slot>

    <x-slot:headerFiles>
        @vite(['resources/scss/light/assets/components/modal.scss'])
    </x-slot>

    <div class="row layout-top-spacing">
        <div class="col-12 layout-spacing">
            <div class="widget p-4">
                <div class="widget-header d-flex align-items-center justify-content-between">
                    <h4>Currencies</h4>
                    <a href="{{ route('admin.currencies.create') }}" class="btn btn-primary">Add Currency</a>
                </div>
                <div class="widget-content mt-4">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Currency Name</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($currencies as $currency)
                                    <tr>
                                        <td>{{ $currency->id }}</td>
                                        <td>{{ $currency->c_name }}</td>
                                        <td>{{ $currency->status == 1 ? 'Active' : 'Inactive' }}</td>
                                        <td>{{ $currency->created_at->format('d M Y') }}</td>
                                    </tr>
                                @endforeach
                                @if($currencies->isEmpty())
                                    <tr>
                                        <td colspan="4" class="text-center">No currencies found.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot:footerFiles>
        {{-- Additional scripts if required --}}
    </x-slot>
</x-base-layout>
