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
                <!-- 1. Title as H2 with mb-0 -->
                <div class="widget-header">
                    <h2 class="mb-0">Add Currency Pair</h2>
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

                        <!-- 2. Base Currency (only status=1) -->
                        <div class="form-group mb-3">
                            <label for="currency_id">Base Currency</label>
                            <select name="currency_id" id="currency_id" class="form-control" required>
                                <option value="">Select Base Currency</option>
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->id }}"
                                        {{ old('currency_id') == $currency->id ? 'selected' : '' }}>
                                        {{ $currency->c_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Quote Currency fixed to ID 1 -->
                        <div class="form-group mb-3">
                            <label for="pair_id">Quote Currency</label>
                            <input type="hidden" name="pair_id" value="1">
                            <input type="text" class="form-control"
                                   value="{{ $currencies->firstWhere('id',1)->c_name ?? 'N/A' }}"
                                   disabled>
                        </div>

                        <!-- 4. min_rate & max_rate hidden, always 0 -->
                        <input type="hidden" name="min_rate" id="min_rate" value="0">
                        <input type="hidden" name="max_rate" id="max_rate" value="0">

                        <!-- 3. Exchange Rate = rate in pairs table -->
                        <div class="form-group mb-3">
                            <label for="rate">Exchange Rate (Earning %)</label>
                            <input type="text" name="rate" id="rate" class="form-control"
                                   value="{{ old('rate') }}" placeholder="0.60" required>
                        </div>

                        <!-- Gate Time & End Time unchanged -->
                        <div class="form-group mb-3 row">
                            <div class="col-md-6">
                                <label for="gate_time">Gate Time (in minutes)</label>
                                <input type="number" name="gate_time" id="gate_time"
                                       class="form-control" value="{{ old('gate_time') }}" required>
                                <small id="gate_time_tip" class="form-text text-muted"></small>
                            </div>
                            <div class="col-md-6">
                                <label for="end_time">End Time (in hours)</label>
                                <select name="end_time" id="end_time" class="form-control" required>
                                    <option value="">Select End Time</option>
                                    @foreach(range(6, 36, 6) as $hour)
                                        <option value="{{ $hour }}"
                                            {{ old('end_time') == $hour ? 'selected' : '' }}>
                                            {{ $hour }} Hour{{ $hour > 1 ? 's' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <small id="end_time_tip" class="form-text text-muted"></small>
                            </div>
                        </div>

                        <!-- 5. Volume + split totals -->
                        <div class="form-group mb-3">
                            <label for="volume">Volume (USDT)</label>
                            <input type="text" name="volume" id="volume" class="form-control"
                                   value="{{ old('volume') }}" required>
                        </div>
                        
                        @if(isset($totalBalanceSmall, $totalBalanceLarge, $unclaimedSmall, $unclaimedLarge))
                            <div class="form-group mb-3 row">
                                <!-- Total trading margin -->
                                <div class="col-md-6">
                                    <label>Total Trading Margin (Wallet):</label>
                                    <div>
                                        {{ number_format($totalBalanceSmall) }}
                                        |
                                        <span class="text-danger">{{ number_format($totalBalanceLarge) }}</span>
                                    </div>
                                </div>
                        
                                <!-- Total unclaimed trading margin -->
                                <div class="col-md-6">
                                    <label>Total Trading Margin (Unclaimed):</label>
                                    <div>
                                        {{ number_format($unclaimedSmall) }}
                                        |
                                        <span class="text-danger">{{ number_format($unclaimedLarge) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif


                        <button type="submit" class="btn btn-primary">Add Pair</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <x-slot:footerFiles>
        <script>
            function computeGateTimeTip(){
                var m = parseInt(document.getElementById('gate_time').value);
                if(m > 0){
                    var t = new Date();
                    t.setMinutes(t.getMinutes() + m);
                    document.getElementById('gate_time_tip').innerText =
                        "Ends at " + t.toLocaleString();
                } else {
                    document.getElementById('gate_time_tip').innerText = "";
                }
            }
            document.getElementById('gate_time')
                    .addEventListener('input', computeGateTimeTip);

            function computeEndTimeTip(){
                var h = parseInt(document.getElementById('end_time').value);
                if(h > 0){
                    var t = new Date();
                    t.setHours(t.getHours() + h);
                    document.getElementById('end_time_tip').innerText =
                        "Ends at " + t.toLocaleString();
                } else {
                    document.getElementById('end_time_tip').innerText = "";
                }
            }
            document.getElementById('end_time')
                    .addEventListener('change', computeEndTimeTip);
        </script>
    </x-slot>
</x-base-layout>
