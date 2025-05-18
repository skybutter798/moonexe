<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

class RecalculateWallets extends Command
{
    protected $signature = 'wallets:recalculate 
                            {--userIds= : Comma-separated list of user IDs or a range (e.g. "2,195")}';

    protected $description = 'Recalculate and update wallets (cash, trading, earning, affiliates, bonus) with correct formulas.';

    public function handle()
    {
        $userIdsOption = $this->option('userIds');
        $userIds = [];

        if ($userIdsOption) {
            $ids = array_map('trim', explode(',', $userIdsOption));
            $userIds = count($ids) === 2 ? range((int)$ids[0], (int)$ids[1]) : $ids;
        } else {
            $input = $this->ask('Enter user IDs (comma-separated)');
            $ids = array_map('trim', explode(',', $input));
            $userIds = count($ids) === 2 ? range((int)$ids[0], (int)$ids[1]) : $ids;
        }

        $users = User::whereIn('id', $userIds)
             ->where('status', '!=', 2)
             ->get();


        foreach ($users as $user) {
            $this->info("Recalculating wallets for user ID: {$user->id}");

            /** -------- CASH WALLET -------- */
            $cash = DB::table('deposits')->where('user_id', $user->id)->where('status', 'Completed')->sum('amount')
                  - DB::table('withdrawals')->where('user_id', $user->id)->where('status', 'Completed')->sum('amount')
                  + DB::table('transfers')->where('user_id', $user->id)
                        ->where('status', 'Completed')
                        ->whereIn('from_wallet', ['affiliates_wallet', 'earning_wallet'])
                        ->where('to_wallet', 'cash_wallet')
                        ->sum('amount')
                  + DB::table('transfers')->where('user_id', $user->id)
                        ->where('status', 'Completed')
                        ->where('from_wallet', 'trading_wallet')
                        ->where('to_wallet', 'cash_wallet')
                        ->sum('amount')
                  - DB::table('transfers')->where('user_id', $user->id)
                        ->where('status', 'Completed')
                        ->where('from_wallet', 'cash_wallet')
                        ->where('to_wallet', 'trading_wallet')
                        ->sum('amount')
                  - DB::table('transfers')->where('user_id', $user->id)
                        ->where('status', 'Completed')
                        ->where('from_wallet', 'cash_wallet')
                        ->where('to_wallet', 'cash_wallet')
                        ->where('remark', 'downline')
                        ->where('amount', '<', 0)
                        ->sum('amount')
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
                    //->where('status', 1)
                    ->first();

                if ($promo) {
                    $base = 100;
                    $multiplier = (float) $promo->multiply;
                    $depositTotal = DB::table('deposits')->where('user_id', $user->id)->where('status', 'Completed')->sum('amount');
                    $bonus = $base + ($depositTotal * $multiplier);
                }
            }

            /** -------- UPDATE -------- */
            /*$this->line("Preview for user ID {$user->id}:");
            $this->line("Cash Wallet: $cash");
            $this->line("Trading Wallet: $trading");
            $this->line("Earning Wallet: $earning");
            $this->line("Affiliates Wallet: $affiliates");
            $this->line("Bonus Wallet: $bonus");*/
            
            DB::table('wallets')->where('user_id', $user->id)->update([
                'cash_wallet' => $cash,
                'trading_wallet' => $trading,
                'earning_wallet' => $earning,
                'affiliates_wallet' => $affiliates,
                'bonus_wallet' => $bonus,
                'updated_at' => now(),
            ]);
            
            $message = "✅ Updated user ID {$user->id}: cash=$cash, trading=$trading, earning=$earning, affiliates=$affiliates, bonus=$bonus";
            $this->info($message);
            Log::channel('cronjob')->info($message);

        }

        $this->info('Wallet recalculation completed.');
        Log::channel('cronjob')->info('Wallet recalculation completed.');

        return 0;
    }
}
