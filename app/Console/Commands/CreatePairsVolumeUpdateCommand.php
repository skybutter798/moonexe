<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pair;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CreatePairsVolumeUpdateCommand extends Command
{
    protected $signature = 'pairs:update';
    protected $description = 'Update active pairs by adding volume every hour';

    public function handle()
    {
        $now   = Carbon::now('Asia/Kuala_Lumpur');
        $today = Carbon::today('Asia/Kuala_Lumpur');

        // only today’s pairs
        $pairs = Pair::whereDate('created_at', $today)->get();

        foreach ($pairs as $pair) {
            // 1) compute gate hours and active drip window
            $gateHours = (int) floor($pair->gate_time / 60);    // e.g. 60 min → 1
            $gateHours = max($gateHours, 1);                    // at least 1
            $activeHrs = max($gateHours - 1, 0);                // subtract final hour, allow 0

            // if there's no drip window, skip
            if ($activeHrs === 0) {
                continue;
            }

            // 2) hours since creation
            $elapsedHrs = $pair->created_at->diffInHours($now);

            // 3) hours since last update
            $lastRunHrs = $pair->created_at->diffInHours($pair->updated_at);

            // 4) how many drips we should have done vs. already did
            $shouldHaveRun = min($elapsedHrs, $activeHrs);
            $alreadyRun    = min($lastRunHrs, $activeHrs);

            if ($shouldHaveRun <= $alreadyRun) {
                // nothing new to do
                continue;
            }

            // 5) compute how many new chunks to apply
            $toDo = $shouldHaveRun - $alreadyRun;

            // 6) recover the original per-hour chunk
            //    since volume after N drips = initialChunk × (N + 1)
            $initialChunk = $pair->volume / ($alreadyRun + 1);

            // 7) apply all missing chunks in one go
            $addition        = $initialChunk * $toDo;
            $pair->volume   += $addition;

            // save() updates updated_at, marking this work as done
            $pair->save();

            Log::channel('pair')
               ->info("Pair #{$pair->id}: +{$addition} over {$toDo} hrs; new volume {$pair->volume}");
        }

        return 0;
    }
}
