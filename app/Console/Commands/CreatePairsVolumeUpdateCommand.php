<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pair;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CreatePairsVolumeUpdateCommand extends Command
{
    protected $signature = 'pairs:update';
    protected $description = 'Update active pairs by adding volume with randomness and guaranteed final drip';

    public function handle()
    {
        $now   = Carbon::now('Asia/Kuala_Lumpur');
        $today = Carbon::today('Asia/Kuala_Lumpur');

        $pairs = Pair::whereDate('created_at', $today)->get();

        foreach ($pairs as $pair) {
            // 1. Convert gate_time to hours
            $gateHours = max((int) floor($pair->gate_time / 60), 1);
            $activeHrs = max($gateHours - 1, 0); // 1 hour reserved at the end

            if ($activeHrs === 0) continue;

            // 2. Time tracking
            $elapsedHrs = $pair->created_at->diffInHours($now);
            $lastRunHrs = $pair->created_at->diffInHours($pair->updated_at);

            $shouldHaveRun = min($elapsedHrs, $activeHrs);
            $alreadyRun    = min($lastRunHrs, $activeHrs);

            if ($shouldHaveRun <= $alreadyRun) continue;

            // 3. Is this the final eligible drip hour?
            $isFinalDripHour = ($elapsedHrs === $activeHrs);

            // 4. Skip randomly unless it's the final drip hour
            if (!$isFinalDripHour && rand(0, 1) === 0) {
                Log::channel('pair')->info("Pair #{$pair->id} skipped randomly.");
                continue;
            }

            // 5. How many missed drips
            $toDo = $shouldHaveRun - $alreadyRun;

            // 6. Calculate base chunk from current volume
            $initialChunk = $pair->volume / ($alreadyRun + 1);

            // 7. Apply random multiplier (80–120%)
            $multiplier = rand(80, 120) / 100;
            $chunk = $initialChunk * $multiplier;

            // 8. Apply missed chunks
            $addition = $chunk * $toDo;
            $pair->volume += $addition;
            $pair->save();

            Log::channel('pair')->info("Pair #{$pair->id}: +{$addition} (toDo: {$toDo}, multiplier: {$multiplier}) → new volume: {$pair->volume}");
        }

        return 0;
    }
}
