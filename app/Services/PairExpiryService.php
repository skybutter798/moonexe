<?php

namespace App\Services;

use App\Models\Pair;
use App\Models\WebhookPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class PairExpiryService
{
    /**
     * Check a currency pair expiry status.
     *
     * @param  string $currencyCode   e.g. "AUD"
     * @param  float  $probeFiat      optional probe increment (fiat units)
     * @return array{
     *   status: string, // ok | near_expiry | expired | no_pair
     *   reason?: string,
     *   pair_id?: int,
     *   currency?: string,
     *   now_utc?: string,
     *   cutoff_time_utc?: string,
     *   expiry_time_utc?: string,
     *   gate_minutes?: int,
     *   pair_total?: float,
     *   probe_volume?: float,
     *   remaining_minutes?: int
     * }
     */
    public function check(string $currencyCode, float $probeFiat = 0.0): array
    {
        $code = strtoupper(trim($currencyCode));

        // Dynamically choose column(s) that actually exist on currencies table
        $cols = [];
        if (Schema::hasColumn('currencies', 'c_name')) $cols[] = 'c_name';
        if (Schema::hasColumn('currencies', 'name'))   $cols[] = 'name';

        if (empty($cols)) {
            return [
                'status'   => 'no_pair',
                'reason'   => 'No suitable currency column on currencies table',
                'currency' => $code,
            ];
        }

        $pair = Pair::whereHas('currency', function ($q) use ($code, $cols) {
                $q->where(function ($qq) use ($code, $cols) {
                    foreach ($cols as $i => $col) {
                        $method = $i === 0 ? 'whereRaw' : 'orWhereRaw';
                        $qq->{$method}("LOWER($col) = ?", [strtolower($code)]);
                    }
                });
            })
            ->latest('created_at')
            ->first();

        if (!$pair) {
            return [
                'status'   => 'no_pair',
                'reason'   => 'No pair found for currency',
                'currency' => $code,
            ];
        }

        $createdAtUtc = Carbon::parse($pair->created_at)->timezone('UTC');
        $nowUtc       = Carbon::now('UTC');
        $gateMinutes  = (int) $pair->gate_time;

        // Soft cutoff: minimum 5h (300m) else 2h before expiry
        $cutoffMinutes = $gateMinutes < 300 ? 300 : $gateMinutes - 120;

        $cutoffTimeUtc = $createdAtUtc->copy()->addMinutes($cutoffMinutes);
        $expiryTimeUtc = $createdAtUtc->copy()->addMinutes($gateMinutes);

        if ($nowUtc->greaterThanOrEqualTo($expiryTimeUtc)) {
            return [
                'status'            => 'expired',
                'pair_id'           => $pair->id,
                'currency'          => $code,
                'now_utc'           => $nowUtc->toDateTimeString(),
                'cutoff_time_utc'   => $cutoffTimeUtc->toDateTimeString(),
                'expiry_time_utc'   => $expiryTimeUtc->toDateTimeString(),
                'gate_minutes'      => $gateMinutes,
                'remaining_minutes' => 0,
            ];
        }

        if ($nowUtc->greaterThanOrEqualTo($cutoffTimeUtc)) {
            return [
                'status'            => 'near_expiry',
                'pair_id'           => $pair->id,
                'currency'          => $code,
                'now_utc'           => $nowUtc->toDateTimeString(),
                'cutoff_time_utc'   => $cutoffTimeUtc->toDateTimeString(),
                'expiry_time_utc'   => $expiryTimeUtc->toDateTimeString(),
                'gate_minutes'      => $gateMinutes,
                'remaining_minutes' => (int) $nowUtc->diffInMinutes($expiryTimeUtc, false),
            ];
        }

        // Use webhook_payments to total volume for this pair
        $pairTotal   = (float) WebhookPayment::where('pair_id', $pair->id)->sum('amount');
        $probeVolume = $pairTotal + max(0, $probeFiat);

        return [
            'status'            => 'ok',
            'pair_id'           => $pair->id,
            'currency'          => $code,
            'now_utc'           => $nowUtc->toDateTimeString(),
            'cutoff_time_utc'   => $cutoffTimeUtc->toDateTimeString(),
            'expiry_time_utc'   => $expiryTimeUtc->toDateTimeString(),
            'gate_minutes'      => $gateMinutes,
            'pair_total'        => $pairTotal,
            'probe_volume'      => $probeVolume,
            'remaining_minutes' => (int) $nowUtc->diffInMinutes($expiryTimeUtc, false),
        ];
    }
}