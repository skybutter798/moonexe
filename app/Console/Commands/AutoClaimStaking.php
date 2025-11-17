<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Payout;
use App\Jobs\DistributeUserStakingJob;

class AutoClaimStaking extends Command
{
    protected $signature = 'staking:auto-claim {userId?}';
    protected $description = 'Auto-claim weekly staking ROI for eligible users.';

    public function handle()
    {
        if (!(bool) env('AUTO_CLAIM_ENABLED', true)) {
            $this->info('AUTO_CLAIM_ENABLED=false, exiting.');
            return Command::SUCCESS;
        }

        $tz  = 'Asia/Kuala_Lumpur';
        $now = Carbon::now($tz);

        // ---------------- CLAIM WINDOW: this week Mon 12:00 → Sun 12:00 (same idea as Dashboard) ----------------
        $defaultHour     = 12;
        $weekMondayStart = $now->copy()->startOfWeek(Carbon::MONDAY)->setTime($defaultHour, 0, 0);
        $weekSundayEnd   = $now->copy()->startOfWeek(Carbon::MONDAY)->addDays(6)->setTime($defaultHour, 0, 0);

        $isInClaimWindow = $now->greaterThanOrEqualTo($weekMondayStart)
            && $now->lessThanOrEqualTo($weekSundayEnd);

        if (!$isInClaimWindow) {
            $this->info('Outside claim window, exiting.');
            return Command::SUCCESS;
        }

        // ---------------- LAST WEEK RANGE (same as Dashboard) ----------------
        $lastMonday = $now->copy()->startOfWeek(Carbon::MONDAY)->subWeek();
        $lastSunday = $lastMonday->copy()->endOfWeek(Carbon::SUNDAY);

        $userIdArg = $this->argument('userId');

        if ($userIdArg) {
            $users = User::where('id', $userIdArg)->get();
        } else {
            // Only users who actually have staking_logs in last week
            $users = User::whereExists(function ($q) use ($lastMonday, $lastSunday) {
                $q->select(DB::raw(1))
                    ->from('staking_logs')
                    ->whereColumn('staking_logs.user_id', 'users.id')
                    ->whereBetween('staking_logs.created_at', [$lastMonday, $lastSunday]);
            })->get();
        }

        $this->info('Checking ' . $users->count() . ' users for auto-claim...');

        // Same shortWeekKey & txid pattern as DashboardController
        $shortWeekKey = $lastMonday->format('y') . 'W' . $lastMonday->format('W');

        foreach ($users as $user) {
            $userId = $user->id;
            $txid   = "ws_{$shortWeekKey}_{$userId}";

            // Total ROI for last week
            $claimableWeekROI = DB::table('staking_logs')
                ->where('user_id', $userId)
                ->whereBetween('created_at', [$lastMonday, $lastSunday])
                ->sum('daily_profit');

            if ($claimableWeekROI <= 0) {
                continue;
            }

            // 1) DB check: already claimed?
            $alreadyClaimed = Payout::where('txid', $txid)->exists();
            if ($alreadyClaimed) {
                $this->line("User #{$userId}: already claimed ({$txid}), skip.");
                continue;
            }

            // 2) Cache guard: same key as in DashboardController
            $cacheKey = "autoclaim:{$userId}:{$txid}";
            if (!Cache::add($cacheKey, true, now()->addMinutes(15))) {
                $this->line("User #{$userId}: cache guard hit (probably claimed via dashboard), skip.");
                continue;
            }

            // 3) Double-check race before dispatch
            if (Payout::where('txid', $txid)->exists()) {
                $this->line("User #{$userId}: payout just created, skip dispatch.");
                continue;
            }

            DistributeUserStakingJob::dispatch($userId);

            Log::info('Auto-claim dispatched (command)', [
                'user_id' => $userId,
                'txid'    => $txid,
                'source'  => 'staking:auto-claim',
            ]);

            $this->info("User #{$userId}: dispatched auto-claim ({$txid})");
        }

        $this->info('Auto-claim run completed.');
        return Command::SUCCESS;
    }
}