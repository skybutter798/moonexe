<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Order;
use App\Models\Staking;
use App\Models\UserRangeOverride; // <-- add
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BatchUserRangeCalculator
{
    protected Collection $wallets;
    protected Collection $orders;
    protected Collection $transfers;
    protected Collection $users;

    protected array $directRangeCache = [];
    protected array $matchingRangeCache = [];
    protected array $computed = [];
    protected array $stakingCache = [];

    // NEW: overrides keyed by user_id
    protected array $overrides = [];

    protected $directRanges;
    protected $matchingRanges;

    public function calculateForTree(User $rootUser): array
    {
        // 1) Load tree
        $allUsers = $this->getUserTree($rootUser);
        $userIds  = $allUsers->pluck('id');

        // 2) Preload static tables/bulk data
        $this->directRanges   = DB::table('directranges')->get()->keyBy('id');   // keyBy id for quick lookup
        $this->matchingRanges = DB::table('matchingranges')->get()->keyBy('id'); // keyBy id for quick lookup

        $this->wallets = Wallet::whereIn('user_id', $userIds)->get()->keyBy('user_id');
        $this->orders  = Order::whereIn('user_id', $userIds)
                              ->where('status', 'pending')
                              ->get()
                              ->groupBy('user_id');

        $this->transfers = DB::table('transfers')
            ->whereIn('user_id', $userIds)
            ->where('status', 'Completed')
            ->whereIn('remark', ['campaign'])
            ->get()
            ->groupBy('user_id');

        $this->users = $allUsers->keyBy('id');

        // 3) Preload overrides for only these userIds
        $now = now();
        UserRangeOverride::whereIn('user_id', $userIds)->get()->each(function ($ov) use ($now) {
            // Only store if currently active (or no window specified)
            $active = true;
            if ($ov->effective_from && $ov->effective_from->isFuture()) $active = false;
            if ($ov->effective_to   && $ov->effective_to->isPast())     $active = false;
            if ($active) {
                $this->overrides[$ov->user_id] = [
                    'direct_range_id'             => $ov->direct_range_id,
                    'matching_range_id'           => $ov->matching_range_id,
                    'direct_percentage_override'  => $ov->direct_percentage_override,
                    'matching_percentage_override'=> $ov->matching_percentage_override,
                ];
            }
        });

        // 4) Compute
        return $this->computeUserGroupTotal($rootUser->id);
    }

    public function computeUserGroupTotal($userId): array
    {
        if (isset($this->computed[$userId])) {
            return $this->computed[$userId];
        }

        $user = $this->users[$userId] ?? null;

        if (!$user || $user->status == 0) {
            return $this->computed[$userId] = [
                'total'               => 0,
                'direct_percentage'   => 0,
                'matching_percentage' => 0,
            ];
        }

        $wallet         = $this->wallets->get($userId);
        $walletBalance  = $wallet?->trading_wallet ?? 0;
        $pendingOrderSum= $this->orders->get($userId)?->sum('buy') ?? 0;

        $campaignTransfers = $this->transfers->get($userId) ?? collect();
        $campaignIn  = $campaignTransfers->where('from_wallet', 'cash_wallet')->where('to_wallet', 'trading_wallet')->sum('amount');
        $campaignOut = $campaignTransfers->where('from_wallet', 'trading_wallet')->where('to_wallet', 'system')->sum('amount');

        // Staking cache
        if (!array_key_exists($userId, $this->stakingCache)) {
            $this->stakingCache[$userId] = Staking::where('user_id', $userId)
                ->orderByDesc('id')
                ->value('balance') ?? 0;
        }
        $stakingBalance = $this->stakingCache[$userId];

        $userTotal  = $walletBalance + $pendingOrderSum + $stakingBalance - ($campaignIn - $campaignOut);
        $groupTotal = $userTotal;

        $downlines = $this->users->where('referral', $userId);
        foreach ($downlines as $downline) {
            $groupTotal += $this->computeUserGroupTotal($downline->id)['total'];
        }

        // ===== APPLY OVERRIDES OR DEFAULT RANGES =====
        [$directPct, $matchingPct] = $this->resolvePercentages($userId, $groupTotal);

        return $this->computed[$userId] = [
            'total'               => $groupTotal,
            'direct_percentage'   => $directPct,
            'matching_percentage' => $matchingPct,
        ];
    }

    /**
     * Decide the final percentages for a given user based on:
     * 1) explicit percentage override (highest priority)
     * 2) range id override (2nd priority)
     * 3) default range lookup by groupTotal (fallback)
     */
    protected function resolvePercentages(int $userId, float $groupTotal): array
    {
        $ov = $this->overrides[$userId] ?? null;

        // 1) Raw percentage overrides win
        if ($ov && (!is_null($ov['direct_percentage_override']) || !is_null($ov['matching_percentage_override']))) {
            $direct   = $ov['direct_percentage_override']   ?? 0;
            $matching = $ov['matching_percentage_override'] ?? 0;
            return [(float)$direct, (float)$matching];
        }

        // 2) Range id overrides
        if ($ov && ($ov['direct_range_id'] || $ov['matching_range_id'])) {
            $directRow   = $ov['direct_range_id']   ? ($this->directRanges[$ov['direct_range_id']]   ?? null) : null;
            $matchingRow = $ov['matching_range_id'] ? ($this->matchingRanges[$ov['matching_range_id']] ?? null) : null;

            $directPct   = $directRow?->percentage ?? null;
            $matchingPct = $matchingRow?->percentage ?? null;

            // If only one is overridden, compute the other via normal range
            if (is_null($directPct)) {
                $defaultDirect = $this->getRangeByValue($groupTotal, $this->directRanges->values(), $this->directRangeCache);
                $directPct = $defaultDirect->percentage ?? 0;
            }
            if (is_null($matchingPct)) {
                $defaultMatching = $this->getRangeByValue($groupTotal, $this->matchingRanges->values(), $this->matchingRangeCache);
                $matchingPct = $defaultMatching->percentage ?? 0;
            }

            return [(float)$directPct, (float)$matchingPct];
        }

        // 3) Defaults via groupTotal → range rows
        $directRow   = $this->getRangeByValue($groupTotal, $this->directRanges->values(), $this->directRangeCache);
        $matchingRow = $this->getRangeByValue($groupTotal, $this->matchingRanges->values(), $this->matchingRangeCache);

        return [
            (float)($directRow->percentage ?? 0),
            (float)($matchingRow->percentage ?? 0),
        ];
    }

    protected function getRangeByValue($value, $collection, &$cache)
    {
        if (isset($cache[$value])) {
            return $cache[$value];
        }

        $range = $collection->first(function ($row) use ($value) {
            return $row->min <= $value && ($row->max === null || $row->max >= $value);
        });

        return $cache[$value] = $range;
    }

    protected function getUserTree(User $rootUser): Collection
    {
        $result = collect([$rootUser]);
        $queue  = [$rootUser->id];

        while ($queue) {
            $batch  = User::whereIn('referral', $queue)->get();
            $result = $result->merge($batch);
            $queue  = $batch->pluck('id')->all();
        }

        return $result;
    }

    public function getDownlineIds(int $userId): array
    {
        return $this->users
            ->filter(fn($u) => $this->isDescendantOf($u, $userId))
            ->pluck('id')
            ->all();
    }

    protected function isDescendantOf($user, $ancestorId): bool
    {
        while ($user && $user->referral) {
            if ((int)$user->referral === (int)$ancestorId) return true;
            $user = $this->users->get($user->referral);
        }
        return false;
    }
}
