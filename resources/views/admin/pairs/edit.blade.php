<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>Edit Currency Pair</x-slot>

    <x-slot:headerFiles>
        @vite(['resources/scss/light/assets/components/modal.scss'])
    </x-slot>

    <div class="row layout-top-spacing">
        <div class="col-12 layout-spacing">
            <div class="widget p-4">
                <div class="widget-header">
                    <h2 class="mb-0">Edit Currency Pair</h2>
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

                    <form action="{{ route('admin.pairs.update', $pair->id) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <!-- Base Currency -->
                        <div class="form-group mb-3">
                            <label for="currency_id">Base Currency</label>
                            <select name="currency_id" id="currency_id" class="form-control" required>
                                <option value="">Select Base Currency</option>
                                @foreach($currencies as $currency)
                                  <option value="{{ $currency->id }}"
                                    {{ old('currency_id', $pair->currency_id) == $currency->id ? 'selected' : '' }}>
                                      {{ $currency->c_name }}
                                  </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Quote Currency (fixed) -->
                        <div class="form-group mb-3">
                            <label>Quote Currency</label>
                            <input type="hidden" name="pair_id" value="1">
                            <input type="text" class="form-control" 
                                   value="{{ $currencies->firstWhere('id',1)->c_name }}" 
                                   disabled>
                        </div>

                        <!-- Hidden min/max -->
                        <input type="hidden" name="min_rate" value="0">
                        <input type="hidden" name="max_rate" value="0">

                        <!-- Rate -->
                        <div class="form-group mb-3">
                            <label for="rate">Exchange Rate (Earning %)</label>
                            <input type="text" name="rate" id="rate" class="form-control"
                                   value="{{ old('rate', $pair->rate) }}" required>
                        </div>

                        <!-- Gate Time & End Time -->
                        <div class="form-group mb-3 row">
                            <div class="col-md-6">
                                <label for="gate_time">Gate Time (minutes)</label>
                                <input type="number" name="gate_time" id="gate_time"
                                       class="form-control"
                                       value="{{ old('gate_time', $pair->gate_time) }}"
                                       required>
                                <small id="gate_time_tip" class="form-text text-muted"></small>
                            </div>
                            <div class="col-md-6">
                                <label for="end_time">End Time (hours)</label>
                                <select name="end_time" id="end_time" class="form-control" required>
                                    <option value="">Select End Time</option>
                                    @foreach(range(6,36,6) as $hour)
                                      <option value="{{ $hour }}"
                                        {{ old('end_time', $pair->end_time) == $hour ? 'selected' : '' }}>
                                          {{ $hour }} Hour{{ $hour>1?'s':'' }}
                                      </option>
                                    @endforeach
                                </select>
                                <small id="end_time_tip" class="form-text text-muted"></small>
                            </div>
                        </div>

                        <!-- Volume -->
                        <div class="form-group mb-3">
                            <label for="volume">Volume</label>
                            <input type="text" name="volume" id="volume" class="form-control"
                                   value="{{ old('volume', $pair->volume) }}"
                                   required>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Pair</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <x-slot:footerFiles>
      <script>
        function computeGateTimeTip(){
          let m = parseInt(document.getElementById('gate_time').value);
          if(m>0){
            let t=new Date(); t.setMinutes(t.getMinutes()+m);
            document.getElementById('gate_time_tip').innerText = "Ends at "+t.toLocaleString();
          } else {
            document.getElementById('gate_time_tip').innerText = "";
          }
        }
        document.getElementById('gate_time').addEventListener('input', computeGateTimeTip);

        function computeEndTimeTip(){
          let h = parseInt(document.getElementById('end_time').value);
          if(h>0){
            let t=new Date(); t.setHours(t.getHours()+h);
            document.getElementById('end_time_tip').innerText = "Ends at "+t.toLocaleString();
          } else {
            document.getElementById('end_time_tip').innerText = "";
          }
        }
        document.getElementById('end_time').addEventListener('change', computeEndTimeTip);
      </script>
    </x-slot>
</x-base-layout>
