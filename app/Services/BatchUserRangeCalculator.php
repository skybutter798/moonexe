<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Staking;

class BatchUserRangeCalculator
{
    protected Collection $wallets;
    protected Collection $orders;
    protected Collection $transfers;
    protected Collection $users;
    protected array $directRangeCache = [];
    protected array $matchingRangeCache = [];
    protected $stakingCache = [];

    protected array $computed = [];

    public function calculateForTree(User $rootUser): array
    {
        // Step 1: Load all downlines
        $allUsers = $this->getUserTree($rootUser);

        // Step 2: Preload all necessary data in bulk
        $userIds = $allUsers->pluck('id');
        
        $this->directRanges = DB::table('directranges')->get();
        $this->matchingRanges = DB::table('matchingranges')->get();


        $this->wallets = Wallet::whereIn('user_id', $userIds)->get()->keyBy('user_id');
        $this->orders = Order::whereIn('user_id', $userIds)->where('status', 'pending')->get()->groupBy('user_id');
        $this->transfers = DB::table('transfers')
            ->whereIn('user_id', $userIds)
            ->where('status', 'Completed')
            ->whereIn('remark', ['campaign'])
            ->get()
            ->groupBy('user_id');

        $this->users = $allUsers->keyBy('id');

        // Step 3: Recursively calculate trading margin
        return $this->computeUserGroupTotal($rootUser->id);
    }

    public function computeUserGroupTotal($userId): array
    {
        if (isset($this->computed[$userId])) {
            return $this->computed[$userId];
        }
    
        $user = $this->users[$userId];
    
        if (!$user || $user->status == 0) {
            return $this->computed[$userId] = [
                'total' => 0,
                'direct_percentage' => 0,
                'matching_percentage' => 0,
            ];
        }
    
        $wallet = $this->wallets->get($userId);
        $walletBalance = $wallet?->trading_wallet ?? 0;
    
        $pendingOrderSum = $this->orders->get($userId)?->sum('buy') ?? 0;
    
        $campaignTransfers = $this->transfers->get($userId) ?? collect();
        $campaignIn = $campaignTransfers->where('from_wallet', 'cash_wallet')->where('to_wallet', 'trading_wallet')->sum('amount');
        $campaignOut = $campaignTransfers->where('from_wallet', 'trading_wallet')->where('to_wallet', 'system')->sum('amount');
        
        
        // Staking balance cache
        if (!array_key_exists($userId, $this->stakingCache)) {
            $this->stakingCache[$userId] = Staking::where('user_id', $userId)
                ->orderByDesc('id')
                ->value('balance'); // only grab balance from latest row
        }
        $stakingBalance = $this->stakingCache[$userId] ?? 0;
        
        $userTotal = $walletBalance + $pendingOrderSum + $stakingBalance - ($campaignIn - $campaignOut);
        $groupTotal = $userTotal;
    
        $downlines = $this->users->where('referral', $userId);
        foreach ($downlines as $downline) {
            $groupTotal += $this->computeUserGroupTotal($downline->id)['total'];
        }

        $directRange = $this->getRange($groupTotal, $this->directRanges, $this->directRangeCache);
        $matchingRange = $this->getRange($groupTotal, $this->matchingRanges, $this->matchingRangeCache);
    
        return $this->computed[$userId] = [
            'total' => $groupTotal,
            'direct_percentage' => $directRange->percentage ?? 0,
            'matching_percentage' => $matchingRange->percentage ?? 0,
        ];
    }

    protected function getUserTree(User $rootUser): Collection
    {
        $result = collect([$rootUser]);
        $queue = [$rootUser->id];

        while ($queue) {
            $batch = User::whereIn('referral', $queue)->get();
            $result = $result->merge($batch);
            $queue = $batch->pluck('id')->all();
        }

        return $result;
    }
    
    protected function getRange($value, $collection, &$cache)
    {
        if (isset($cache[$value])) {
            return $cache[$value];
        }
    
        $range = $collection->first(function ($row) use ($value) {
            return $row->min <= $value && ($row->max === null || $row->max >= $value);
        });
    
        return $cache[$value] = $range;
    }
    
    public function getDownlineIds(int $userId): array
    {
        return $this->users->filter(fn($user) => $this->isDescendantOf($user, $userId))->pluck('id')->all();
    }
    
    protected function isDescendantOf($user, $ancestorId): bool
    {
        while ($user && $user->referral) {
            if ($user->referral == $ancestorId) {
                return true;
            }
            $user = $this->users->get($user->referral);
        }
        return false;
    }


}
