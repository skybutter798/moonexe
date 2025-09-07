<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\Order;
use App\Models\DirectRange;
use App\Models\MatchingRange;
use App\Models\User;
use App\Models\Staking;
use Illuminate\Support\Facades\DB;

class UserRangeCalculator
{
    protected $walletCache = [];
    protected $orderSumCache = [];
    protected $campaignBonusInCache = [];
    protected $campaignBonusOutCache = [];
    protected $referralCache = [];
    protected $stakingCache = [];

    public function calculate($user)
    {
        if (!$user || $user->status == 0) {
            return [
                'total' => 0,
                'direct_percentage' => 0,
                'matching_percentage' => 0,
            ];
        }

        $total = $this->getUserGroupTotal($user);

        $directRange = DirectRange::where('min', '<=', $total)
            ->where(function ($query) use ($total) {
                $query->where('max', '>=', $total)
                      ->orWhereNull('max');
            })->first();

        $matchingRange = MatchingRange::where('min', '<=', $total)
            ->where(function ($query) use ($total) {
                $query->where('max', '>=', $total)
                      ->orWhereNull('max');
            })->first();

        return [
            'total' => $total,
            'direct_percentage' => $directRange->percentage ?? 0,
            'matching_percentage' => $matchingRange->percentage ?? 0,
        ];
    }

    protected function getUserGroupTotal($user)
    {
        $groupTotal = 0;
        $userId = $user->id;

        // Wallet cache
        if (!array_key_exists($userId, $this->walletCache)) {
            $this->walletCache[$userId] = Wallet::where('user_id', $userId)->first();
        }
        $wallet = $this->walletCache[$userId];
        $walletBalance = $wallet ? $wallet->trading_wallet : 0;

        // Pending orders cache
        if (!array_key_exists($userId, $this->orderSumCache)) {
            $this->orderSumCache[$userId] = Order::where('user_id', $userId)
                ->where('status', 'pending')
                ->sum('buy');
        }
        $pendingOrders = $this->orderSumCache[$userId];

        // Staking balance cache
        if (!array_key_exists($userId, $this->stakingCache)) {
            $this->stakingCache[$userId] = Staking::where('user_id', $userId)
                ->orderByDesc('id')
                ->value('balance'); // only grab balance from latest row
        }
        $stakingBalance = $this->stakingCache[$userId] ?? 0;
        
        $userTotal = $walletBalance + $pendingOrders + $stakingBalance;

        if ($user->status == 1) {
            // Campaign bonus in
            if (!array_key_exists($userId, $this->campaignBonusInCache)) {
                $this->campaignBonusInCache[$userId] = DB::table('transfers')
                    ->where('user_id', $userId)
                    ->where('from_wallet', 'cash_wallet')
                    ->where('to_wallet', 'trading_wallet')
                    ->where('remark', 'campaign')
                    ->where('status', 'Completed')
                    ->sum('amount');
            }

            // Campaign bonus out
            if (!array_key_exists($userId, $this->campaignBonusOutCache)) {
                $this->campaignBonusOutCache[$userId] = DB::table('transfers')
                    ->where('user_id', $userId)
                    ->where('from_wallet', 'trading_wallet')
                    ->where('to_wallet', 'system')
                    ->where('remark', 'campaign')
                    ->where('status', 'Completed')
                    ->sum('amount');
            }

            $in = $this->campaignBonusInCache[$userId];
            $out = $this->campaignBonusOutCache[$userId];
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

        return $groupTotal;
    }
}
