<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DistributeWeeklyStaking extends Command
{
    protected $signature = 'staking:distribute';
    protected $description = 'Distribute weekly staking profit for the last completed MYT week (Mon 00:00 MYT → Sun 23:59 MYT)';

    public function handle()
    {
        $tz = 'Asia/Kuala_Lumpur';

        $nowMy      = Carbon::now($tz);                            // e.g., Mon 11:00 MYT
        $thisMonday = $nowMy->copy()->startOfWeek(Carbon::MONDAY); // Mon 00:00 MYT
        $lastMonday = $thisMonday->copy()->subWeek();              // previous Mon 00:00 MYT
        $lastSunday = $thisMonday->copy()->subDay();               // previous Sun 00:00 MYT

        $weekStart = $lastMonday->toDateString(); // inclusive
        $weekEnd   = $lastSunday->toDateString(); // inclusive
        $weekKey   = $lastMonday->isoFormat('GGGG-[W]WW'); // e.g. 2025-W36
        $shortWeekKey = $lastMonday->format('y') . 'W' . $lastMonday->format('W'); // e.g. 25W36

        $this->info("Distributing MYT week {$weekKey} :: {$weekStart} → {$weekEnd} (inclusive).");
        Log::channel('payout')->info("[staking:distribute] Begin week={$weekKey} window={$weekStart}→{$weekEnd}");

        $totals = [
            'users_seen'      => 0,
            'users_paid'      => 0,
            'users_skipped_0' => 0, // zero profit
            'users_skipped_i' => 0, // already paid
            'users_skipped_w' => 0, // no wallet
            'aff_runs'        => 0,
        ];

        \App\Models\User::query()->chunkById(500, function ($users) use ($weekStart, $weekEnd, $weekKey, $shortWeekKey, $tz, &$totals) {
            foreach ($users as $user) {
                $totals['users_seen']++;

                // Capture across transaction to decide whether to run uplines.
                $createdPayout = false;
                $createdOrder  = null;
                $totalProfit   = 0.0;

                DB::transaction(function () use ($user, $weekStart, $weekEnd, $weekKey, $shortWeekKey, $tz, &$createdPayout, &$createdOrder, &$totalProfit, &$totals) {

                    // 1) Aggregate weekly profit for this user
                    $totalProfit = (float) \App\Models\StakingLog::where('user_id', $user->id)
                        ->whereBetween('stake_date', [$weekStart, $weekEnd])
                        ->sum('daily_profit');

                    if ($totalProfit <= 0) {
                        $totals['users_skipped_0']++;
                        Log::channel('payout')->info("[staking:distribute] skip user={$user->id} reason=zero_profit total=0");
                        return; // nothing to pay, nothing to distribute
                    }

                    // 2) Idempotency key (user + ISO week) — short form
                    $txid = "ws_{$shortWeekKey}_{$user->id}";

                    // If user weekly payout was already created, skip fully
                    $alreadyPaid = \App\Models\Payout::where([
                        'user_id' => $user->id,
                        'type'    => 'payout',
                        'wallet'  => 'earning',
                        'txid'    => $txid,
                    ])->exists();

                    if ($alreadyPaid) {
                        $totals['users_skipped_i']++;
                        Log::channel('payout')->info("[staking:distribute] skip user={$user->id} reason=already_paid txid={$txid}");
                        return;
                    }

                    // 3) Lock wallet and credit earning wallet
                    $wallet = \App\Models\Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                    if (!$wallet) {
                        $totals['users_skipped_w']++;
                        Log::channel('payout')->warning("[staking:distribute] skip user={$user->id} reason=no_wallet txid={$txid}");
                        return; // user without wallet: skip safely
                    }

                    // 4) Create a minimal synthetic Order for this weekly staking cycle
                    $createdOrder = \App\Models\Order::create([
                        'user_id'    => $user->id,
                        'buy'        => 0,
                        'earning'    => $totalProfit,
                        'status'     => 'completed',
                        'txid'       => $txid, // tie order to the unique week
                        'time'       => 0,
                        'created_at' => Carbon::parse($weekEnd, $tz)->endOfDay($tz),
                        'updated_at' => now($tz),
                    ]);

                    // 5) Credit user weekly earning and write the payout (idempotency anchor)
                    $before = $wallet->earning_wallet ?? 0;
                    $wallet->earning_wallet = $before + $totalProfit;
                    $wallet->save();

                    \App\Models\Payout::create([
                        'order_id' => $createdOrder->id,
                        'user_id'  => $user->id,
                        'total'    => $totalProfit,
                        'actual'   => $totalProfit,
                        'type'     => 'payout',
                        'wallet'   => 'earning',
                        'status'   => 1,
                        'txid'     => $txid,
                    ]);

                    $createdPayout = true; // signal to run uplines after commit
                    $totals['users_paid']++;

                    Log::channel('payout')->info("[staking:distribute] paid user={$user->id} order={$createdOrder->id} txid={$txid} profit={$totalProfit} earning_wallet_before={$before} after={$wallet->earning_wallet}");
                });

                // Outside the transaction: if we just created the payout, now distribute to uplines.
                if ($createdPayout && $createdOrder && $totalProfit > 0) {
                    try {
                        Log::channel('payout')->info("[staking:distribute] upline_start user={$user->id} order={$createdOrder->id} base={$totalProfit}");
                        (new \App\Services\UplineDistributor())->distribute($createdOrder, $totalProfit, $user);
                        $totals['aff_runs']++;
                        Log::channel('payout')->info("[staking:distribute] upline_done user={$user->id} order={$createdOrder->id}");
                    } catch (\Throwable $e) {
                        Log::channel('payout')->error("[staking:distribute] upline_fail user={$user->id} order={$createdOrder->id} err={$e->getMessage()}");
                    }
                }
            }
        });

        Log::channel('payout')->info("[staking:distribute] End week={$weekKey} summary=" . json_encode($totals));
        $this->info('✅ Weekly staking profit distributed for last MYT week (idempotent & uplines processed).');
    }
}
