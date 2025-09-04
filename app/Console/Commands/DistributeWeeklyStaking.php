<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DistributeWeeklyStaking extends Command
{
    protected $signature = 'staking:distribute';
    protected $description = 'Distribute weekly staking profit for the last completed NY week (Mon 00:00 → next Mon 00:00)';

    public function handle()
    {
        $tz = 'America/New_York';

        // Last completed week in NY time
        $nowNy       = Carbon::now($tz);
        $weekEndNy   = $nowNy->copy()->startOfWeek(Carbon::MONDAY); // current Monday 00:00 NY
        $weekStartNy = $weekEndNy->copy()->subWeek();                // previous Monday 00:00 NY

        // For DATE comparisons on staking_logs.stake_date (DATE column)
        $weekStartDate = $weekStartNy->toDateString(); // inclusive
        $weekEndDate   = $weekEndNy->toDateString();   // exclusive

        // For making a stable "week key" (year + ISO week number in NY)
        $weekKey = $weekStartNy->isoFormat('GGGG-[W]WW'); // e.g., 2025-W35

        $this->info("Distributing logs for NY week {$weekKey} :: {$weekStartDate} → {$weekEndDate} (exclusive).");

        \App\Models\User::query()->chunkById(500, function ($users) use ($weekStartDate, $weekEndDate, $weekKey) {
            foreach ($users as $user) {
                DB::transaction(function () use ($user, $weekStartDate, $weekEndDate, $weekKey) {
                    // 1) Sum this user's logs for that exact week window
                    $totalProfit = \App\Models\StakingLog::where('user_id', $user->id)
                        ->where('stake_date', '>=', $weekStartDate)
                        ->where('stake_date', '<',  $weekEndDate)
                        ->sum('daily_profit');

                    if ($totalProfit <= 0) {
                        return; // nothing to distribute
                    }

                    // 2) Idempotency: deterministic weekly txid per user
                    $weekNum = now('America/New_York')->isoWeek;
                    $txid = 's_' . $weekNum . str_pad((string)random_int(0, 9999999), 7, '0', STR_PAD_LEFT);

                    $alreadyPaid = \App\Models\Payout::where('user_id', $user->id)
                        ->where('type', 'payout')
                        ->where('wallet', 'earning')
                        ->where('txid', $txid)
                        ->exists();

                    if ($alreadyPaid) {
                        // This week already distributed for this user
                        return;
                    }

                    // 3) Wallet row
                    $wallet = \App\Models\Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                    if (!$wallet) {
                        return; // no wallet, skip
                    }

                    // 4) Optional order_id: latest staking row id (if exists)
                    $latestStake = \App\Models\Staking::where('user_id', $user->id)
                        ->orderByDesc('id')
                        ->first();
                    $orderIdForPayout = $latestStake?->id;

                    // 5) Credit earning wallet
                    $wallet->earning_wallet = ($wallet->earning_wallet ?? 0) + $totalProfit;
                    $wallet->save();

                    // 6) Create payout record (idempotent via unique txid pattern)
                    \App\Models\Payout::create([
                        'order_id' => $orderIdForPayout,
                        'user_id'  => $user->id,
                        'total'    => $totalProfit,
                        'actual'   => $totalProfit,
                        'type'     => 'payout',
                        'wallet'   => 'earning',
                        'status'   => 1,
                        'txid'     => $txid,
                    ]);
                });
            }
        });

        $this->info('✅ Weekly staking profit distributed (no distributed_at; idempotent via weekly txid).');
    }
}