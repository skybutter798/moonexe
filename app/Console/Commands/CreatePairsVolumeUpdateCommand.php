<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pair;
use App\Models\Setting;
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

        // ✅ Load special_name from settings table
        $settings = Setting::where('status', 1)
            ->where('name', 'special_name')
            ->pluck('value', 'name')
            ->toArray();

        $specialNames = array_filter(explode(',', $settings['special_name'] ?? ''));

        // ✅ Eager-load currency to avoid multiple DB hits
        $pairs = Pair::with('currency')->whereDate('created_at', $today)->get();

        foreach ($pairs as $pair) {
            if ($pair->currency && in_array($pair->currency->c_name, $specialNames)) {
                $latestPair = Pair::where('currency_id', $pair->currency_id)->orderByDesc('id')->first();

                if ($latestPair && $latestPair->volume > 0) {
                    $maxVolume = $latestPair->volume / 4;

                    if ($pair->volume > $maxVolume) {
                        Log::channel('pair')->info("{$pair->currency->c_name} Pair #{$pair->id} volume capped at {$maxVolume} (current: {$pair->volume})");
                        continue;
                    }
                } else {
                    Log::channel('pair')->warning("{$pair->currency->c_name} has no valid previous pair for max volume check.");
                }
            }

            $gateHours = max((int) floor($pair->gate_time / 60), 1);
            $activeHrs = max($gateHours - 1, 0);

            if ($activeHrs === 0) continue;

            $elapsedHrs = $pair->created_at->diffInHours($now);
            $lastRunHrs = $pair->created_at->diffInHours($pair->updated_at);

            $shouldHaveRun = min($elapsedHrs, $activeHrs);
            $alreadyRun    = min($lastRunHrs, $activeHrs);

            if ($shouldHaveRun <= $alreadyRun) continue;

            $isFinalDripHour = ($elapsedHrs === $activeHrs);

            if (!$isFinalDripHour && rand(0, 1) === 0) {
                Log::channel('pair')->info("Pair #{$pair->id} skipped randomly.");
                continue;
            }

            $toDo = $shouldHaveRun - $alreadyRun;
            $initialChunk = $pair->volume / ($alreadyRun + 1);
            $multiplier = rand(80, 120) / 100;
            $chunk = $initialChunk * $multiplier;
            $addition = $chunk * $toDo;

            $pair->volume += $addition;
            $pair->save();

            Log::channel('pair')->info("Pair #{$pair->id}: +{$addition} (toDo: {$toDo}, multiplier: {$multiplier}) → new volume: {$pair->volume}");
        }

        return 0;
    }
}
