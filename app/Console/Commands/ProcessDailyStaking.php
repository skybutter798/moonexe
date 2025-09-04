<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Staking;
use App\Models\StakingLog;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessDailyStaking extends Command
{
    protected $signature = 'staking:daily';
    protected $description = 'Calculate and store accurate daily ROI profit per user (ledger-based)';

    public function handle()
    {
        $today = now()->startOfDay();

        // Weekly ROI settings with safe defaults
        $roi100     = (float) (Setting::where('name', 'staking_roi_100')->value('value')    ?? 0.007);
        $roi1000    = (float) (Setting::where('name', 'staking_roi_1000')->value('value')   ?? 0.0105);
        $roi10000   = (float) (Setting::where('name', 'staking_roi_10000')->value('value')  ?? 0.014);
        $roi100000  = (float) (Setting::where('name', 'staking_roi_100000')->value('value') ?? 0.02);

        User::whereHas('stakings', function ($q) {
            $q->where('status', 'active');
        })
        ->chunkById(500, function ($users) use ($today, $roi100, $roi1000, $roi10000, $roi100000) {
            foreach ($users as $user) {
                $latestStake = Staking::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->orderByDesc('id')
                    ->first();

                $currentBalance = $latestStake ? (float) $latestStake->balance : 0;

                if ($currentBalance <= 0) {
                    $this->warn("⏭️  User {$user->id} has no active staking balance today.");
                    continue;
                }

                // Determine tier
                if ($currentBalance >= 100000) {
                    $dailyRate = $roi100000 / 7.0;
                } elseif ($currentBalance >= 10000) {
                    $dailyRate = $roi10000 / 7.0;
                } elseif ($currentBalance >= 1000) {
                    $dailyRate = $roi1000 / 7.0;
                } elseif ($currentBalance >= 100) {
                    $dailyRate = $roi100 / 7.0;
                } else {
                    $dailyRate = 0.0;
                }

                if ($dailyRate <= 0) {
                    $this->warn("⏭️  User {$user->id} below minimum tier; no profit today.");
                    continue;
                }

                $dailyProfit = round($currentBalance * $dailyRate, 8);

                if ($dailyProfit <= 0) {
                    $this->warn("⏭️  User {$user->id} computed zero profit.");
                    continue;
                }

                // Idempotent write: one log per user + date
                DB::transaction(function () use ($user, $today, $currentBalance, $dailyRate, $dailyProfit) {
                    StakingLog::updateOrCreate(
                        [
                            'user_id'    => $user->id,
                            'stake_date' => $today->toDateString(),
                        ],
                        [
                            'total_balance' => $currentBalance,
                            'daily_roi'     => $dailyRate,
                            'daily_profit'  => $dailyProfit,
                        ]
                    );
                });

                $this->info("✅ User {$user->id} | balance={$currentBalance} | rate={$dailyRate} | profit={$dailyProfit}");
            }
        });

        $this->info('🎯 Daily staking ROI processed (ledger-based).');
    }
}