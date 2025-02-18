<x-base-layout :scrollspy="false">

    <x-slot:pageTitle>
        Plan List
    </x-slot>

    <x-slot:headerFiles>
        @vite(['resources/scss/light/assets/components/modal.scss'])
    </x-slot>

    <div class="row layout-top-spacing">
        <div class="col-12 layout-spacing">
            <div class="widget p-4">
                <div class="widget-header d-flex align-items-center">
                    <h4>Investment Plan</h4>
                    <button class="btn btn-primary btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#createPackageModal">+ Plan</button>
                </div>

                <div class="widget-content">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Rate</th>
                                    <th>Max Payout</th>
                                    <th>Bonus</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($packages as $package)
                                <tr>
                                    <td>{{ $package->name }}</td>
                                    <td>{{ $package->eshare }}</td>
                                    <td>{{ $package->max_payout }}</td>
                                    <td>{{ $package->profit }}</td>
                                    <td>
                                        <span class="badge {{ $package->status ? 'badge-light-success' : 'badge-light-danger' }}">
                                            {{ $package->status ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </td>
                                    <td>{{ $package->created_at->format('d M Y') }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editPackageModal{{ $package->id }}">Edit</button>
                                        @if ($package->status)
                                            <form action="{{ route('packages.disable', $package->id) }}" method="POST" style="display: inline-block;">
                                                @csrf
                                                <button type="submit" class="btn btn-danger btn-sm">Disable</button>
                                            </form>
                                        @else
                                            <form action="{{ route('packages.enable', $package->id) }}" method="POST" style="display: inline-block;">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm">Enable</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>

                                <!-- Edit Package Modal -->
                                <div class="modal fade" id="editPackageModal{{ $package->id }}" tabindex="-1" aria-labelledby="editPackageModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form action="{{ route('packages.update', $package->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editPackageModalLabel">Edit Investment Plan</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="form-group mb-3">
                                                        <label for="name">Name</label>
                                                        <input type="text" name="name" class="form-control" value="{{ $package->name }}" required>
                                                    </div>
                                                    <div class="form-group mb-3">
                                                        <label for="eshare">Rate</label>
                                                        <input type="number" step="0.01" name="eshare" class="form-control" value="{{ $package->eshare }}" required>
                                                    </div>
                                                    <div class="form-group mb-3">
                                                        <label for="max_payout">Max Payout</label>
                                                        <input type="number" step="0.01" name="max_payout" class="form-control" value="{{ $package->max_payout }}" required>
                                                    </div>
                                                    <div class="form-group mb-3">
                                                        <label for="profit">Bonus</label>
                                                        <input type="number" step="0.01" name="profit" class="form-control" value="{{ $package->profit }}" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Update</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Package Modal -->
    <div class="modal fade" id="createPackageModal" tabindex="-1" aria-labelledby="createPackageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('packages.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createPackageModalLabel">Create Investment Plan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="name">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="eshare">Rate</label>
                            <input type="number" step="0.01" name="eshare" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="max_payout">Max Payout</label>
                            <input type="number" step="0.01" name="max_payout" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="profit">Bonus</label>
                            <input type="number" step="0.01" name="profit" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <x-slot:footerFiles>
        {{-- Add any specific scripts if required --}}
    </x-slot>
</x-base-layout>
