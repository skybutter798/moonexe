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
                        <!-- Base Currency: skip id=1 -->
                        <div class="form-group mb-3">
                            <label for="currency_id">Base Currency</label>
                            <select name="currency_id" id="currency_id" class="form-control" required>
                                <option value="">Select Base Currency</option>
                                @foreach($currencies as $currency)
                                    @if($currency->id != 1)
                                        <option value="{{ $currency->id }}" {{ old('currency_id') == $currency->id ? 'selected' : '' }}>
                                            {{ $currency->c_name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Quote Currency: fixed to id=1 -->
                        <div class="form-group mb-3">
                            <label for="pair_id">Quote Currency</label>
                            <!-- Force value to 1 -->
                            <input type="hidden" name="pair_id" value="1">
                            @php
                                $quoteCurrency = $currencies->firstWhere('id', 1);
                            @endphp
                            <input type="text" class="form-control" value="{{ $quoteCurrency ? $quoteCurrency->c_name : 'N/A' }}" disabled>
                        </div>
                        
                        <!-- Row: Min Rate and Max Rate -->
                        <div class="form-group mb-3 row">
                            <div class="col-md-6">
                                <input type="hidden" name="min_rate" id="min_rate" class="form-control" value="1.0" required>
                            </div>
                            <div class="col-md-6">
                                <input type="hidden" name="max_rate" id="max_rate" class="form-control" value="1.1" required>
                            </div>
                        </div>
                        
                        <!-- Earning Gap Field -->
                        <div class="form-group mb-3">
                            <label for="earning_gap">Exchange Rate (Earning %)</label>
                            <input type="text" name="earning_gap" id="earning_gap" class="form-control" value="{{ old('earning_gap') }}" placeholder="1.50" required>
                        </div>

                        <!-- Row: Gate Time and End Time -->
                        <div class="form-group mb-3 row">
                            <div class="col-md-6">
                                <label for="gate_time">Gate Time (in minutes)</label>
                                <input type="number" name="gate_time" id="gate_time" class="form-control" value="{{ old('gate_time') }}" required>
                                <small id="gate_time_tip" class="form-text text-muted"></small>
                            </div>
                            <div class="col-md-6">
                                <label for="end_time">End Time (in hours)</label>
                                <select name="end_time" id="end_time" class="form-control" required>
                                    <option value="">Select End Time</option>
                                    @foreach(range(6, 36, 6) as $hour)
                                        <option value="{{ $hour }}" {{ old('end_time') == $hour ? 'selected' : '' }}>{{ $hour }} Hour{{ $hour > 1 ? 's' : '' }}</option>
                                    @endforeach
                                </select>
                                <small id="end_time_tip" class="form-text text-muted"></small>
                            </div>
                        </div>

                        <!-- Volume Field with tip showing total users' balance -->
                        <div class="form-group mb-3">
                            <label for="volume">Volume</label>
                            <input type="text" name="volume" id="volume" class="form-control" 
                                   value="{{ old('volume') }}" 
                                   placeholder="{{ isset($totalBalance) ? $totalBalance * 10 : '' }}"
                                   required>
                            @if(isset($totalBalance))
                                <small class="form-text text-muted">
                                    <!--Total cash balance from all wallets: {{ $totalCash }}-->
                                    Total trading margin from all wallets: {{ $totalBalance }}
                                </small>
                            @endif
                        </div>

                        <button type="submit" class="btn btn-primary">Add Pair</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <x-slot:footerFiles>
        {{-- Additional scripts if needed --}}
        <script>
            // Display gate time tip: calculate end time by adding minutes to current time.
            function computeGateTimeTip(){
                var minutes = parseInt(document.getElementById('gate_time').value);
                if(!isNaN(minutes) && minutes > 0){
                    var current = new Date();
                    current.setMinutes(current.getMinutes() + minutes);
                    var formatted = current.toLocaleString();
                    document.getElementById('gate_time_tip').innerText = "Ends at " + formatted;
                } else {
                    document.getElementById('gate_time_tip').innerText = "";
                }
            }
            document.getElementById('gate_time').addEventListener('input', computeGateTimeTip);
    
            // Display end time tip: calculate end time by adding hours to current time.
            function computeEndTimeTip(){
                var hours = parseInt(document.getElementById('end_time').value);
                if(!isNaN(hours) && hours > 0){
                    var current = new Date();
                    current.setHours(current.getHours() + hours);
                    var formatted = current.toLocaleString();
                    document.getElementById('end_time_tip').innerText = "Ends at " + formatted;
                } else {
                    document.getElementById('end_time_tip').innerText = "";
                }
            }
            document.getElementById('end_time').addEventListener('change', computeEndTimeTip);
        </script>
    </x-slot>
</x-base-layout>