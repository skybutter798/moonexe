<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pair;
use App\Models\WebhookPayment;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use App\Events\OrderUpdated;
use Carbon\Carbon;

class CreatePairsVolumeUpdateCommand extends Command
{
    protected $signature = 'pairs:update';
    protected $description = 'Update active pairs by applying webhook volume and auto-drip logic';

    public function handle()
    {
        $now   = Carbon::now('Asia/Kuala_Lumpur');
        $today = Carbon::today('Asia/Kuala_Lumpur');

        // ✅ STEP 1: Process webhook payments by matching currency
        $webhookPayments = WebhookPayment::where('status', 'Paid')->get();

        foreach ($webhookPayments as $payment) {
            $currency = strtoupper(trim($payment->currency ?? ''));

            if ($currency === 'USD') {
                $pair = Pair::whereDate('created_at', $today)
                    ->inRandomOrder()
                    ->first();
            } else {
                $pair = Pair::whereDate('created_at', $today)
                    ->whereHas('currency', function ($q) use ($currency) {
                        $q->whereRaw('LOWER(c_name) = ?', [strtolower($currency)]);
                    })
                    ->inRandomOrder()
                    ->first();
            }

            if (! $pair) {
                Log::channel('pair')->warning("⚠️ No pair found for currency {$currency} (PayID: {$payment->pay_id})");
                continue;
            }

            $pair->volume += $payment->amount;
            $pair->save();

            $payment->pair_id = $pair->id;
            $payment->status = 'Processed';
            $payment->save();

            Log::channel('pair')->info("💵 Webhook matched: {$payment->amount} from {$payment->pay_id} → Pair #{$pair->id} [{$currency}]");

            broadcast(new OrderUpdated($pair->id, $pair->volume, $pair->volume));
        }

        // ✅ STEP 2: Auto-drip volume logic (merged)
        $settings = Setting::where('status', 1)
            ->where('name', 'special_name')
            ->pluck('value', 'name')
            ->toArray();

        $specialNames = array_filter(explode(',', $settings['special_name'] ?? ''));

        $pairs = Pair::with('currency')->whereDate('created_at', $today)->get();

        foreach ($pairs as $pair) {
            $originalVolume = $pair->volume;

            // ✅ COP-specific volume cap
            if ($pair->currency && $pair->currency->c_name === 'COP') {
                $maxCOPVolume = 164505200;
                if ($pair->volume > $maxCOPVolume) {
                    Log::channel('pair')->info("COP Pair #{$pair->id} volume capped at {$maxCOPVolume} (current: {$pair->volume})");
                    $pair->save();
                    continue;
                }
            }

            // ✅ Special name max volume logic
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

            if ($shouldHaveRun > $alreadyRun) {
                $isFinalDripHour = ($elapsedHrs === $activeHrs);

                if (!$isFinalDripHour && rand(0, 1) === 0) {
                    Log::channel('pair')->info("⏭️ Pair #{$pair->id} skipped randomly.");
                    continue;
                }

                $toDo = $shouldHaveRun - $alreadyRun;
                $initialChunk = $pair->volume / ($alreadyRun + 1);
                $multiplier = rand(80, 120) / 100;
                $chunk = $initialChunk * $multiplier;
                $addition = $chunk * $toDo;

                $pair->volume += $addition;
                $pair->save();

                broadcast(new OrderUpdated($pair->id, $pair->volume, $pair->volume));
                Log::channel('pair')->info("💧 Auto-drip: +{$addition} → Pair #{$pair->id} (toDo: {$toDo}, multiplier: {$multiplier})");
                Log::channel('pair')->info("📦 Final update → Pair #{$pair->id}: New Volume: {$pair->volume}");
            }
        }

        return 0;
    }
}
