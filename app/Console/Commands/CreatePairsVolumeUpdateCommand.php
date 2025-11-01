<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pair;
use App\Models\Order;
use App\Models\WebhookPayment;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Events\OrderUpdated;
use Carbon\Carbon;

class CreatePairsVolumeUpdateCommand extends Command
{
    protected $signature = 'pairs:update 
        {--pair_volume= : total volume in USD from webhook (optional)} 
        {--currency= : currency code (e.g. USD, HKD, THB)}';

    protected $description = 'Update active pairs by applying webhook volume (if provided) and auto-drip logic';

    public function handle()
    {
        $now   = Carbon::now('Asia/Kuala_Lumpur');
        $today = Carbon::today('Asia/Kuala_Lumpur');

        $pairVolumeUSD = (float) ($this->option('pair_volume') ?? 0);
        $currencyCode  = strtoupper(trim($this->option('currency') ?? ''));

        /**
         * ============================================================
         *  MODE 1: DIRECT UPDATE (called by webhook with --pair_volume)
         * ============================================================
         */
        if ($pairVolumeUSD > 0 && !empty($currencyCode)) {
            Log::channel('pair')->info("[Webhook] Direct update mode detected", [
                'currency' => $currencyCode,
                'pair_volume_usd' => $pairVolumeUSD,
            ]);

            $pair = Pair::whereHas('currency', function ($q) use ($currencyCode) {
                    $q->whereRaw('LOWER(c_name) = ?', [strtolower($currencyCode)]);
                })
                ->latest('created_at')
                ->first();

            if (!$pair) {
                Log::channel('pair')->warning("[Webhook] No pair found for {$currencyCode}");
                return 0;
            }

            $localCurrency = strtoupper($pair->currency->c_name ?? '');
            $finalVolumeLocal = $pairVolumeUSD;

            // 🔄 Convert USD → local currency if needed
            if ($localCurrency !== 'USD') {
                $symbol1 = 'USD' . $localCurrency;
                $symbol2 = $localCurrency . 'USD';

                $rateRow = DB::table('market_data')
                    ->whereIn('symbol', [$symbol1, $symbol2])
                    ->select('symbol', 'mid')
                    ->first();

                if ($rateRow && $rateRow->mid > 0) {
                    $finalVolumeLocal = $rateRow->symbol === $symbol1
                        ? $pairVolumeUSD * $rateRow->mid
                        : $pairVolumeUSD / $rateRow->mid;
                } else {
                    Log::channel('pair')->warning("[Webhook] No FX rate found for {$localCurrency}");
                    return 0;
                }
            }

            // ✅ Replace volume with latest value
            $pair->volume = $finalVolumeLocal;
            $pair->save();

            Log::channel('pair')->info("[Webhook] Pair volume replaced → {$pairVolumeUSD} USD ({$finalVolumeLocal} {$localCurrency}) for Pair #{$pair->id}");

            // 📢 Broadcast updated volume
            $pendingBuyLocal = Order::where('pair_id', $pair->id)
                ->whereNotNull('buy')
                ->where('status', 'pending')
                ->sum('receive');

            $remaining = max($pair->volume - $pendingBuyLocal, 0);
            broadcast(new OrderUpdated($pair->id, $remaining, $pair->volume));

            Log::channel('pair')->info("[Webhook] Broadcast after direct update → Pair #{$pair->id}: Remaining {$remaining}, Total {$pair->volume}");
            return 0;
        }

        /**
         * ============================================================
         *  MODE 2: NORMAL BATCH UPDATE (fallback mode)
         * ============================================================
         */
        $webhookPayments = WebhookPayment::where('status', 'Paid')->get();

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

            if (!$pair) continue;

            $localCurrency = strtoupper($pair->currency->c_name ?? '');
            $convertedAmount = $payment->amount; // USD by default

            // 🔄 USD → local conversion
            if ($localCurrency !== 'USD') {
                $symbol1 = 'USD' . $localCurrency;
                $symbol2 = $localCurrency . 'USD';
                $marketRateRow = DB::table('market_data')
                    ->whereIn('symbol', [$symbol1, $symbol2])
                    ->select('symbol', 'mid')
                    ->first();

                if ($marketRateRow && $marketRateRow->mid > 0) {
                    $convertedAmount = $marketRateRow->symbol === $symbol1
                        ? $payment->amount * $marketRateRow->mid
                        : $payment->amount / $marketRateRow->mid;
                } else {
                    continue;
                }
            }

            // 🔒 Volume cap check
            if ($pair->currency && in_array($pair->currency->c_name, $specialNames) && $specialMaxUSD > 0) {
                $rateRow = DB::table('market_data')
                    ->whereIn('symbol', ['USD' . $localCurrency, $localCurrency . 'USD'])
                    ->select('symbol', 'mid')
                    ->first();

                if ($rateRow && $rateRow->mid) {
                    $isReversed = $rateRow->symbol === 'USD' . $localCurrency;
                    $pairVolumeUSD = $isReversed
                        ? $pair->volume / $rateRow->mid
                        : $pair->volume * $rateRow->mid;

                    if ($pairVolumeUSD > $specialMaxUSD) continue;
                }
            }

            // ✅ Normal add mode
            $pair->volume += $convertedAmount;
            $pair->save();

            $payment->pair_id = $pair->id;
            $payment->status = 'Processed';
            $payment->save();

            Log::channel('pair')->info("[Webhook] Matched payment → {$payment->amount} USD → {$convertedAmount} {$localCurrency} → Pair #{$pair->id}");

            $pendingBuyLocal = Order::where('pair_id', $pair->id)
                ->whereNotNull('buy')
                ->where('status', 'pending')
                ->sum('receive');

            $remaining = max($pair->volume - $pendingBuyLocal, 0);
            broadcast(new OrderUpdated($pair->id, $remaining, $pair->volume));
            Log::channel('pair')->info("[Webhook] Broadcast after webhook → Pair #{$pair->id}: Remaining {$remaining}, Total {$pair->volume}");
        }

