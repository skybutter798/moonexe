<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pair;
use Carbon\Carbon;

class SeedPairs extends Command
{
    protected $signature = 'seed:pairs';
    protected $description = 'Seed demo pairs one per day from 2025-02-13 to 2025-03-16';

    public function handle()
    {
        // Set start and end dates with a default time (e.g., 2:00 PM)
        $startDate = Carbon::parse('2025-02-13 14:00:00');
        $endDate   = Carbon::parse('2025-03-16 14:00:00');

        // Allowed values for the random fields
        $volumes   = [100000, 200000, 300000, 400000, 500000];
        $gateTimes = [100, 200, 300, 400, 500];
        $endTimes  = [18, 24];

        $currentDate = $startDate->copy();
        $count = 0;

        while ($currentDate->lte($endDate)) {
            // Random values based on your criteria:
            // currency_id: random from 2 to 7
            $currencyId = rand(2, 7);
            // pair_id is always 1
            $pairId = 1;
            // min_rate is always 1
            $minRate = 1;
            // earning_gap (which we store in 'rate') random from 0.61 to 0.71, rounded to 2 decimal places
            $earningGap = round(rand(61, 71) / 100, 2);
            // volume: pick one of the allowed values
            $volume = $volumes[array_rand($volumes)];
            // gate_time: random value from the given list
            $gateTime = $gateTimes[array_rand($gateTimes)];
            // end_time: randomly 18 or 24
            $endTime = $endTimes[array_rand($endTimes)];

            // Create the Pair record with the desired created_at/updated_at date
            Pair::create([
                'currency_id' => $currencyId,
                'pair_id'     => $pairId,
                'min_rate'    => $minRate,
                // We store the earning_gap in the 'rate' column per your store() method,
                // and calculate max_rate as min_rate + earning_gap.
                'rate'        => $earningGap,
                'max_rate'    => $minRate + $earningGap,
                'volume'      => $volume,
                'gate_time'   => $gateTime,
                'end_time'    => $endTime,
                'created_at'  => $currentDate,
                'updated_at'  => $currentDate,
            ]);

            $this->info("Created pair for date: " . $currentDate->toDateTimeString());

            // Move to the next day
            $currentDate->addDay();
            $count++;
        }

        $this->info("Created {$count} pairs.");
    }
}
