<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecalculateWallets extends Command
{
    protected $signature = 'wallets:recalculate 
                            {userRange : Comma-separated user ID range or list, e.g. "3,100" or "1,5,7"}';

    protected $description = 'Recalculate and update wallets (cash, trading, earning, affiliates, bonus) with detailed breakdown.';

    public function handle()
    {
        $userRangeInput = $this->argument('userRange');
        $ids = array_map('trim', explode(',', $userRangeInput));
        $userIds = count($ids) === 2 && is_numeric($ids[0]) && is_numeric($ids[1])
            ? range((int)$ids[0], (int)$ids[1])
            : $ids;

        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            if ($user->status == 2) {
                $this->info("⏭️  Skipping user ID: {$user->id} (status = 2)");
                continue;
            }
            $this->info("Recalculating wallets for user ID: {$user->id}");

            // CASH breakdown
            $cashParts = [
                'Deposits' => DB::table('deposits')->where('user_id', $user->id)->where('status', 'Completed')->sum('amount'),
                'Withdrawals' => -DB::table('withdrawals') ->where('user_id', $user->id) ->where('status', '!=', 'Rejected') ->select(DB::raw('SUM(amount + fee) as total')) ->value('total'),
                'Aff/Earn to Cash' => DB::table('transfers')->where('user_id', $user->id)->where('status', 'Completed')->whereIn('from_wallet', ['affiliates_wallet', 'earning_wallet'])->where('to_wallet', 'cash_wallet')->sum('amount'),
                'Trading to Cash (remark)' => DB::table('transfers')->where('user_id', $user->id)->where('status', 'Completed')->where('from_wallet', 'trading_wallet')->where('to_wallet', 'cash_wallet')->sum(DB::raw('CAST(remark AS DECIMAL(20,8))')),
                'Trading to Cash (amount)' => DB::table('transfers')->where('user_id', $user->id)->where('status', 'Completed')->where('from_wallet', 'trading_wallet')->where('to_wallet', 'cash_wallet')->sum('amount'),
                'Cash to Trading (package)' => -DB::table('transfers')->where('user_id', $user->id)->where('status', 'Completed')->where('from_wallet', 'cash_wallet')->where('to_wallet', 'trading_wallet')->where('remark', 'package')->sum('amount'),
                'Downline Sent' => DB::table('transfers')->where('user_id', $user->id)->where('status', 'Completed')->where('from_wallet', 'cash_wallet')->where('to_wallet', 'cash_wallet')->where('remark', 'downline')->where('amount', '<', 0)->sum('amount'),
                'Downline Received' => DB::table('transfers')->where('user_id', $user->id)->where('status', 'Completed')->where('from_wallet', 'cash_wallet')->where('to_wallet', 'cash_wallet')->where('remark', 'downline')->where('amount', '>', 0)->sum('amount'),
            ];
            $cash = array_sum($cashParts);

            // TRADING breakdown
            $tradingParts = [
                'Cash to Trading' => DB::table('transfers')->where('user_id', $user->id)->where('status', 'Completed')->where('from_wallet', 'cash_wallet')->where('to_wallet', 'trading_wallet')->where('remark', 'package')->sum('amount'),
                'Campaign' => DB::table('transfers')->where('user_id', $user->id)->where('status', 'Completed')->where('from_wallet', 'cash_wallet')->where('to_wallet', 'trading_wallet')->where('remark', 'campaign')->sum('amount'),
                'Trading to Cash' => -DB::table('transfers')->where('user_id', $user->id)->where('status', 'Completed')->where('from_wallet', 'trading_wallet')->where('to_wallet', 'cash_wallet')->sum('amount'),
                'Trading to System' => -DB::table('transfers')->where('user_id', $user->id)->where('status', 'Completed')->where('from_wallet', 'trading_wallet')->where('to_wallet', 'system')->sum('amount'),
                'Pending Orders' => -DB::table('orders')->where('user_id', $user->id)->where('status', 'pending')->sum('buy'),
                
            ];
            $trading = array_sum($tradingParts);
            if ($user->status == 0) $trading = 0;

            // EARNING breakdown
            $earningParts = [
                'Payouts' => DB::table('payouts')->where('user_id', $user->id)->where('status', 1)->where('type', 'payout')->where('wallet', 'earning')->sum('actual'),
                'To Cash' => -DB::table('transfers')->where('user_id', $user->id)->where('status', 'Completed')->where('from_wallet', 'earning_wallet')->where('to_wallet', 'cash_wallet')->sum('amount'),
            ];
            $earning = array_sum($earningParts);

            // AFFILIATES breakdown
            $affiliatesParts = [
                'Payouts/Direct' => DB::table('payouts')->where('user_id', $user->id)->where('status', 1)->whereIn('type', ['payout', 'direct'])->where('wallet', 'affiliates')->sum('actual'),
                'To Cash' => -DB::table('transfers')->where('user_id', $user->id)->where('status', 'Completed')->where('from_wallet', 'affiliates_wallet')->where('to_wallet', 'cash_wallet')->sum('amount'),
            ];
            $affiliates = array_sum($affiliatesParts);

            // BONUS breakdown
            $bonus = 0;
            $bonusParts = [];
            if (!empty($user->bonus)) {
                $promo = DB::table('promotions')->where('code', $user->bonus)->first();
                if ($promo) {
                    $base = 100;
                    $multiplier = (float) $promo->multiply;
                    $depositTotal = DB::table('deposits')->where('user_id', $user->id)->where('status', 'Completed')->sum('amount');
                    $bonus = $base + ($depositTotal * $multiplier);
                    $bonusParts = [
                        'Base' => $base,
                        'Multiplier Applied' => $depositTotal * $multiplier,
                    ];
                }
            }

            $existingWallet = DB::table('wallets')->where('user_id', $user->id)->first();
            $oldCash = $existingWallet->cash_wallet ?? 0;
            $oldTrading = $existingWallet->trading_wallet ?? 0;
            $oldEarning = $existingWallet->earning_wallet ?? 0;
            $oldAffiliates = $existingWallet->affiliates_wallet ?? 0;
            $oldBonus = $existingWallet->bonus_wallet ?? 0;

            $updateData = [
                'trading_wallet' => $trading,
                'earning_wallet' => $earning,
                'affiliates_wallet' => $affiliates,
                'bonus_wallet' => $bonus,
                'updated_at' => now(),
            ];
            if ($cash >= 0) {
                $updateData['cash_wallet'] = $cash;
                
                DB::table('wallets')->where('user_id', $user->id)->update($updateData);
            } else {
                $this->warn("⚠️  Skipped updating cash_wallet for user ID {$user->id} (value: $cash)");
                
                Log::channel('cronjob')->warning("Negative cash_wallet detected", [
                    'user_id' => $user->id,
                    'calculated_cash_wallet' => $cash,
                    'cash_parts' => $cashParts,
                    'trading_wallet' => $trading,
                    'trading_parts' => $tradingParts,
                    'earning_wallet' => $earning,
                    'earning_parts' => $earningParts,
                    'affiliates_wallet' => $affiliates,
                    'affiliates_parts' => $affiliatesParts,
                    'bonus_wallet' => $bonus,
                    'bonus_parts' => $bonusParts,
                ]);
            }


            $this->printBreakdown($user->id, $cash, $cashParts, $trading, $tradingParts, $earning, $earningParts, $affiliates, $affiliatesParts, $bonus, $bonusParts);
        }

        $this->info('Wallet recalculation completed.');
        Log::channel('cronjob')->info('Wallet recalculation completed.');
        return 0;
    }

    private function printBreakdown($userId, $cash, $cashParts, $trading, $tradingParts, $earning, $earningParts, $affiliates, $affiliatesParts, $bonus, $bonusParts)
    {
        $this->line("\n📊 Breakdown for User ID: $userId");

        $this->line("🔹 Cash Wallet: $cash");
        foreach ($cashParts as $label => $val) {
            $this->line("   - $label: $val");
        }

        $this->line("🔹 Trading Wallet: $trading");
        foreach ($tradingParts as $label => $val) {
            $this->line("   - $label: $val");
        }

        $this->line("🔹 Earning Wallet: $earning");
        foreach ($earningParts as $label => $val) {
            $this->line("   - $label: $val");
        }

        $this->line("🔹 Affiliates Wallet: $affiliates");
        foreach ($affiliatesParts as $label => $val) {
            $this->line("   - $label: $val");
        }

        $this->line("🔹 Bonus Wallet: $bonus");
        foreach ($bonusParts as $label => $val) {
            $this->line("   - $label: $val");
        }
    }
}
