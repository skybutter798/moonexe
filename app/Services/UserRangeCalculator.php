<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\Order;
use App\Models\DirectRange;
use App\Models\MatchingRange;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserRangeCalculator
{
    
    public function calculate($user)
    {
        if (!$user || $user->status == 0) {
            return [
                'total' => 0,
                'direct_percentage' => 0,
                'matching_percentage' => 0,
            ];
        }
        
        // Calculate the group total which includes the user and all of their downlines.
        $total = $this->getUserGroupTotal($user);
    
        // Find the matching direct range.
        $directRange = DirectRange::where('min', '<=', $total)
            ->where(function ($query) use ($total) {
                $query->where('max', '>=', $total)
                      ->orWhereNull('max');
            })->first();
    
        // Find the matching profit-sharing (matching) range.
        $matchingRange = MatchingRange::where('min', '<=', $total)
            ->where(function ($query) use ($total) {
                $query->where('max', '>=', $total)
                      ->orWhereNull('max');
            })->first();
    
        $directPercentage = $directRange ? $directRange->percentage : 0;
        $matchingPercentage = $matchingRange ? $matchingRange->percentage : 0;
    
        //Log::info("Range ---> user {$user->id}, {$total}, direct {$directPercentage}, matchin {$matchingPercentage}");
    
        return [
            'total' => $total,
            'direct_percentage' => $directPercentage,
            'matching_percentage' => $matchingPercentage,
        ];
    }
    
    protected function getUserGroupTotal($user)
    {
        $groupTotal = 0;

        // Calculate the user's own totals.
        $wallet = Wallet::where('user_id', $user->id)->first();
        $walletBalance = $wallet ? $wallet->trading_wallet : 0;
        $pendingOrders = Order::where('user_id', $user->id)
            ->where('status', 'pending')
            ->sum('buy');

        $groupTotal += $walletBalance + $pendingOrders;

        // Log basic info for the user.
        //Log::info("UserRangeCalculator: User {$user->id} - Wallet: {$walletBalance}, Pending Orders: {$pendingOrders}");

        // Retrieve downlines and recursively add their totals.
        $downlines = User::where('referral', $user->id)->get();
        foreach ($downlines as $downline) {
            $groupTotal += $this->getUserGroupTotal($downline);
        }

        //Log::info("UserRangeCalculator: Final group total for User {$user->id}: {$groupTotal}");
        return $groupTotal;
    }
}
