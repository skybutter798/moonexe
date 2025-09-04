<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class FullWalletReport extends Command
{
    protected $signature = 'wallets:report {userRange}';
    protected $description = 'Full wallet, ROI, affiliates, open orders, and downline margin report. Does NOT update DB.';

    public function handle()
    {
        $userRangeInput = $this->argument('userRange');
        $ids = array_map('trim', explode(',', $userRangeInput));
        $userIds = count($ids) === 2 && is_numeric($ids[0]) && is_numeric($ids[1])
            ? range((int)$ids[0], (int)$ids[1])
            : $ids;

        $excludedIds = array_merge(range(1, 18), range(194, 205));

        $users = User::whereIn('id', $userIds)
            ->whereNotIn('id', $excludedIds)
            ->where('status', '!=', 2)
            ->get();

        foreach ($users as $user) {
            $uid = $user->id;

            $totalDeposit = DB::table('deposits')->where('user_id', $uid)->where('status', 'Completed')->sum('amount');
            $realDeposit  = DB::table('deposits')->where('user_id', $uid)->where('status', 'Completed')->whereNotNull('external_txid')->sum('amount');
            $totalWithdrawal = DB::table('withdrawals') ->where('user_id', $uid) ->where('status', '!=', 'Rejected') ->select(DB::raw('SUM(amount + fee) as total')) ->value('total');

            $cash = $totalDeposit
                - $totalWithdrawal
                + DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')->whereIn('from_wallet', ['affiliates_wallet', 'earning_wallet'])->where('to_wallet', 'cash_wallet')->sum('amount')
                + DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')->where('from_wallet', 'trading_wallet')->where('to_wallet', 'cash_wallet')->sum(DB::raw('CAST(remark AS DECIMAL(20,8))'))
                + DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')->where('from_wallet', 'trading_wallet')->where('to_wallet', 'cash_wallet')->sum('amount')
                - DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')->where('from_wallet', 'cash_wallet')->where('to_wallet', 'trading_wallet')->where('remark','package')->sum('amount')
                + DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')->where('from_wallet', 'cash_wallet')->where('to_wallet', 'cash_wallet')->where('remark', 'downline')->where('amount', '<', 0)->sum('amount')
                + DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')->where('from_wallet', 'cash_wallet')->where('to_wallet', 'cash_wallet')->where('remark', 'downline')->where('amount', '>', 0)->sum('amount');

            $trading = 
                // inflows into trading
                DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')->where('from_wallet', 'cash_wallet')    ->where('to_wallet', 'trading_wallet')->sum('amount')
              + DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')->where('from_wallet', 'staking_wallet') ->where('to_wallet', 'trading_wallet')->sum('amount') // ← add unstake
            
                // outflows from trading
              - DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')->where('from_wallet', 'trading_wallet')->where('to_wallet', 'cash_wallet')->sum('amount')
              - DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')->where('from_wallet', 'trading_wallet')->where('to_wallet', 'staking_wallet')->sum('amount')
            
                // reserved in open orders (optional, if you treat pending orders as not in free trading wallet)
              - DB::table('orders')->where('user_id', $uid)->where('status', 'pending')->sum('buy');
            
            if ($user->status == 0) $trading = 0;

            $earning = DB::table('payouts')->where('user_id', $uid)->where('status', 1)->where('type', 'payout')->where('wallet', 'earning')->sum('actual')
                - DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')->where('from_wallet', 'earning_wallet')->where('to_wallet', 'cash_wallet')->sum('amount');

            $affiliates = DB::table('payouts')->where('user_id', $uid)->where('status', 1)->whereIn('type', ['payout', 'direct'])->where('wallet', 'affiliates')->sum('actual')
                - DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')->where('from_wallet', 'affiliates_wallet')->where('to_wallet', 'cash_wallet')->sum('amount');

            $bonus = 0;
            if (!empty($user->bonus)) {
                $promo = DB::table('promotions')->where('code', $user->bonus)->first();
                if ($promo) $bonus = 100 + ($totalDeposit * (float) $promo->multiply);
            }

            $openOrders = DB::table('orders')->where('user_id', $uid)->where('status', 'pending')->sum('buy');

            // ✅ Corrected Transfer In/Out with breakdown
            $cashToCashTransfers = DB::table('transfers')
                ->where('user_id', $uid)
                ->where('from_wallet', 'cash_wallet')
                ->where('to_wallet', 'cash_wallet')
                ->whereIn('status', ['Completed', 'Rejected'])
                ->select('amount', 'status')
                ->get();

            $transferInApproved = $cashToCashTransfers->where('amount', '>', 0)->where('status', 'Completed')->sum('amount');
            $transferInRejected = $cashToCashTransfers->where('amount', '>', 0)->where('status', 'Rejected')->sum('amount');
            $transferIn = $transferInApproved + $transferInRejected;

            $transferOut = $cashToCashTransfers->where('amount', '<', 0)->sum('amount'); // Negative value
            $transferOutTotal = abs($transferOut);

            $expectedBalance = $totalDeposit - $totalWithdrawal + $transferIn;
            $actualBalance = $cash + $openOrders;
            $balanceCheck = $actualBalance - $expectedBalance;



            $roiEarning = DB::table('payouts as p')
                ->leftJoin('orders as o', 'p.order_id', '=', 'o.id')
                ->leftJoin('users as u_order', 'o.user_id', '=', 'u_order.id')
                ->where('p.user_id', $uid)
                ->where('p.status', 1)
                ->where('p.type', 'payout')
                ->where('p.wallet', 'earning')
                ->where(function ($q) {
                    $q->whereNull('p.order_id')->orWhere('u_order.status', 1);
                })->sum('p.actual');

            $affiliatePayout = DB::table('payouts as p')
                ->leftJoin('orders as o', 'p.order_id', '=', 'o.id')
                ->leftJoin('users as u_order', 'o.user_id', '=', 'u_order.id')
                ->where('p.user_id', $uid)
                ->where('p.status', 1)
                ->where('p.wallet', 'affiliates')
                ->where('p.type', 'payout')
                ->where(function ($sub) use ($excludedIds) {
                    $sub->whereNull('p.order_id')
                        ->orWhere(function ($inner) use ($excludedIds) {
                            $inner->whereNotIn('u_order.status', [2, 3])
                                  ->whereNotIn('u_order.id', $excludedIds);
                        });
                })->sum('p.actual');

            $affiliateDirect = DB::table('payouts as p')
                ->leftJoin('transfers as t', 'p.order_id', '=', 't.id')
                ->leftJoin('users as u_transfer', 't.user_id', '=', 'u_transfer.id')
                ->where('p.user_id', $uid)
                ->where('p.status', 1)
                ->where('p.wallet', 'affiliates')
                ->where('p.type', 'direct')
                ->whereNotIn('u_transfer.status', [2, 3])
                ->whereNotIn('u_transfer.id', $excludedIds)
                ->sum('p.actual');

            $downlines = $this->getUserTree($uid);
            $validDownlines = User::whereIn('id', $downlines)->whereNotIn('id', $excludedIds)->where('status', '!=', 2)->pluck('id')->toArray();

            $marginIn = DB::table('transfers')->whereIn('user_id', $validDownlines)->where('from_wallet', 'cash_wallet')->where('to_wallet', 'trading_wallet')->where('status', 'Completed')->whereRaw("LOWER(remark) = 'package'")->sum('amount');

            $marginOutCash = DB::table('transfers')
                ->whereIn('user_id', $validDownlines)
                ->where('from_wallet', 'trading_wallet')
                ->where('to_wallet', 'cash_wallet')
                ->where('status', 'Completed')
                ->get()
                ->reduce(function ($carry, $t) {
                    $fee = 0;
                    if (preg_match('/[\d\.]+/', $t->remark, $m)) $fee = floatval($m[0]);
                    return $carry + $t->amount + $fee;
                }, 0);
            
            $marginOutStake = DB::table('transfers')
                ->whereIn('user_id', $validDownlines)
                ->where('from_wallet', 'trading_wallet')
                ->where('to_wallet', 'staking_wallet')
                ->where('status', 'Completed')
                ->sum('amount');
            
            $marginOut = $marginOutCash + $marginOutStake;


            $netMargin = $marginIn - $marginOut;
            
            // Current Staking Balance: latest ledger row balance (running total)
            $latestStakeRow = DB::table('stakings')
                ->where('user_id', $uid)
                ->where('status', 'active')
                ->orderByDesc('id')
                ->first();
            
            $stakingBalance = $latestStakeRow ? (float)$latestStakeRow->balance : 0.0;
            
            // Combined capital view
            $tradingPlusStaking = $trading + $stakingBalance;

$this->line("
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
👤 User ID: $uid
💳 Total Deposit:       $totalDeposit
✅ Real Deposit:        $realDeposit
📈 ROI Earning:         $roiEarning
🧑 Affiliates:          $affiliatePayout + $affiliateDirect
📦 Downline Margin:     $netMargin

💰 Cash Wallet:         $cash
📊 Trading Wallet:      $trading
🪙 Staking Balance:     $stakingBalance
Σ  Trading+Staking:     $tradingPlusStaking
🏆 Earning Wallet:      $earning
🤝 Affiliates Wallet:   $affiliates
🎁 Bonus Wallet:        $bonus
🕹️ Open Orders:         $openOrders

📉 Withdrawal:          $totalWithdrawal
🔁 Transfer Out (−):    $transferOut
🔁 Transfer In  (+):    $transferIn
   ├─ Approved:         $transferInApproved
   └─ Rejected:         $transferInRejected
🧮 Balance Check:
   ├─ Current Balance:  $expectedBalance
   └─ Real Balance:     $actualBalance
   Balance Different:   $balanceCheck

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
");
        }

        $this->info("✅ Full wallet report completed.");
    }

    protected function getUserTree($uplineId)
    {
        $allUsers = User::all(['id', 'referral']);
        $downlineIds = [];
        $queue = [$uplineId];

        while (!empty($queue)) {
            $parentId = array_shift($queue);
            $children = $allUsers->filter(fn($u) => $u->referral == $parentId);
            foreach ($children as $child) {
                $downlineIds[] = $child->id;
                $queue[] = $child->id;
            }
        }

        return $downlineIds;
    }
}
