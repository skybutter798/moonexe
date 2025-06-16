<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\TelegramService;

class CheckRealWalletBalance extends Command
{
    protected $signature = 'check:real-wallet {user_key?} {--no-telegram}';

    protected $description = 'Check wallet breakdown using user ID or name, and send result to Telegram';

    protected $telegram;

    public function __construct(TelegramService $telegram)
    {
        parent::__construct();
        $this->telegram = $telegram;
    }

    public function handle()
    {
        $userKey = $this->argument('user_key');
        $excludedIds = array_merge(range(1, 18), range(194, 205));

        if ($userKey) {
            $user = is_numeric($userKey)
                ? User::find($userKey)
                : User::where('name', $userKey)->first();

            if (!$user) {
                $this->error("User '{$userKey}' not found.");
                return;
            }

            $users = collect([$user]);
        } else {
            $users = User::whereNotIn('id', $excludedIds)
                ->where('status', '!=', 2)
                ->get();
        }

        foreach ($users as $user) {
            $referrer = User::find($user->referral);
            $referrerDisplay = $referrer
                ? "{$referrer->name} (ID: {$referrer->id})"
                : '—';

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

            // 3b. Affiliates Earning from Direct Type (with breakdown)
            $directAffiliatePayouts = DB::table('payouts as p')
                ->leftJoin('transfers as t', 'p.order_id', '=', 't.id')
                ->leftJoin('users as u_transfer', 't.user_id', '=', 'u_transfer.id')
                ->where('p.user_id', $user->id)
                ->where('p.status', 1)
                ->where('p.wallet', 'affiliates')
                ->where('p.type', 'direct')
                ->whereNotIn('u_transfer.status', [2, 3])
                ->whereNotIn('u_transfer.id', $excludedIds)
                ->select([
                    'p.actual as payout_amount',
                    'p.order_id',
                    't.user_id as downline_id',
                    'u_transfer.name as downline_name'
                ])
                ->get();

            /*$this->info("📋 Direct Affiliate Breakdown for {$user->name} (ID: {$user->id}):");
            foreach ($directAffiliatePayouts as $row) {
                $this->line("→ From Downline {$row->downline_name} (ID: {$row->downline_id}), Transfer ID: {$row->order_id}, Amount: {$row->payout_amount}");
            }*/

            $affiliateDirect = $directAffiliatePayouts->sum('payout_amount');

            $affiliatesEarning = $affiliatePayout + $affiliateDirect;

            // 4. Trading Margin Calculation
            $allDownlineIds = $this->getUserTree($user->id);

            $validDownlineIds = User::whereIn('id', $allDownlineIds)
                ->whereNotIn('id', $excludedIds)
                ->where('status', '!=', 2)
                ->pluck('id')
                ->toArray();

            $tradingIn = DB::table('transfers')
                ->join('users', 'transfers.user_id', '=', 'users.id')
                ->whereIn('transfers.user_id', $validDownlineIds)
                ->where('users.status', 1) // this filters only active users
                ->where('transfers.from_wallet', 'cash_wallet')
                ->where('transfers.to_wallet', 'trading_wallet')
                ->where('transfers.status', 'Completed') // disambiguate this line!
                ->whereRaw("LOWER(transfers.remark) = 'package'")
                ->whereNotIn('transfers.id', [
                    233, 236, 237, 244, 333, 334, 544, 701,
                    790, 797, 360, 361, 383, 1859, 2045, 2150
                ])
                ->sum('transfers.amount');


                
            $tradingInRecords = DB::table('transfers')
                ->whereIn('user_id', $validDownlineIds)
                ->where('from_wallet', 'cash_wallet')
                ->where('to_wallet', 'trading_wallet')
                ->where('status', 'Completed')
                ->whereRaw("LOWER(remark) = 'package'")
                ->whereNotIn('id', [219, 700, 701, 790, 797])
                ->get();
            
            $this->info("📦 Trading-In Transfer Records:");
            foreach ($tradingInRecords as $record) {
                //$this->line("→ ID: {$record->id}, User ID: {$record->user_id}, Amount: {$record->amount}, Remark: {$record->remark}");
            }


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

            // Telegram Message
            $message = <<<EOL
<b>REAL WALLET BREAKDOWN</b>

💼 USER DETAILS
|—— <b>ID:</b> <code>{$user->id}</code>
|—— <b>Name:</b> {$user->name}
|—— <b>Email:</b> {$user->email}
|—— <b>Referred By:</b> {$referrerDisplay}

💰 DEPOSIT & EARNINGS
|—— <b>Real Deposit:</b> {$totalDeposit}
|—— <b>ROI Earnings:</b> {$directEarning}

🤝 AFFILIATE EARNINGS
|—— <b>Matching:</b> {$affiliatePayout}
|—— <b>Direct:</b> {$affiliateDirect}
|—— <b>Total:</b> {$affiliatesEarning}

📦 DOWNLINE MARGIN
|—— <b>Net Trading Margin:</b> {$netMargin}
EOL;

            // Print in terminal
            $this->line($message);
            
            if (!$this->option('no-telegram')) {
                $this->telegram->sendMessage($message, '-4807439791');
            }
            $this->info("✅ Sent breakdown for user {$user->name} (ID: {$user->id}) to Telegram.");
        }

        $this->info("✅ All breakdowns completed and sent.");
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
