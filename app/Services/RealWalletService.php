<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RealWalletService
{
    public function getAvailableFund(int $userId): float
    {
        // Same exclusions used in your CheckRealWalletBalance command
        $excludedIds = array_merge(range(1, 18), range(194, 205));

        // 3a. Affiliates Earning from Payout Type
        $affiliatePayout = DB::table('payouts as p')
            ->leftJoin('orders as o', 'p.order_id', '=', 'o.id')
            ->leftJoin('users as u_order', 'o.user_id', '=', 'u_order.id')
            ->where('p.user_id', $userId)
            ->where('p.status', 1)
            ->where('p.wallet', 'affiliates')
            ->where('p.type', 'payout')
            ->where(function ($sub) use ($excludedIds) {
                $sub->whereNull('p.order_id')
                    ->orWhere(function ($inner) use ($excludedIds) {
                        $inner->whereNotIn('u_order.status', [2, 3])
                              ->whereNotIn('u_order.id', $excludedIds);
                    });
            })
            ->sum('p.actual');

        // 3b. Affiliates Earning from Direct Type
        $affiliateDirect = DB::table('payouts as p')
            ->leftJoin('transfers as t', 'p.order_id', '=', 't.id')
            ->leftJoin('users as u_transfer', 't.user_id', '=', 'u_transfer.id')
            ->where('p.user_id', $userId)
            ->where('p.status', 1)
            ->where('p.wallet', 'affiliates')
            ->where('p.type', 'direct')
            ->whereNotIn('u_transfer.status', [2, 3])
            ->whereNotIn('u_transfer.id', $excludedIds)
            ->sum('p.actual');

        $affiliatesEarning = (float)$affiliatePayout + (float)$affiliateDirect;

        // 5. Total Withdrawal
        $totalWithdrawal = (float) DB::table('withdrawals')
            ->where('user_id', $userId)
            ->where('status', 'Completed')
            ->sum('amount');

        // Available Fund = Total Affiliates Earning - Total Withdrawal
        $availableFund = $affiliatesEarning - $totalWithdrawal;

        // Never below zero
        return max(0.0, $availableFund);
    }
}
