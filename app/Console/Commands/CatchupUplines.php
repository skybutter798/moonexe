<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Payout;
use App\Models\User;
use App\Services\UplineDistributor;

class CatchupUplines extends Command
{
    protected $signature = 'staking:catchup-uplines {--week=}';
    protected $description = 'Catch up missing upline distribution for already-created payouts';

    public function handle()
    {
        $tz = 'Asia/Kuala_Lumpur';
        $weekOpt = $this->option('week');

        // if --week not provided, default to last full week
        $now        = Carbon::now($tz);
        $thisMonday = $now->copy()->startOfWeek(Carbon::MONDAY);
        $lastMonday = $thisMonday->copy()->subWeek();
        $lastSunday = $thisMonday->copy()->subDay();

        $weekStart = $weekOpt ? Carbon::parse($weekOpt, $tz)->startOfWeek() : $lastMonday;
        $weekEnd   = $weekOpt ? Carbon::parse($weekOpt, $tz)->endOfWeek()   : $lastSunday;

        $this->info("Catching up uplines for payouts {$weekStart->toDateString()} → {$weekEnd->toDateString()}");

        $payouts = Payout::where('type', 'payout')
            ->whereBetween('created_at', [$weekStart, $weekEnd])
            ->get();

        foreach ($payouts as $payout) {
            $user = User::find($payout->user_id);
            if (!$user) {
                Log::channel('payout')->warning("[catchup] user not found for payout {$payout->id}");
                continue;
            }

            try {
                (new UplineDistributor())->distribute($payout, $payout->actual, $user);
                Log::channel('payout')->info("[catchup] upline_done payout_id={$payout->id} user={$user->id} profit={$payout->actual}");
            } catch (\Throwable $e) {
                Log::channel('payout')->error("[catchup] upline_fail payout_id={$payout->id} user={$user->id} err={$e->getMessage()}");
            }
        }

        $this->info("✅ Catch-up uplines done.");
    }
}