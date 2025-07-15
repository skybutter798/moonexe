<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pair;
use App\Models\Order;
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

        // Load special cap settings
        $settings = Setting::where('status', 1)
            ->whereIn('name', ['special_name', 'special_max_vol'])
            ->pluck('value', 'name')
            ->toArray();
        $specialNames = array_filter(explode(',', $settings['special_name'] ?? ''));
        $specialMaxUSD = (float) ($settings['special_max_vol'] ?? 0);

        foreach ($webhookPayments as $payment) {
            $currency = strtoupper(trim($payment->currency ?? ''));

            $pair = Pair::whereHas('currency', function ($q) use ($currency) {
                $q->whereRaw('LOWER(c_name) = ?', [strtolower($currency)]);
            })
            ->latest('created_at')
            ->first();


            if (! $pair) {
                Log::channel('pair')->warning("⚠️ No pair found for currency {$currency} (PayID: {$payment->pay_id})");
                continue;
            }

            $localCurrency = strtoupper($pair->currency->c_name ?? '');
            $convertedAmount = $payment->amount; // USD

            if ($localCurrency !== 'USD') {
                $symbol1 = 'USD' . $localCurrency;
                $symbol2 = $localCurrency . 'USD';

                $marketRateRow = \DB::table('market_data')
                    ->whereIn('symbol', [$symbol1, $symbol2])
                    ->select('symbol', 'mid')
                    ->first();

                if ($marketRateRow) {
                    $convertedAmount = $marketRateRow->symbol === $symbol1
                        ? $payment->amount * $marketRateRow->mid
                        : $payment->amount / $marketRateRow->mid;
                } else {
                    Log::channel('pair')->warning("⚠️ No market_data rate found for USD to {$localCurrency} (PayID: {$payment->pay_id})");
                    continue;
                }
            }

            // 🔒 Volume cap check (Webhook)
            if ($pair->currency && in_array($pair->currency->c_name, $specialNames) && $specialMaxUSD > 0) {
                $rateRow = \DB::table('market_data')
                    ->whereIn('symbol', ['USD' . $localCurrency, $localCurrency . 'USD'])
                    ->select('symbol', 'mid')
                    ->first();

                if ($rateRow && $rateRow->mid) {
                    $isReversed = $rateRow->symbol === 'USD' . $localCurrency;
                    $pairVolumeUSD = $isReversed
                        ? $pair->volume / $rateRow->mid
                        : $pair->volume * $rateRow->mid;

                    if ($pairVolumeUSD > $specialMaxUSD) {
                        Log::channel('pair')->info("⛔ Webhook skipped: {$pair->currency->c_name} Pair #{$pair->id} volume capped at {$specialMaxUSD} USD (current: {$pairVolumeUSD} USD)");
                        continue;
                    }
                }
            }

            $pair->volume += $convertedAmount;
            $pair->save();

            $payment->pair_id = $pair->id;
            $payment->status = 'Processed';
            $payment->save();

            Log::channel('pair')->info("💵 Webhook matched: {$payment->amount} USD → {$convertedAmount} {$localCurrency} → Pair #{$pair->id}");

            $pendingBuyLocal = Order::where('pair_id', $pair->id)
                ->whereNotNull('buy')
                ->where('status', 'pending')
                ->sum('receive');

            $remaining = max($pair->volume - $pendingBuyLocal, 0);
            broadcast(new OrderUpdated($pair->id, $remaining, $pair->volume));
            Log::channel('pair')->info("📣 Broadcast after webhook → Pair #{$pair->id}: Remaining {$remaining}, Total {$pair->volume}");
        }

        // ✅ STEP 2: Auto-drip logic
        $pairs = Pair::with('currency')->whereDate('created_at', $today)->get();

        foreach ($pairs as $pair) {
            $originalVolume = $pair->volume;

            if ($pair->currency && in_array($pair->currency->c_name, $specialNames)) {
                if ($specialMaxUSD > 0) {
                    $localCurrency = strtoupper($pair->currency->c_name ?? '');
                    $rateRow = \DB::table('market_data')
                        ->whereIn('symbol', ['USD' . $localCurrency, $localCurrency . 'USD'])
                        ->select('symbol', 'mid')
                        ->first();

                    if (!$rateRow || !$rateRow->mid) {
                        Log::channel('pair')->warning("⚠️ No market_data rate for {$localCurrency} to convert for special_max_vol check.");
                        continue;
                    }

                    $isReversed = $rateRow->symbol === 'USD' . $localCurrency;
                    $pairVolumeUSD = $isReversed
                        ? $pair->volume / $rateRow->mid
                        : $pair->volume * $rateRow->mid;

                    if ($pairVolumeUSD > $specialMaxUSD) {
                        Log::channel('pair')->info("⛔ {$pair->currency->c_name} Pair #{$pair->id} volume capped at {$specialMaxUSD} USD (current: {$pairVolumeUSD} USD)");
                        continue;
                    }
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

                $localCurrency = strtoupper($pair->currency->c_name ?? '');
                $pendingBuyUSD = Order::where('pair_id', $pair->id)
                    ->whereNotNull('buy')
                    ->where('status', 'pending')
                    ->sum('buy');

                $pendingBuyLocal = $pendingBuyUSD;

                if ($localCurrency !== 'USD') {
                    $symbol1 = 'USD' . $localCurrency;
                    $symbol2 = $localCurrency . 'USD';

                    $marketRateRow = \DB::table('market_data')
                        ->whereIn('symbol', [$symbol1, $symbol2])
                        ->select('symbol', 'mid')
                        ->first();

                    if ($marketRateRow) {
                        $pendingBuyLocal = $marketRateRow->symbol === $symbol1
                            ? $pendingBuyUSD * $marketRateRow->mid
                            : $pendingBuyUSD / $marketRateRow->mid;
                    } else {
                        Log::channel('pair')->warning("⚠️ No market_data rate for pending buy conversion for {$localCurrency} → skipping broadcast.");
                        $pendingBuyLocal = 0;
                    }
                }

                $marketRateRow = \DB::table('market_data')
                    ->whereIn('symbol', [$symbol1, $symbol2])
                    ->select('symbol', 'mid')
                    ->first();

                if (!$marketRateRow || !$marketRateRow->mid) {
                    Log::channel('pair')->warning("⚠️ No valid rate for converting {$localCurrency} to USD during webhook update.");
                    return;
                }

                $rate = $marketRateRow->mid;
                $isReversed = $marketRateRow->symbol === $symbol1;
                $totalUSDT = $isReversed ? $pair->volume / $rate : $pair->volume * $rate;
                $usedUSDT = $isReversed ? $pendingBuyLocal / $rate : $pendingBuyLocal * $rate;
                $remainingUSDT = max($totalUSDT - $usedUSDT, 0);

                broadcast(new OrderUpdated($pair->id, $remainingUSDT, $totalUSDT));
                Log::channel('pair')->info("📣 Broadcast after drip → Pair #{$pair->id}: Remaining {$remainingUSDT}, Total {$totalUSDT}");
                Log::channel('pair')->info("💧 Auto-drip: +{$addition} → Pair #{$pair->id} (toDo: {$toDo}, multiplier: {$multiplier})");
                Log::channel('pair')->info("📦 Final update → Pair #{$pair->id}: New Volume: {$pair->volume}");
            }
        }

        return 0;
    }
}
