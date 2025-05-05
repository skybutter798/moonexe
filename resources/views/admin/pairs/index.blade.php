<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>Pair List</x-slot:pageTitle>

    <x-slot:headerFiles>
        @vite(['resources/scss/light/assets/components/modal.scss'])
        <style>
            /* Force header row to not break */
            #pairs-table thead tr { white-space: nowrap; }
            /* Compact filter inputs */
            .filter-form .form-control { padding: .25rem .5rem; font-size: .875rem; }
        </style>
    </x-slot:headerFiles>

    <div class="row layout-top-spacing">
        <div class="col-12 layout-spacing">
            <div class="widget p-4">
                <div class="widget-header">
                    <h2 class="mb-0">Pair List</h2>
                </div>
                <div class="widget-content mt-3">
                    @if(session('success'))
                        <div class="alert alert-success py-1">{{ session('success') }}</div>
                    @endif
                    
                    <form method="GET" class="row gx-1 gy-1 align-items-center mb-3 filter-form">
                        <div class="col-auto">
                            <input type="text" name="id" value="{{ request('id') }}" class="form-control" placeholder="ID">
                        </div>
                        <div class="col-auto">
                            <input type="text" name="currency" value="{{ request('currency') }}" class="form-control" placeholder="Currency">
                        </div>
                        <div class="col-auto">
                            <input type="number" step="0.01" name="volume" value="{{ request('volume') }}" class="form-control" placeholder="Vol">
                        </div>
                        <div class="col-auto">
                            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                        </div>
                        <div class="col-auto">
                            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-sm px-2">Filter</button>
                        </div>
                        <div class="col-auto">
                            <a href="{{ route('admin.pairs.index') }}" class="btn btn-primary btn-sm px-2">Reset</a>
                        </div>
                        <div class="col-auto ms-auto">
                            <a href="{{ route('admin.pairs.create') }}" class="btn btn-primary p-1" style="width:30px; height:30px">+</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="pairs-table">
                          <thead class="bg-dark text-white">
                            <tr>
                              <th>ID</th>
                              <th>Pair</th>
                              <th>Volume</th>
                              <th>Remaining</th>
                              <th>Estimate Rate</th>
                              <th>Actual Rate</th>
                              <th>Gate Close</th>
                              <th>Claiming Time</th>
                              <th>Status</th>
                              <th>Created At</th>
                              <th>Action</th>
                            </tr>
                          </thead>
                          <tbody>
                            @foreach($pairs as $pair)
                              @php
                                $gateCloseTs = $pair->created_at->copy()->addMinutes($pair->gate_time)->getTimestamp() * 1000;
                                $endTimeTs   = $pair->created_at->copy()->addHours($pair->end_time)->getTimestamp() * 1000;
                                $remaining   = $pair->volume - $pair->orders->sum('receive');
                                $estimate    = $pair->rate;
                                $firstOrder  = $pair->orders->first();
                                $actual      = $firstOrder ? $firstOrder->est_rate : null;
                              @endphp
                              <tr>
                                <td>{{ $pair->id }}</td>
                                <td>{{ $pair->currency->c_name }} / {{ $pair->pairCurrency->c_name }}</td>
                                <td>{{ number_format($pair->volume, 2) }}</td>
                                <td>{{ number_format($remaining, 2) }}</td>
                                <td><span class="badge bg-dark">{{ number_format($estimate, 2) }}</span></td>
                                <td><span class="badge bg-dark">{{ $actual !== null ? number_format($actual, 2) : 'pairing' }}</span></td>
                                <td><span class="badge bg-success countdown" data-gate-close="{{ $gateCloseTs }}">--</span></td>
                                <td><span class="badge bg-success countdown-end" data-end-time="{{ $endTimeTs }}">--</span></td>
                                
                                <!-- Status -->
                                <td>
                                  @if($pair->status)
                                    <span class="badge bg-success">Active</span>
                                  @else
                                    <span class="badge bg-dark">Inactive</span>
                                  @endif
                                </td>
                                
                                <td>{{ $pair->created_at->format('d M Y H:i') }}</td>
                                
                                <!-- Actions -->
                                <td>
                                  <a href="{{ route('admin.pairs.edit', $pair->id) }}"
                                     class="btn btn-dark btn-sm">
                                    Edit
                                  </a>
                                
                                  @if($pair->status)
                                    <form action="{{ route('admin.pairs.disable', $pair->id) }}"
                                          method="POST"
                                          style="display:inline">
                                      @csrf
                                      @method('PATCH')
                                      <button type="submit"
                                              class="btn btn-dark btn-sm">
                                        Disable
                                      </button>
                                    </form>
                                  @endif
                                </td>
                              </tr>
                            @endforeach

                            @if($pairs->isEmpty())
                              <tr>
                                <td colspan="9" class="text-center py-3">No currency pairs found.</td>
                              </tr>
                            @endif
                          </tbody>
                        </table>
                        {{ $pairs->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot:footerFiles>
        <script>
          function updateCountdowns() {
            const now = Date.now();
            document.querySelectorAll('.countdown').forEach(el => {
              const target = +el.dataset.gateClose;
              let diff = Math.floor((target - now) / 1000);
              if (diff > 0) {
                el.classList.replace('bg-dark','bg-success');
                const h=String(Math.floor(diff/3600)).padStart(2,'0');
                const m=String(Math.floor((diff%3600)/60)).padStart(2,'0');
                const s=String(diff%60).padStart(2,'0');
                el.textContent = `${h}:${m}:${s}`;
              } else {
                el.textContent = 'Ended';
                el.classList.replace('bg-success','bg-dark');
              }
            });
            document.querySelectorAll('.countdown-end').forEach(el => {
              const target=+el.dataset.endTime;
              let diff=Math.floor((target-now)/1000);
              if(diff>0){
                el.classList.replace('bg-dark','bg-success');
                const h=String(Math.floor(diff/3600)).padStart(2,'0');
                const m=String(Math.floor((diff%3600)/60)).padStart(2,'0');
                const s=String(diff%60).padStart(2,'0');
                el.textContent=`${h}:${m}:${s}`;
              } else {
                el.textContent='Ended';
                el.classList.replace('bg-success','bg-dark');
              }
            });
          }
          updateCountdowns();
          setInterval(updateCountdowns,1000);
        </script>
    </x-slot:footerFiles>
</x-base-layout>
