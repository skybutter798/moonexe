<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\View;

class WalletBreakdownService
{
    public static function generate($userId)
    {
        $user = User::findOrFail($userId);

        // 1. USDT Wallet Records (Completed deposits with external_txid)
        $usdt = DB::table('deposits')
            ->where('user_id', $userId)
            ->where('status', 'Completed')
            ->whereNotNull('external_txid')
            ->get();

        // 2. Trading Margin Transfers
        $downlineIds = self::getDownlines($userId);
        $trading = DB::table('transfers')
            ->where('user_id', $userId)
            ->where('to_wallet', 'trading_wallet')
            ->where('status', 'Completed')
            ->get();


        // 3. Earning ROI (payouts)
        $roi = DB::table('payouts')
            ->where('user_id', $userId)
            ->where('wallet', 'earning')
            ->where('status', 1)
            ->get();

        // 4. Affiliate Payouts
        $affiliate = DB::table('payouts')
            ->where('user_id', $userId)
            ->where('wallet', 'affiliates')
            ->where('status', 1)
            ->get();

        return [
            'usdt'     => View::make('components.wallet.usdt', compact('usdt'))->render(),
            'trading'  => View::make('components.wallet.trading', compact('trading'))->render(),
            'earning'  => View::make('components.wallet.earning', compact('roi'))->render(),
            'affiliate'=> View::make('components.wallet.affiliate', compact('affiliate'))->render(),
        ];
    }

    protected static function getDownlines($uplineId)
    {
        $allUsers = User::all(['id', 'referral']);
        $queue = [$uplineId];
        $downlines = [];

        while (!empty($queue)) {
            $current = array_shift($queue);
            $children = $allUsers->where('referral', $current);
            foreach ($children as $child) {
                $downlines[] = $child->id;
                $queue[] = $child->id;
            }
        }

        return $downlines;
    }
}