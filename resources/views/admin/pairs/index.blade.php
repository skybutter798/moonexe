<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>
        Currency Pairs
    </x-slot>

    <x-slot:headerFiles>
        @vite(['resources/scss/light/assets/components/modal.scss'])
    </x-slot>

    <div class="row layout-top-spacing">
        <div class="col-12 layout-spacing">
            <div class="widget p-4">
                <div class="widget-header d-flex align-items-center justify-content-between">
                    <h4>Currency Pairs</h4>
                    <a href="{{ route('admin.pairs.create') }}" class="btn btn-primary">Add Pair</a>
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
                                    <th>Pair (e.g. MYR/USD)</th>
                                    <th>Rate</th>
                                    <th>Volume</th>
                                    <th>Gate Time</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pairs as $pair)
                                    <tr>
                                        <td>{{ $pair->id }}</td>
                                        <td>
                                            {{ $pair->currency->c_name }} / {{ $pair->pairCurrency->c_name }}
                                        </td>
                                        <td>{{ $pair->rate }}</td>
                                        <td>{{ $pair->volume }}</td>
                                        <td>{{ \Carbon\Carbon::parse($pair->gate_time)->format('d M Y H:i') }}</td>
                                        <td>{{ $pair->created_at->format('d M Y') }}</td>
                                    </tr>
                                @endforeach
                                @if($pairs->isEmpty())
                                    <tr>
                                        <td colspan="6" class="text-center">No currency pairs found.</td>
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
