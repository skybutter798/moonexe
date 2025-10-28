<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\StakingLog;
use App\Models\Payout;
use App\Models\Wallet;
use App\Models\User;
use App\Services\UplineDistributor;

class DistributeUserStaking extends Command
{
    protected $signature = 'staking:distribute-user {userId}';
    protected $description = 'Distribute staking profit for a specific user (last Mon–Sun MYT)';

    public function handle()
    {
        $userId = $this->argument('userId');
        $tz = 'Asia/Kuala_Lumpur';

        $now        = Carbon::now($tz);
        $thisMonday = $now->copy()->startOfWeek(Carbon::MONDAY);
        $lastMonday = $thisMonday->copy()->subWeek();
        $lastSunday = $thisMonday->copy()->subDay();

        $weekStart = $lastMonday->toDateString();
        $weekEnd   = $lastSunday->toDateString();
        $shortWeekKey = $lastMonday->format('y') . 'W' . $lastMonday->format('W');

        $this->info("Distribute staking profits for user={$userId} {$weekStart} → {$weekEnd}");

        $row = StakingLog::select(
                'user_id',
                DB::raw('SUM(daily_profit) as total_profit'),
                DB::raw('MAX(id) as last_log_id')
            )
            ->where('user_id', $userId)
            ->whereBetween('stake_date', [$weekStart, $weekEnd])
            ->groupBy('user_id')
            ->having('total_profit', '>', 0)
            ->first();

        if (!$row) {
            $this->warn("No profit found for user={$userId}");
            return;
        }

        $totalProfit = (float) $row->total_profit;
        $lastLogId   = $row->last_log_id;
        $txid        = "ws_{$shortWeekKey}_{$userId}";

        if (Payout::where('txid', $txid)->exists()) {
            $this->warn("Already paid user={$userId} txid={$txid}");
            return;
        }

        $payout = DB::transaction(function () use ($userId, $totalProfit, $txid, $lastLogId) {
            $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();
            if (!$wallet) {
                Log::channel('payout')->warning("[staking:distribute-user] no_wallet user={$userId}");
                return null;
            }

            $wallet->earning_wallet += $totalProfit;
            $wallet->save();

            $payout = Payout::create([
                'user_id'  => $userId,
                'order_id' => $lastLogId,
                'total'    => $totalProfit,
                'actual'   => $totalProfit,
                'type'     => 'payout',
                'wallet'   => 'earning',
                'status'   => 1,
                'txid'     => $txid,
            ]);

            Log::channel('payout')->info("[staking:distribute-user] paid user={$userId} txid={$txid} order_id={$lastLogId} profit={$totalProfit}");

            return $payout;
        });

        if ($payout) {
            try {
                $user = User::find($userId);
                (new UplineDistributor())->distribute($payout, $totalProfit, $user);
                Log::channel('payout')->info("[staking:distribute-user] upline_done user={$userId} profit={$totalProfit}");
            } catch (\Throwable $e) {
                Log::channel('payout')->error("[staking:distribute-user] upline_fail user={$userId} err={$e->getMessage()}");
            }
        }

        $this->info("✅ Weekly staking distribution completed for user={$userId} {$weekStart} → {$weekEnd}");
    }
}
