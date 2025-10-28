<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class AutoClaimStakingAtNoon extends Command
{
    protected $signature = 'staking:auto-claim-noon {--dry-run}';
    protected $description = 'At 12:00 MYT, auto-claim weekly staking ROI for all users who have profit (last Mon–Sun)';

    public function handle()
    {
        $tz = 'Asia/Kuala_Lumpur';
        $now = Carbon::now($tz);

        // Last Mon–Sun (MYT)
        $thisMonday = $now->copy()->startOfWeek(Carbon::MONDAY);
        $lastMonday = $thisMonday->copy()->subWeek();
        $lastSunday = $thisMonday->copy()->subDay();

        $weekStart = $lastMonday->toDateString();
        $weekEnd   = $lastSunday->toDateString();
        $shortWeekKey = $lastMonday->format('y') . 'W' . $lastMonday->format('W');

        $this->info("Auto-claim scan for {$weekStart} → {$weekEnd} (MYT)");

        /**
         * Find distinct users who:
         *  - earned > 0 total profit in staking_logs within last Mon–Sun
         *  - have NOT already been paid (payouts.txid=ws_YYWww_userId)
         *
         * NOTE: using staking_logs ensures we skip users whose current stake is 0
         */
        $claimableUserIds = DB::table('staking_logs as sl')
            ->select('sl.user_id', DB::raw('SUM(sl.daily_profit) as total_profit'))
            ->whereBetween('sl.stake_date', [$weekStart, $weekEnd])
            ->groupBy('sl.user_id')
            ->havingRaw('SUM(sl.daily_profit) > 0')
            ->pluck('sl.user_id')
            ->filter(function ($uid) use ($shortWeekKey) {
                $txid = "ws_{$shortWeekKey}_{$uid}";
                return !DB::table('payouts')->where('txid', $txid)->exists();
            })
            ->values();

        if ($claimableUserIds->isEmpty()) {
            $this->warn('No users need auto-claim right now.');
            return Command::SUCCESS;
        }

        $this->info("Will trigger auto-claim for {$claimableUserIds->count()} user(s).");

        if ($this->option('dry-run')) {
            foreach ($claimableUserIds as $uid) {
                $txid = "ws_{$shortWeekKey}_{$uid}";
                $this->line("DRY-RUN: would run staking:distribute-user {$uid} (txid={$txid})");
            }
            return Command::SUCCESS;
        }

        foreach ($claimableUserIds as $uid) {
            // Reuse your existing single-user command
            Artisan::call('staking:distribute-user', ['userId' => $uid]);
            $this->line("Triggered claim for user={$uid}");
        }

        $this->info('Done.');
        return Command::SUCCESS;
    }
}
