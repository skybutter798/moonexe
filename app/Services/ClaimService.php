<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Order;
use App\Models\Transfer;
use App\Models\ParingProfit;
use Illuminate\Support\Facades\Log;

class ClaimService
{
    public function calculate($order)
    {
        // 1. Get the user who owns the order
        $user = User::find($order->user_id);

        if (!$user) {
            Log::channel('payout')->warning("User not found for order ID: {$order->id}");
            return ['base' => 0];
        }

        // 2. Sum all qualifying transfers
        $totalTransferred = Transfer::where('user_id', $user->id)
            ->where('from_wallet', 'cash_wallet')
            ->where('to_wallet', 'trading_wallet')
            ->where('status', 'Completed')
            ->sum('amount');

        Log::channel('payout')->info("Total transferred for user {$user->id}: {$totalTransferred}");

        // 3. Find the applicable paring profit range
        $paring = ParingProfit::where('min', '<=', $totalTransferred)
            ->where('max', '>=', $totalTransferred)
            ->first();

        if (!$paring) {
            $percentage = 50; // fallback
            Log::channel('payout')->warning("No matching paring profit for user {$user->id}, transferred: {$totalTransferred}, using default: {$percentage}%");
        } else {
            $percentage = $paring->percentage;
            Log::channel('payout')->info("Matched paring '{$paring->name}' for user {$user->id} with {$percentage}%");
        }

        // 4. Calculate base claim
        $baseClaim = $order->earning * ($percentage / 100);

        Log::channel('payout')->info("Calculated base claim for order {$order->id}: Earning {$order->earning}, Percentage {$percentage}%, Final Claim: {$baseClaim}");

        return [
            'base' => $baseClaim,
            'percentage' => $percentage,
        ];
    }
}
