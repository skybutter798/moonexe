<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class CheckRealWalletBalance extends Command
{
    protected $signature = 'check:real-wallet {user_id?}';
    protected $description = 'Check wallet breakdown: deposit, direct earning, affiliate earning';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $excludedIds = array_merge(range(1, 18), range(194, 205));
    
        $users = User::query()
            ->when($userId, function ($q) use ($userId) {
                return $q->where('id', $userId);
            }, function ($q) use ($excludedIds) {
                return $q->whereNotIn('id', $excludedIds)
                         ->where('status', '!=', 2);
            })
            ->get();
    
        foreach ($users as $user) {
            // 1. Total Real Deposit
            $totalDeposit = DB::table('deposits')
                ->where('user_id', $user->id)
                ->where('status', 'Completed')
                ->whereNotNull('external_txid')
                ->sum('amount');
    
            // 2. Total ROI Earning
            $directEarning = DB::table('payouts as p')
                ->leftJoin('orders as o', 'p.order_id', '=', 'o.id')
                ->leftJoin('users as u_order', 'o.user_id', '=', 'u_order.id')
                ->where('p.user_id', $user->id)
                ->where('p.status', 1)
                ->where('p.type', 'payout')
                ->where('p.wallet', 'earning')
                ->where(function ($query) {
                    $query->whereNull('p.order_id')
                          ->orWhere('u_order.status', 1);
                })
                ->sum('p.actual');
    
            // 3a. Affiliates Earning from Payout Type
            $affiliatePayout = DB::table('payouts as p')
                ->leftJoin('orders as o', 'p.order_id', '=', 'o.id')
                ->leftJoin('users as u_order', 'o.user_id', '=', 'u_order.id')
                ->where('p.user_id', $user->id)
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
                ->where('p.user_id', $user->id)
                ->where('p.status', 1)
                ->where('p.wallet', 'affiliates')
                ->where('p.type', 'direct')
                ->whereNotIn('u_transfer.status', [2, 3])
                ->whereNotIn('u_transfer.id', $excludedIds)
                ->sum('p.actual');
            
            $affiliatesEarning = $affiliatePayout + $affiliateDirect;


    
            // 4. Trading Margin Calculation
            $allDownlineIds = $this->getUserTree($user->id);

            // Filter valid downlines for margin calc
            $validDownlineIds = User::whereIn('id', $allDownlineIds)
                ->whereNotIn('id', $excludedIds)
                ->where('status', '!=', 2)
                ->pluck('id')
                ->toArray();

            $tradingIn = DB::table('transfers')
                ->whereIn('user_id', $validDownlineIds)
                ->where('from_wallet', 'cash_wallet')
                ->where('to_wallet', 'trading_wallet')
                ->where('status', 'Completed')
                ->whereRaw("LOWER(remark) = 'package'")
                ->sum('amount');
            
            $tradingOut = DB::table('transfers')
                ->whereIn('user_id', $validDownlineIds)
                ->where('from_wallet', 'trading_wallet')
                ->where('to_wallet', 'cash_wallet')
                ->where('status', 'Completed')
                ->get()
                ->reduce(function ($carry, $transfer) {
                    $fee = 0;
                    if (preg_match('/[\d\.]+/', $transfer->remark, $matches)) {
                        $fee = floatval($matches[0]);
                    }
                    return $carry + $transfer->amount + $fee;
                }, 0);

    
            $netMargin = $tradingIn - $tradingOut;
    
            // Final output
            $this->info("User ID: {$user->id} | Deposit: $totalDeposit | ROI: $directEarning | Payout: $affiliatePayout, Direct: $affiliateDirect | Trading Margin: $netMargin");

        }
    
        $this->info("✅ Breakdown completed.");
    }
    
    
    protected function getUserTree($uplineId)
    {
        $allUsers = User::all(['id', 'referral']);
    
        $downlineIds = [];
        $queue = [$uplineId];
    
        while (!empty($queue)) {
            $parentId = array_shift($queue);
    
            $children = $allUsers->filter(function ($user) use ($parentId) {
                return $user->referral == $parentId;
            });
    
            foreach ($children as $child) {
                $downlineIds[] = $child->id;
                $queue[] = $child->id;
            }
        }
    
        return $downlineIds;
    }

}
