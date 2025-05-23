<x-base-layout :scrollspy="false">

    {{-- Set page title --}}
    <x-slot:pageTitle>
        Campaign Tool
    </x-slot>

    {{-- Optional custom styles --}}
    <x-slot:headerFiles>
        {{-- Add custom styles here if needed --}}
    </x-slot>

    <div class="row layout-top-spacing">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">Enter Password to Access Tool</h4>
                <a href="{{ route('tool.logout') }}" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>

            <div class="card p-4 shadow-sm rounded-4">
                <form method="POST" action="{{ route('tool.index') }}">
                    @csrf
                    <div class="mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Password">
                        @error('password')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Enter</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Optional custom scripts --}}
    <x-slot:footerFiles>
        {{-- Add JS here if needed --}}
    </x-slot>
</x-base-layout>
