{{-- resources/views/app/directrange/index.blade.php --}}
<x-base-layout :scrollspy="false">

    <x-slot:pageTitle>Direct & Matching Ranges</x-slot>

    <x-slot:headerFiles>
        @vite(['resources/scss/light/assets/components/modal.scss'])
    </x-slot>

    <div class="row layout-top-spacing">
        <div class="col-12 layout-spacing">

            {{-- Direct Ranges --}}
            <div class="widget p-4">
                <div class="widget-header d-flex align-items-center mb-3">
                    <h4 class="mb-0">Direct Ranges</h4>
                    <button class="btn btn-primary btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#createRangeModal">
                        + Range
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th>Name</th>
                                <th>Min</th>
                                <th>Max</th>
                                <th>Percentage</th>
                                <th>Created At</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($directranges as $range)
                                <tr>
                                    <td>{{ $range->name }}</td>
                                    <td>{{ $range->min }}</td>
                                    <td>{{ $range->max }}</td>
                                    <td>{{ $range->percentage }}%</td>
                                    <td>{{ $range->created_at->format('d M Y') }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editRangeModal{{ $range->id }}">
                                            Edit
                                        </button>
                                        <form action="{{ route('admin.directranges.destroy', $range->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>

                                {{-- Edit DirectRange Modal --}}
                                <div class="modal fade" id="editRangeModal{{ $range->id }}" tabindex="-1" aria-labelledby="editRangeModalLabel{{ $range->id }}" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form action="{{ route('admin.directranges.update', $range->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editRangeModalLabel{{ $range->id }}">Edit Direct Range</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Name</label>
                                                        <input type="text" name="name" class="form-control" value="{{ $range->name }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Min</label>
                                                        <input type="number" name="min" class="form-control" value="{{ $range->min }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Max</label>
                                                        <input type="number" name="max" class="form-control" value="{{ $range->max }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Percentage</label>
                                                        <input type="number" step="0.01" name="percentage" class="form-control" value="{{ $range->percentage }}" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No direct ranges found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Matching Ranges --}}
            <div class="widget p-4 mt-5">
                <div class="widget-header d-flex align-items-center mb-3">
                    <h4 class="mb-0">Matching Ranges</h4>
                    <button class="btn btn-primary btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#createMatchingModal">
                        + Range
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="bg-secondary text-white">
                            <tr>
                                <th>Name</th>
                                <th>Min</th>
                                <th>Max</th>
                                <th>Percentage</th>
                                <th>Created At</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($matchingranges as $range)
                                <tr>
                                    <td>{{ $range->name }}</td>
                                    <td>{{ $range->min }}</td>
                                    <td>{{ $range->max }}</td>
                                    <td>{{ $range->percentage }}%</td>
                                    <td>{{ $range->created_at->format('d M Y') }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editMatchingModal{{ $range->id }}">
                                            Edit
                                        </button>
                                        <form action="{{ route('admin.matchingranges.destroy', $range->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>

                                {{-- Edit MatchingRange Modal --}}
                                <div class="modal fade" id="editMatchingModal{{ $range->id }}" tabindex="-1" aria-labelledby="editMatchingModalLabel{{ $range->id }}" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form action="{{ route('admin.matchingranges.update', $range->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editMatchingModalLabel{{ $range->id }}">Edit Matching Range</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Name</label>
                                                        <input type="text" name="name" class="form-control" value="{{ $range->name }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Min</label>
                                                        <input type="number" name="min" class="form-control" value="{{ $range->min }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Max</label>
                                                        <input type="number" name="max" class="form-control" value="{{ $range->max }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Percentage</label>
                                                        <input type="number" step="0.01" name="percentage" class="form-control" value="{{ $range->percentage }}" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No matching ranges found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    {{-- Create DirectRange Modal --}}
    <div class="modal fade" id="createRangeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('admin.directranges.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Direct Range</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Min</label>
                            <input type="number" name="min" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Max</label>
                            <input type="number" name="max" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Percentage</label>
                            <input type="number" step="0.01" name="percentage" class="form-control" required>
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

    {{-- Create MatchingRange Modal --}}
    <div class="modal fade" id="createMatchingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('admin.matchingranges.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Matching Range</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Min</label>
                            <input type="number" name="min" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Max</label>
                            <input type="number" name="max" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Percentage</label>
                            <input type="number" step="0.01" name="percentage" class="form-control" required>
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
        @vite(['resources/assets/js/custom.js'])
        <script src="{{ asset('plugins/global/vendors.min.js') }}"></script>
        <script src="{{ asset('plugins/jquery-ui/jquery-ui.min.js') }}"></script>
    </x-slot>

</x-base-layout>
