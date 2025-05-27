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

    protected $description = 'Recalculate and update wallets (cash, trading, earning, affiliates, bonus) with correct formulas.';

    public function handle()
    {
        // Parse argument
        $userRangeInput = $this->argument('userRange');
        $ids = array_map('trim', explode(',', $userRangeInput));
        $userIds = count($ids) === 2 && is_numeric($ids[0]) && is_numeric($ids[1])
            ? range((int)$ids[0], (int)$ids[1])
            : $ids;

        $users = User::whereIn('id', $userIds)
            ->where('status', '!=', 2)
            ->get();

        foreach ($users as $user) {
            $this->info("Recalculating wallets for user ID: {$user->id}");

            /** -------- CASH WALLET -------- */
            $cash = DB::table('deposits')->where('user_id', $user->id)->where('status', 'Completed')->sum('amount')
                - DB::table('withdrawals')->where('user_id', $user->id)->where('status', '!=', 'Rejected')->sum('amount')
                + DB::table('transfers')->where('user_id', $user->id)
                    ->where('status', 'Completed')
                    ->whereIn('from_wallet', ['affiliates_wallet', 'earning_wallet'])
                    ->where('to_wallet', 'cash_wallet')
                    ->sum('amount')
                + DB::table('transfers')->where('user_id', $user->id)
                    ->where('status', 'Completed')
                    ->where('from_wallet', 'trading_wallet')
                    ->where('to_wallet', 'cash_wallet')
                    ->sum(DB::raw('CAST(remark AS DECIMAL(20,8))'))
                + DB::table('transfers')->where('user_id', $user->id)
                    ->where('status', 'Completed')
                    ->where('from_wallet', 'trading_wallet')
                    ->where('to_wallet', 'cash_wallet')
                    ->sum('amount')
                - DB::table('transfers')->where('user_id', $user->id)
                    ->where('status', 'Completed')
                    ->where('from_wallet', 'cash_wallet')
                    ->where('to_wallet', 'trading_wallet')
                    ->where('remark','package')
                    ->sum('amount')
                // Downline sent (already negative in DB, so just sum directly)
                + DB::table('transfers')->where('user_id', $user->id)
                    ->where('status', 'Completed')
                    ->where('from_wallet', 'cash_wallet')
                    ->where('to_wallet', 'cash_wallet')
                    ->where('remark', 'downline')
                    ->where('amount', '<', 0)
                    ->sum('amount')
                
                // Downline received
                + DB::table('transfers')->where('user_id', $user->id)
                    ->where('status', 'Completed')
                    ->where('from_wallet', 'cash_wallet')
                    ->where('to_wallet', 'cash_wallet')
                    ->where('remark', 'downline')
                    ->where('amount', '>', 0)
                    ->sum('amount');


            /** -------- TRADING WALLET -------- */
            $trading = DB::table('transfers')->where('user_id', $user->id)
                ->where('status', 'Completed')
                ->where('from_wallet', 'cash_wallet')
                ->where('to_wallet', 'trading_wallet')
                ->sum('amount')
                
                - DB::table('transfers')->where('user_id', $user->id)
                ->where('status', 'Completed')
                ->where('from_wallet', 'trading_wallet')
                ->where('to_wallet', 'cash_wallet')
                ->sum('amount')
                
                - DB::table('orders')->where('user_id', $user->id)
                ->where('status', 'pending')
                ->sum('buy');
            
            // ✅ Force trading_wallet to 0 if user is inactive (status = 0)
            if ($user->status == 0) {
                $trading = 0;
            }

            /** -------- EARNING WALLET -------- */
            $earning = DB::table('payouts')->where('user_id', $user->id)
                ->where('status', 1)
                ->where('type', 'payout')
                ->where('wallet', 'earning')
                ->sum('actual')
                - DB::table('transfers')->where('user_id', $user->id)
                ->where('status', 'Completed')
                ->where('from_wallet', 'earning_wallet')
                ->where('to_wallet', 'cash_wallet')
                ->sum('amount');

            /** -------- AFFILIATES WALLET -------- */
            $affiliates = DB::table('payouts')->where('user_id', $user->id)
                ->where('status', 1)
                ->whereIn('type', ['payout', 'direct'])
                ->where('wallet', 'affiliates')
                ->sum('actual')
                - DB::table('transfers')->where('user_id', $user->id)
                ->where('status', 'Completed')
                ->where('from_wallet', 'affiliates_wallet')
                ->where('to_wallet', 'cash_wallet')
                ->sum('amount');

            /** -------- BONUS WALLET -------- */
            $bonus = 0;
            if (!empty($user->bonus)) {
                $promo = DB::table('promotions')
                    ->where('code', $user->bonus)
                    ->first();

                if ($promo) {
                    $base = 100;
                    $multiplier = (float) $promo->multiply;
                    $depositTotal = DB::table('deposits')->where('user_id', $user->id)->where('status', 'Completed')->sum('amount');
                    $bonus = $base + ($depositTotal * $multiplier);
                }
            }

            // Previous wallet values
            $existingWallet = DB::table('wallets')->where('user_id', $user->id)->first();
            $oldCash       = $existingWallet->cash_wallet ?? 0;
            $oldTrading    = $existingWallet->trading_wallet ?? 0;
            $oldEarning    = $existingWallet->earning_wallet ?? 0;
            $oldAffiliates = $existingWallet->affiliates_wallet ?? 0;
            $oldBonus      = $existingWallet->bonus_wallet ?? 0;

            // Only update and log if any value has changed
            if (
                round((float)$oldCash, 4) !== round((float)$cash, 4) ||
                round((float)$oldTrading, 4) !== round((float)$trading, 4) ||
                round((float)$oldEarning, 4) !== round((float)$earning, 4) ||
                round((float)$oldAffiliates, 4) !== round((float)$affiliates, 4) ||
                round((float)$oldBonus, 4) !== round((float)$bonus, 4)
            ) {
                $updateData = [
                    'trading_wallet' => $trading,
                    'earning_wallet' => $earning,
                    'affiliates_wallet' => $affiliates,
                    'bonus_wallet' => $bonus,
                    'updated_at' => now(),
                ];
                
                if ($cash >= 0) {
                    $updateData['cash_wallet'] = $cash;
                } else {
                    $this->warn("⚠️  Skipped updating cash_wallet for user ID {$user->id} (value: $cash)");
                }
                
                DB::table('wallets')->where('user_id', $user->id)->update($updateData);

            
                $cashDisplay = $cash >= 0 ? $this->highlight($oldCash) . " ➝ " . $this->highlight($cash) : "[SKIPPED: $cash]";

                $message = "🔄 User ID {$user->id} wallet updated:
                - Cash: $cashDisplay
                - Trading: " . $this->highlight($oldTrading) . " ➝ " . $this->highlight($trading) . "
                - Earning: " . $this->highlight($oldEarning) . " ➝ " . $this->highlight($earning) . "
                - Affiliates: " . $this->highlight($oldAffiliates) . " ➝ " . $this->highlight($affiliates) . "
                - Bonus: " . $this->highlight($oldBonus) . " ➝ " . $this->highlight($bonus);

            
                $this->info($message);
                Log::channel('cronjob')->info($message);
            }


        }

        $this->info('Wallet recalculation completed.');
        Log::channel('cronjob')->info('Wallet recalculation completed.');

        return 0;
    }

    private function highlight($value)
    {
        return $value < 0 ? "❗{$value}" : $value;
    }
}
