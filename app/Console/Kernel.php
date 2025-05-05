<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('marketdata:feed')->twiceDaily(8, 20);
        $schedule->command('marketdata:persist')->everyThirtyMinutes();
        $schedule->command('pairs:create')->everyMinute();
        $schedule->command('simulate:fake-user-buy')->everyThirtyMinutes();
        $schedule->command('seed:claim-orders 19 192')->everyThreeHours();
        $schedule->command('seed:admin-orders')->cron('*/2 * * * *');
        $schedule->command('cron:aggregate-matching')->everyFiveMinutes();
        $schedule->command('pairs:update')->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
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
    ];
}
