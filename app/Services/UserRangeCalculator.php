<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Wallet;
use App\Models\Order;
use App\Models\DirectRange;
use App\Models\MatchingRange;
use App\Models\User;
use App\Models\Staking;
use App\Models\UserRangeOverride;
use Illuminate\Support\Facades\DB;

class UserRangeCalculator
{
    protected $walletCache = [];
    protected $orderSumCache = [];
    protected $campaignBonusInCache = [];
    protected $campaignBonusOutCache = [];
    protected $referralCache = [];
    protected $stakingCache = [];

    public function calculate($user): array
    {
        if (!$user || (int)$user->status === 0) {
            return [
                'total'               => 0,
                'direct_percentage'   => 0,
                'matching_percentage' => 0,
            ];
        }

        $total = $this->getUserGroupTotal($user);

        // ===== Override-aware percentage resolution =====
        [$directPct, $matchingPct] = $this->resolvePercentages((int)$user->id, (float)$total);

        return [
            'total'               => $total,
            'direct_percentage'   => $directPct,
            'matching_percentage' => $matchingPct,
        ];
    }

    /**
     * Resolve final percentages with priority:
     * 1) explicit % overrides (if active)
     * 2) range-id overrides (if active)
     * 3) default range lookup by $total
     */
    protected function resolvePercentages(int $userId, float $total): array
    {
        $now = now();
        $override = UserRangeOverride::where('user_id', $userId)->first();

        $overrideActive = false;
        if ($override) {
            $overrideActive = true;
            if ($override->effective_from && $override->effective_from->isFuture()) {
                $overrideActive = false;
            }
            if ($override->effective_to && $override->effective_to->isPast()) {
                $overrideActive = false;
            }
        }

        // 1) Explicit percentage overrides take precedence
        if ($override && $overrideActive && (
                !is_null($override->direct_percentage_override) ||
                !is_null($override->matching_percentage_override)
            )) {

            // If one side is null, fall back to default lookup for that side.
            $directPct = !is_null($override->direct_percentage_override)
                ? (float)$override->direct_percentage_override
                : $this->lookupDirectRangePct($total);

            $matchingPct = !is_null($override->matching_percentage_override)
                ? (float)$override->matching_percentage_override
                : $this->lookupMatchingRangePct($total);

            return [$directPct, $matchingPct];
        }

        // 2) Range ID overrides (use those ranges where provided; missing side falls back)
        if ($override && $overrideActive && (
                $override->direct_range_id || $override->matching_range_id
            )) {

            $directPct = $override->direct_range_id
                ? (float)optional(DirectRange::find($override->direct_range_id))->percentage
                : $this->lookupDirectRangePct($total);

            $matchingPct = $override->matching_range_id
                ? (float)optional(MatchingRange::find($override->matching_range_id))->percentage
                : $this->lookupMatchingRangePct($total);

            // When find() returns null, optional()->percentage is null; coalesce to default
            if (!$override->direct_range_id || !$directPct) {
                $directPct = $this->lookupDirectRangePct($total);
            }
            if (!$override->matching_range_id || !$matchingPct) {
                $matchingPct = $this->lookupMatchingRangePct($total);
            }

            return [$directPct, $matchingPct];
        }

        // 3) Default: min/max lookup based on $total
        return [
            $this->lookupDirectRangePct($total),
            $this->lookupMatchingRangePct($total),
        ];
    }

    protected function lookupDirectRangePct(float $total): float
    {
        $directRange = DirectRange::where('min', '<=', $total)
            ->where(function ($q) use ($total) {
                $q->where('max', '>=', $total)
                  ->orWhereNull('max');
            })->first();

        return (float)($directRange->percentage ?? 0);
    }

    protected function lookupMatchingRangePct(float $total): float
    {
        $matchingRange = MatchingRange::where('min', '<=', $total)
            ->where(function ($q) use ($total) {
                $q->where('max', '>=', $total)
                  ->orWhereNull('max');
            })->first();

        return (float)($matchingRange->percentage ?? 0);
    }

    protected function getUserGroupTotal($user): float
    {
        $groupTotal = 0.0;
        $userId = (int)$user->id;

        // Wallet cache
        if (!array_key_exists($userId, $this->walletCache)) {
            $this->walletCache[$userId] = Wallet::where('user_id', $userId)->first();
        }
        $wallet = $this->walletCache[$userId];
        $walletBalance = $wallet ? (float)$wallet->trading_wallet : 0.0;

        // Pending orders cache
        if (!array_key_exists($userId, $this->orderSumCache)) {
            $this->orderSumCache[$userId] = (float) Order::where('user_id', $userId)
                ->where('status', 'pending')
                ->sum('buy');
        }
        $pendingOrders = (float)$this->orderSumCache[$userId];

        // Staking balance cache (latest balance)
        if (!array_key_exists($userId, $this->stakingCache)) {
            $this->stakingCache[$userId] = (float) (Staking::where('user_id', $userId)
                ->orderByDesc('id')
                ->value('balance') ?? 0);
        }
        $stakingBalance = (float)$this->stakingCache[$userId];

        $userTotal = $walletBalance + $pendingOrders + $stakingBalance;

        if ((int)$user->status === 1) {
            // Campaign bonus in
            if (!array_key_exists($userId, $this->campaignBonusInCache)) {
                $this->campaignBonusInCache[$userId] = (float) DB::table('transfers')
                    ->where('user_id', $userId)
                    ->where('from_wallet', 'cash_wallet')
                    ->where('to_wallet', 'trading_wallet')
                    ->where('remark', 'campaign')
                    ->where('status', 'Completed')
                    ->sum('amount');
            }

            // Campaign bonus out
            if (!array_key_exists($userId, $this->campaignBonusOutCache)) {
                $this->campaignBonusOutCache[$userId] = (float) DB::table('transfers')
                    ->where('user_id', $userId)
                    ->where('from_wallet', 'trading_wallet')
                    ->where('to_wallet', 'system')
                    ->where('remark', 'campaign')
                    ->where('status', 'Completed')
                    ->sum('amount');
            }

            $in  = (float)$this->campaignBonusInCache[$userId];
            $out = (float)$this->campaignBonusOutCache[$userId];
            $userTotal -= ($in - $out);
        }

        $groupTotal += $userTotal;

        // Downlines cache
        if (!array_key_exists($userId, $this->referralCache)) {
            $this->referralCache[$userId] = User::where('referral', $userId)->get();
        }
        $downlines = $this->referralCache[$userId];

        foreach ($downlines as $downline) {
            $groupTotal += $this->getUserGroupTotal($downline);
        }

        return (float)$groupTotal;
        }
}
