<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class WalletRecalculator
{
    public function recalculate(int $userId): void
    {
        $user = User::find($userId);
        if (!$user || $user->status == 2) return;

        // -------- CASH WALLET --------
        $cash = DB::table('deposits')->where('user_id', $userId)->where('status', 'Completed')->sum('amount')
            - DB::table('withdrawals')->where('user_id', $userId)->where('status', '!=', 'Rejected')->sum('amount')
            + DB::table('transfers')->where('user_id', $userId)->where('status', 'Completed')
                ->whereIn('from_wallet', ['affiliates_wallet', 'earning_wallet'])
                ->where('to_wallet', 'cash_wallet')->sum('amount')
            + DB::table('transfers')->where('user_id', $userId)->where('status', 'Completed')
                ->where('from_wallet', 'trading_wallet')->where('to_wallet', 'cash_wallet')
                ->sum(DB::raw('CAST(remark AS DECIMAL(20,8))'))
            + DB::table('transfers')->where('user_id', $userId)->where('status', 'Completed')
                ->where('from_wallet', 'trading_wallet')->where('to_wallet', 'cash_wallet')->sum('amount')
            - DB::table('transfers')->where('user_id', $userId)->where('status', 'Completed')
                ->where('from_wallet', 'cash_wallet')->where('to_wallet', 'trading_wallet')
                ->where('remark', 'package')->sum('amount')
            + DB::table('transfers')->where('user_id', $userId)->where('status', 'Completed')
                ->where('from_wallet', 'cash_wallet')->where('to_wallet', 'cash_wallet')
                ->where('remark', 'downline')->where('amount', '<', 0)->sum('amount')
            + DB::table('transfers')->where('user_id', $userId)->where('status', 'Completed')
                ->where('from_wallet', 'cash_wallet')->where('to_wallet', 'cash_wallet')
                ->where('remark', 'downline')->where('amount', '>', 0)->sum('amount');

        // -------- TRADING WALLET --------
        $trading = DB::table('transfers')->where('user_id', $userId)->where('status', 'Completed')
                ->where('from_wallet', 'cash_wallet')->where('to_wallet', 'trading_wallet')->sum('amount')
            - DB::table('transfers')->where('user_id', $userId)->where('status', 'Completed')
                ->where('from_wallet', 'trading_wallet')->where('to_wallet', 'cash_wallet')->sum('amount')
            - DB::table('transfers')->where('user_id', $userId)->where('status', 'Completed')
                ->where('from_wallet', 'trading_wallet')->where('to_wallet', 'system')->sum('amount')
            - DB::table('orders')->where('user_id', $userId)->where('status', 'pending')->sum('buy');

        if ($user->status == 0) {
            $trading = 0;
        }

        // -------- EARNING WALLET --------
        $earning = DB::table('payouts')->where('user_id', $userId)->where('status', 1)
                ->where('type', 'payout')->where('wallet', 'earning')->sum('actual')
            - DB::table('transfers')->where('user_id', $userId)->where('status', 'Completed')
                ->where('from_wallet', 'earning_wallet')->where('to_wallet', 'cash_wallet')->sum('amount');

        // -------- AFFILIATES WALLET --------
        $affiliates = DB::table('payouts')->where('user_id', $userId)->where('status', 1)
                ->whereIn('type', ['payout', 'direct'])->where('wallet', 'affiliates')->sum('actual')
            - DB::table('transfers')->where('user_id', $userId)->where('status', 'Completed')
                ->where('from_wallet', 'affiliates_wallet')->where('to_wallet', 'cash_wallet')->sum('amount');

        // -------- BONUS WALLET --------
        $bonus = 0;
        if (!empty($user->bonus)) {
            $promo = DB::table('promotions')->where('code', $user->bonus)->first();
            if ($promo) {
                $base = 100;
                $multiplier = (float) $promo->multiply;
                $depositTotal = DB::table('deposits')->where('user_id', $userId)->where('status', 'Completed')->sum('amount');
                $bonus = $base + ($depositTotal * $multiplier);
            }
        }

        // -------- Update Wallet --------
        $existing = DB::table('wallets')->where('user_id', $userId)->first();
        if (!$existing) return;

        $updates = [
            'cash_wallet'       => round($cash, 4),
            'trading_wallet'    => round($trading, 4),
            'earning_wallet'    => round($earning, 4),
            'affiliates_wallet' => round($affiliates, 4),
            'bonus_wallet'      => round($bonus, 4),
        ];
        
        $existingValues = [
            'cash_wallet'       => round((float)($existing->cash_wallet ?? 0), 4),
            'trading_wallet'    => round((float)($existing->trading_wallet ?? 0), 4),
            'earning_wallet'    => round((float)($existing->earning_wallet ?? 0), 4),
            'affiliates_wallet' => round((float)($existing->affiliates_wallet ?? 0), 4),
            'bonus_wallet'      => round((float)($existing->bonus_wallet ?? 0), 4),
        ];
        
        $diff = [];
        foreach ($updates as $key => $newValue) {
            if ($existingValues[$key] !== $newValue) {
                $diff[$key] = ['old' => $existingValues[$key], 'new' => $newValue];
            }
        }
        
        if (!empty($diff)) {
            $updateData = $updates;
            $updateData['updated_at'] = now();
            
            // Prevent setting negative balances
            $negativeFields = ['cash_wallet', 'trading_wallet', 'earning_wallet', 'affiliates_wallet'];
            foreach ($negativeFields as $field) {
                if (isset($updateData[$field]) && $updateData[$field] < 0) {
                    Log::channel('cronjob')->warning("⚠️ Skipped {$field} update for User ID {$userId} due to negative value: {$updateData[$field]}");
                    unset($updateData[$field]);
                    unset($diff[$field]);
                }
            }


        
            DB::table('wallets')->where('user_id', $userId)->update($updateData);
        
            Log::channel('cronjob')->info("🔄 User ID {$userId} wallet updated.", $diff);
        } else {
            Log::channel('cronjob')->info("✅ User ID {$userId} wallet unchanged.");
        }

    }
}
