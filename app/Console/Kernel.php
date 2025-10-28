<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Carbon\Carbon;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('marketdata:feed')->twiceDaily(8, 20);
        $schedule->command('marketdata:persist')->everyThirtyMinutes();
        $schedule->command('pairs:create')->everyMinute();
        $schedule->command('simulate:fake-user-buy')->everyThirtyMinutes();
        $schedule->command('seed:claim-orders 17 630')->hourly();
        $schedule->command('cron:aggregate-matching')->everyFiveMinutes();

        // Staking
        $schedule->command('staking:daily')
            ->dailyAt('12:00')
            ->timezone('Asia/Kuala_Lumpur')
            ->withoutOverlapping();

        //$schedule->command('staking:distribute') ->weeklyOn(1, '11:00') ->timezone('Asia/Kuala_Lumpur') ->withoutOverlapping();

        $schedule->command('staking:release-unstakes')->everyFiveMinutes();
        $yesterday = Carbon::yesterday()->toDateString();
        $schedule->command("record:assets:backfill --userIds=3,800 --start={$yesterday}")
            ->dailyAt('01:00');
        $schedule->command("record:profit:backfill --userIds=3,800 --start={$yesterday}")
            ->dailyAt('02:00');
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }

    protected $commands = [
        \App\Console\Commands\PersistMarketData::class,
        \App\Console\Commands\SeedPairs::class,
        \App\Console\Commands\SeedOrdersForUser::class,
        \App\Console\Commands\SeedUsersTree::class,
        \App\Console\Commands\SeedDeposits::class,
        \App\Console\Commands\SeedBuyPackages::class,
        \App\Console\Commands\SeedClaimOrders::class,
        \App\Console\Commands\BackfillAssetsRecords::class,
        \App\Console\Commands\BackfillProfitRecords::class,
        \App\Console\Commands\AggregateMatchingRecords::class,
        \App\Console\Commands\SeedRandomOrders::class,
        \App\Console\Commands\HistoryClaimOrders::class,
        \App\Console\Commands\RecalculateWallets::class,
        \App\Console\Commands\TelegramOneShot::class,
        \App\Console\Commands\ProcessMegadropBonus::class,
        \App\Console\Commands\CampaignAddToTradingWallet::class,
        \App\Console\Commands\TelegramMessage::class,
        \App\Console\Commands\DistributeUserStaking::class,
        \App\Console\Commands\RepushTerminateNotice::class,
    ];
}
