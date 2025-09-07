<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DistributeWeeklyStaking extends Command
{
    protected $signature = 'staking:distribute';
    protected $description = 'Distribute weekly staking profit for the last completed MYT week (Mon 00:00 MYT → Sun 23:59 MYT)';

    public function handle()
    {
        $tz = 'Asia/Kuala_Lumpur';

        $nowMy      = Carbon::now($tz);                                  // e.g., Mon 11:00 MYT
        $thisMonday = $nowMy->copy()->startOfWeek(Carbon::MONDAY);        // Mon 00:00 MYT
        $lastMonday = $thisMonday->copy()->subWeek();                     // previous Mon 00:00 MYT
        $lastSunday = $thisMonday->copy()->subDay();                      // previous Sun 00:00 MYT

        $weekStart = $lastMonday->toDateString(); // inclusive
        $weekEnd   = $lastSunday->toDateString(); // inclusive
        $weekKey   = $lastMonday->isoFormat('GGGG-[W]WW');

        $this->info("Distributing MYT week {$weekKey} :: {$weekStart} → {$weekEnd} (inclusive).");

        \App\Models\User::query()->chunkById(500, function ($users) use ($weekStart, $weekEnd, $weekKey) {
            foreach ($users as $user) {
                DB::transaction(function () use ($user, $weekStart, $weekEnd, $weekKey) {
                    $totalProfit = (float) \App\Models\StakingLog::where('user_id', $user->id)
                        ->whereBetween('stake_date', [$weekStart, $weekEnd])
                        ->sum('daily_profit');

                    if ($totalProfit <= 0) return;

                    $txid = "weekly-stake-myt-{$user->id}-{$weekKey}";
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

        $this->info('✅ Weekly staking profit distributed for last MYT week (idempotent).');
    }
}
