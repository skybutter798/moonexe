<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Events\CampaignBalanceUpdated;

class CampaignSimulator extends Command
{
    protected $signature = 'campaign:simulate';
    protected $description = 'Simulate campaign deduction with random timing and value';

    public function handle()
    {
        $settings = DB::table('settings')
            ->whereIn('name', [
                'cam_balance', 'cam_min_time', 'cam_max_time',
                'cam_min_buy', 'cam_max_buy', 'cam_next_run'
            ])
            ->get()
            ->keyBy('name');

        $balanceRow = $settings->get('cam_balance');

        if (!$balanceRow || $settings->count() < 6) {
            Log::channel('cronjob')->warning("⚠️ Missing campaign settings.");
            return;
        }

        $lastRunAt  = Carbon::parse($balanceRow->updated_at);
        $now        = now();
        $nextRunSec = intval($settings->get('cam_next_run')->value);

        if ($now->lt($lastRunAt->copy()->addSeconds($nextRunSec))) {
            Log::channel('cronjob')->info("⏳ Not yet time. Next run in {$nextRunSec}s after {$lastRunAt}.");
            return;
        }

        // Random deduction value: 100, 200... within buy range
        $minBuy = intval($settings->get('cam_min_buy')->value);
        $maxBuy = intval($settings->get('cam_max_buy')->value);
        $buyOptions = range($minBuy, $maxBuy, 100);
        $buy = $buyOptions[array_rand($buyOptions)];
        $deduct = $buy * 1;

        $currentBalance = intval($balanceRow->value);
        $newBalance = max(0, $currentBalance - $deduct);

        // Update balance and updated_at
        DB::table('settings')->where('name', 'cam_balance')->update([
            'value'      => $newBalance,
            'updated_at' => $now
        ]);

        // Update next run delay
        $minTime = intval($settings->get('cam_min_time')->value);
        $maxTime = intval($settings->get('cam_max_time')->value);
        $nextDelay = rand($minTime, $maxTime);

        DB::table('settings')->where('name', 'cam_next_run')->update([
            'value' => $nextDelay
        ]);

        // 🔔 Broadcast new balance
        event(new CampaignBalanceUpdated($newBalance));

        Log::channel('cronjob')->info("✅ Deducted $deduct. New cam_balance: $newBalance. Next run in $nextDelay sec.");
    }
}
