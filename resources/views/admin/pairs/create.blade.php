<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>
        Add Currency Pair
    </x-slot>

    <x-slot:headerFiles>
        @vite(['resources/scss/light/assets/components/modal.scss'])
    </x-slot>

    <div class="row layout-top-spacing">
        <div class="col-12 layout-spacing">
            <div class="widget p-4">
                <div class="widget-header">
                    <h4>Add Currency Pair</h4>
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

                    <form action="{{ route('admin.pairs.store') }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="currency_id">Base Currency</label>
                            <select name="currency_id" id="currency_id" class="form-control" required>
                                <option value="">Select Base Currency</option>
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->id }}" {{ old('currency_id') == $currency->id ? 'selected' : '' }}>
                                        {{ $currency->c_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="pair_id">Quote Currency</label>
                            <select name="pair_id" id="pair_id" class="form-control" required>
                                <option value="">Select Quote Currency</option>
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->id }}" {{ old('pair_id') == $currency->id ? 'selected' : '' }}>
                                        {{ $currency->c_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="rate">Rate</label>
                            <input type="text" name="rate" id="rate" class="form-control" value="{{ old('rate') }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="volume">Volume</label>
                            <input type="text" name="volume" id="volume" class="form-control" value="{{ old('volume') }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="gate_time">Gate Time</label>
                            <!-- HTML5 datetime-local input (minute precision) -->
                            <input type="datetime-local" name="gate_time" id="gate_time" class="form-control" value="{{ old('gate_time') }}" required>
                            <small class="form-text text-muted">Select gate time (minutes precision)</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Pair</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <x-slot:footerFiles>
        {{-- Additional scripts if needed --}}
    </x-slot>
</x-base-layout>
