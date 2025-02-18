<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>
        Add Currency
    </x-slot>

    <x-slot:headerFiles>
        @vite(['resources/scss/light/assets/components/modal.scss'])
    </x-slot>

    <div class="row layout-top-spacing">
        <div class="col-12 layout-spacing">
            <div class="widget p-4">
                <div class="widget-header">
                    <h4>Add Currency</h4>
                </div>
                <div class="widget-content mt-4">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                  <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.currencies.store') }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="c_name">Currency Name</label>
                            <input type="text" name="c_name" id="c_name" class="form-control" value="{{ old('c_name') }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Currency</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <x-slot:footerFiles>
        {{-- Additional scripts if needed --}}
    </x-slot>
</x-base-layout>
