<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class CheckNegativeWallets extends Command
{
    protected $signature = 'wallets:check-negative 
                            {userRange? : Optional range or comma-separated IDs, e.g. "3,100" or "1,5,7"}';

    protected $description = 'Check all users for negative wallet balances without updating the database.';

    public function handle()
    {
        $userRangeInput = $this->argument('userRange');
        $userQuery = User::query();

        if ($userRangeInput) {
            $ids = array_map('trim', explode(',', $userRangeInput));
            $userIds = count($ids) === 2 && is_numeric($ids[0]) && is_numeric($ids[1])
                ? range((int)$ids[0], (int)$ids[1])
                : $ids;
            $userQuery->whereIn('id', $userIds);
        }

        $users = $userQuery->where('status', '!=', 2)->get();

        $this->info("🔍 Checking " . $users->count() . " user wallets...");

        $negatives = [];

        foreach ($users as $user) {
            $cash = $this->calculateCashWallet($user->id);
            $trading = $this->calculateTradingWallet($user->id);
            $earning = $this->calculateEarningWallet($user->id);
            $affiliates = $this->calculateAffiliatesWallet($user->id);

            if ($cash < 0 || $trading < 0 || $earning < 0 || $affiliates < 0) {
                $negatives[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'cash' => $cash,
                    'trading' => $trading,
                    'earning' => $earning,
                    'affiliates' => $affiliates,
                ];

                Log::channel('cronjob')->warning('Negative wallet detected', end($negatives));

                $this->warn("⚠️  User #{$user->id} ({$user->name}) has negative wallet(s): "
                    . "Cash: {$cash}, Trading: {$trading}, Earning: {$earning}, Affiliates: {$affiliates}");
            }
        }

        $this->info('✅ Negative wallet scan completed.');

        if (empty($negatives)) {
            $this->info('No negative wallets found. All good.');
        } else {
            $this->error(count($negatives) . ' users found with negative values.');
        }

        return 0;
    }

    // --- Helper Calculation Methods (same logic as your RecalculateWallets, but read-only) ---

    private function calculateCashWallet($uid)
    {
        $overrideDeposit = $this->getDepositOverride((int) $uid);
    
        $depositPart =
            ($overrideDeposit !== null ? $overrideDeposit : 0)
            +
            DB::table('deposits')
                ->where('user_id', $uid)
                ->where('status', 'Completed')
                ->sum('amount');
    
        $parts = [
            $depositPart,
            -DB::table('withdrawals')->where('user_id', $uid)->where('status', '!=', 'Rejected')
                ->select(DB::raw('SUM(amount + fee) as total'))->value('total'),
            DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')
                ->whereIn('from_wallet', ['affiliates_wallet', 'earning_wallet'])
                ->where('to_wallet', 'cash_wallet')->sum('amount'),
            DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')
                ->where('from_wallet', 'trading_wallet')->where('to_wallet', 'cash_wallet')->sum('amount'),
            -DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')
                ->where('from_wallet', 'cash_wallet')->where('to_wallet', 'trading_wallet')
                ->where('remark', 'package')->sum('amount'),
            DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')
                ->where('from_wallet', 'cash_wallet')->where('to_wallet', 'cash_wallet')
                ->where('remark', 'downline')->where('amount', '>', 0)->sum('amount'),
            DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')
                ->where('from_wallet', 'cash_wallet')->where('to_wallet', 'cash_wallet')
                ->where('remark', 'downline')->where('amount', '<', 0)->sum('amount'),
            DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')
                ->where('from_wallet', 'cash_wallet')->where('to_wallet', 'cash_wallet')
                ->where('remark', 'system')->sum('amount'),
        ];
    
        return array_sum($parts);
    }

    private function calculateTradingWallet($uid)
    {
        $parts = [
            DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')
                ->where('from_wallet', 'cash_wallet')->where('to_wallet', 'trading_wallet')
                ->whereRaw("LOWER(COALESCE(remark,'')) = 'package'")
                ->sum('amount'),
            DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')
                ->where('from_wallet', 'cash_wallet')->where('to_wallet', 'trading_wallet')
                ->whereRaw("LOWER(COALESCE(remark,'')) = 'campaign'")
                ->sum('amount'),
            DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')
                ->where('from_wallet', 'staking_wallet')->where('to_wallet', 'trading_wallet')
                ->sum('amount'),
            -DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')
                ->where('from_wallet', 'trading_wallet')->where('to_wallet', 'cash_wallet')
                ->sum('amount'),
        ];
        return array_sum($parts);
    }

    private function calculateEarningWallet($uid)
    {
        $parts = [
            DB::table('payouts')->where('user_id', $uid)->where('status', 1)
                ->where('type', 'payout')->where('wallet', 'earning')->sum('actual'),
            -DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')
                ->where('from_wallet', 'earning_wallet')->where('to_wallet', 'cash_wallet')->sum('amount'),
        ];
        return array_sum($parts);
    }

    private function calculateAffiliatesWallet($uid)
    {
        $parts = [
            DB::table('payouts')->where('user_id', $uid)->where('status', 1)
                ->whereIn('type', ['payout', 'direct'])->where('wallet', 'affiliates')->sum('actual'),
            -DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')
                ->where('from_wallet', 'affiliates_wallet')->where('to_wallet', 'cash_wallet')->sum('amount'),
        ];
        return array_sum($parts);
    }
    
    private function getDepositOverride(int $userId): ?float
    {
        $raw = env('WALLET_DEPOSIT_OVERRIDES', '');
    
        if (empty($raw)) {
            return null;
        }
    
        $pairs = explode(',', $raw);
        $map = [];
    
        foreach ($pairs as $pair) {
            $pair = trim($pair);
            if ($pair === '') {
                continue;
            }
    
            [$id, $amount] = array_pad(explode(':', $pair), 2, null);
    
            if ($id !== null && $amount !== null && is_numeric($id) && is_numeric($amount)) {
                $map[(int) $id] = (float) $amount;
            }
        }
    
        return $map[$userId] ?? null;
    }
}
