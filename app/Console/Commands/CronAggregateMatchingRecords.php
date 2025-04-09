<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CronAggregateMatchingRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * This command will be scheduled to run every minute.
     *
     * @var string
     */
    protected $signature = 'cron:aggregate-matching';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for new payouts and trigger aggregation if new records exist';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // 1. Retrieve the record from recordchecks table where name = "record"
        $record = DB::table('recordchecks')->where('name', 'record')->first();

        if (!$record) {
            Log::channel('cronjob')->error("No record found in recordchecks with name 'record'.");
            $this->error("No record found in recordchecks with name 'record'");
            return 1;
        }

        $recordTime = Carbon::parse($record->time);
        

        // 2. Get the latest created_at from the payouts table.
        $latestPayoutCreatedAt = DB::table('payouts')->max('created_at');

        if (!$latestPayoutCreatedAt) {
            Log::channel('cronjob')->info("No payouts found.");
            $this->info("No payouts found.");
            return 0;
        }

        $latestPayoutTime = Carbon::parse($latestPayoutCreatedAt);
        

        // 3. If the latest payout happened after the record reference time, run the aggregation command.
        if ($latestPayoutTime->gt($recordTime)) {
            // Get the latest user id.
            $latestUserId = DB::table('users')->max('id');
            // Define today's date for the aggregation.
            $today = Carbon::today()->toDateString();
            
            Log::channel('cronjob')->info(
                "\n" .
                "====> CronAggregateMatchingRecords started.\n" .
                "Record time: {$recordTime}\n" .
                "Latest payout time: {$latestPayoutTime}\n" .
                "New payouts found. Running aggregate:matching command for users 2 to {$latestUserId} on {$today}"
            );


            // 4. Run the existing aggregate:matching command.
            $exitCode = Artisan::call('aggregate:matching', [
                '--start-user' => 2,
                '--end-user'   => $latestUserId,
                '--start-date' => $today,
                '--end-date'   => $today,
            ]);

            $output = Artisan::output();
            //Log::channel('cronjob')->info("Aggregation output: " . $output);

            // 5. Update the recordchecks time to the latest payout time.
            DB::table('recordchecks')
                ->where('id', $record->id)
                ->update(['time' => $latestPayoutTime->toDateTimeString()]);
        } else {
            //Log::channel('cronjob')->info("No new payouts found since last check.");
            $this->info("No new payouts found since last check.");
        }

        //Log::channel('cronjob')->info("CronAggregateMatchingRecords finished.");

        return 0;
    }
}