        /**
         * ============================================================
         *  STEP 2: AUTO-DRIP LOGIC
         * ============================================================
         */
        $pairs = Pair::with('currency')->whereDate('created_at', $today)->get();

        foreach ($pairs as $pair) {
            $localCurrency = strtoupper($pair->currency->c_name ?? '');
            $originalVolume = $pair->volume;

            if ($pair->currency && in_array($pair->currency->c_name, $specialNames)) {
                if ($specialMaxUSD > 0) {
                    $rateRow = DB::table('market_data')
                        ->whereIn('symbol', ['USD' . $localCurrency, $localCurrency . 'USD'])
                        ->select('symbol', 'mid')
                        ->first();

                    if (!$rateRow || !$rateRow->mid) continue;

                    $isReversed = $rateRow->symbol === 'USD' . $localCurrency;
                    $pairVolumeUSD = $isReversed
                        ? $pair->volume / $rateRow->mid
                        : $pair->volume * $rateRow->mid;

                    if ($pairVolumeUSD > $specialMaxUSD) continue;
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
                if (!$isFinalDripHour && rand(0, 1) === 0) continue;

                $toDo = $shouldHaveRun - $alreadyRun;
                $initialChunk = $pair->volume / ($alreadyRun + 1);
                $multiplier = rand(80, 120) / 100;
                $chunk = $initialChunk * $multiplier;
                $addition = $chunk * $toDo;

                $pair->volume += $addition;
                $pair->save();

                $symbol1 = 'USD' . $localCurrency;
                $symbol2 = $localCurrency . 'USD';
                $marketRateRow = DB::table('market_data')
                    ->whereIn('symbol', [$symbol1, $symbol2])
                    ->select('symbol', 'mid')
                    ->first();

                if (!$marketRateRow || !$marketRateRow->mid) continue;

                $rate = $marketRateRow->mid;
                $isReversed = $marketRateRow->symbol === $symbol1;
                $totalUSDT = $isReversed ? $pair->volume / $rate : $pair->volume * $rate;
                $pendingBuyUSD = Order::where('pair_id', $pair->id)
                    ->whereNotNull('buy')
                    ->where('status', 'pending')
                    ->sum('buy');
                $usedUSDT = $isReversed ? $pendingBuyUSD / $rate : $pendingBuyUSD * $rate;
                $remainingUSDT = max($totalUSDT - $usedUSDT, 0);

                broadcast(new OrderUpdated($pair->id, $remainingUSDT, $totalUSDT));

                Log::channel('pair')->info("[Webhook] Auto-drip +{$addition} → Pair #{$pair->id}");
            }
        }

        return 0;
    }
}
