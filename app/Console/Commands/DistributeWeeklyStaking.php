<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DistributeWeeklyStaking extends Command
{
    protected $signature = 'staking:distribute';
    protected $description = 'Distribute weekly staking profit for the last completed NY week (Mon 00:00 → next Mon 00:00)';

    public function handle()
    {
        $tz = 'America/New_York';

        $nowNy       = Carbon::now($tz);
        $weekEndNy   = $nowNy->copy()->startOfWeek(Carbon::MONDAY); // current Mon 00:00 NY
        $weekStartNy = $weekEndNy->copy()->subWeek();                // previous Mon 00:00 NY

        $weekStartDate = $weekStartNy->toDateString(); // inclusive
        $weekEndDate   = $weekEndNy->toDateString();   // exclusive
        $weekKey       = $weekStartNy->isoFormat('GGGG-[W]WW');

        $this->info("Distributing logs for NY week {$weekKey} :: {$weekStartDate} → {$weekEndDate} (exclusive).");

        \App\Models\User::query()->chunkById(500, function ($users) use ($weekStartDate, $weekEndDate, $weekKey) {
            foreach ($users as $user) {
                DB::transaction(function () use ($user, $weekStartDate, $weekEndDate, $weekKey) {
                    $totalProfit = (float) \App\Models\StakingLog::where('user_id', $user->id)
                        ->where('stake_date', '>=', $weekStartDate)
                        ->where('stake_date', '<',  $weekEndDate)
                        ->sum('daily_profit');

                    if ($totalProfit <= 0) return;

                    $txid = "weekly-stake-{$user->id}-{$weekKey}";

                    $alreadyPaid = \App\Models\Payout::where([
                        'user_id' => $user->id,
                        'type'    => 'payout',
                        'wallet'  => 'earning',
                        'txid'    => $txid,
                    ])->exists();

                    if ($alreadyPaid) return;

                    $wallet = \App\Models\Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                    if (!$wallet) return;

                    $latestStake = \App\Models\Staking::where('user_id', $user->id)->orderByDesc('id')->first();

                    $wallet->earning_wallet = ($wallet->earning_wallet ?? 0) + $totalProfit;
                    $wallet->save();

                    \App\Models\Payout::create([
                        'order_id' => $latestStake?->id,
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

        $this->info('✅ Weekly staking profit distributed (idempotent via weekly txid).');
    }
}
