<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>
        Currency Pairs
    </x-slot:pageTitle>

    <x-slot:headerFiles>
        @vite(['resources/scss/light/assets/components/modal.scss'])
        <style>
            /* Force header row to not break */
            #pairs-table thead tr {
                white-space: nowrap;
            }
        </style>
    </x-slot:headerFiles>

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
                        <table class="table table-bordered" id="pairs-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Pair (e.g. MYR/USD)</th>
                                    <th>Min Rate</th>
                                    <th>Max Rate</th>
                                    <th>Exchange</th>
                                    <th>Expect Earning</th>
                                    <th>Volume</th>
                                    <th>Remaining</th>
                                    <th>Gate Close</th>
                                    <th>Countdown</th>
                                    <th>End Time</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pairs as $pair)
                                    @php
                                        // Calculate gate close time.
                                        $gateClose = $pair->created_at->copy()->addMinutes($pair->gate_time);
                                        // Calculate end time by adding the number of hours in end_time to created_at.
                                        $endTime = $pair->created_at->copy()->addHours($pair->end_time);
                                        // Calculate expected earning range.
                                        $expectedLow = $pair->min_rate + $pair->rate;
                                        $expectedHigh = $pair->max_rate + $pair->rate;
                                    @endphp
                                    <tr>
                                        <td>{{ $pair->id }}</td>
                                        <td>{{ $pair->currency->c_name }} / {{ $pair->pairCurrency->c_name }}</td>
                                        <td>{{ number_format($pair->min_rate, 2) }}</td>
                                        <td>{{ number_format($pair->max_rate, 2) }}</td>
                                        <td>+ {{ number_format($pair->rate, 2) }}</td>
                                        <td>{{ number_format($expectedLow, 2) }} - {{ number_format($expectedHigh, 2) }}</td>
                                        <td>{{ number_format($pair->volume, 2) }}</td>
                                        <td>{{ number_format($pair->volume - $pair->orders->sum('buy'), 2) }}</td>
                                        <td>{{ $gateClose->format('d M Y H:i') }}</td>
                                        <td class="countdown" data-gate-close="{{ $gateClose->getTimestamp() * 1000 }}">--</td>
                                        <td>{{ $endTime->format('d M Y H:i:s') }}</td> <!-- Display full date & time -->
                                        <td>{{ $pair->created_at->format('d M Y') }}</td>
                                    </tr>
                                @endforeach
                                @if($pairs->isEmpty())
                                    <tr>
                                        <td colspan="12" class="text-center">No currency pairs found.</td>
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
        <script>
            function updateCountdowns() {
                var countdownElements = document.querySelectorAll('.countdown');
                var now = new Date().getTime();

                countdownElements.forEach(function(el) {
                    var gateClose = parseInt(el.getAttribute('data-gate-close'));
                    var diffInSeconds = Math.floor((gateClose - now) / 1000);

                    if(diffInSeconds > 0) {
                        var hours   = Math.floor(diffInSeconds / 3600);
                        var minutes = Math.floor((diffInSeconds % 3600) / 60);
                        var seconds = diffInSeconds % 60;
                        el.innerText = ("0" + hours).slice(-2) + ':' + ("0" + minutes).slice(-2) + ':' + ("0" + seconds).slice(-2);
                    } else {
                        el.innerText = 'Ended';
                    }
                });
            }

            updateCountdowns();
            setInterval(updateCountdowns, 1000);
        </script>
    </x-slot:footerFiles>
</x-base-layout>