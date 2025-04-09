<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProfitRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BackfillProfitRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * --userIds: Comma-separated list of user IDs to process. 
     *            If exactly two IDs are provided, they are treated as a range.
     * --start: Optional start date (format: YYYY-MM-DD) for backfill.
     *
     * @var string
     */
    protected $signature = 'record:profit:backfill 
                            {--userIds= : Comma-separated list of user IDs or a range (e.g. "2,195")}
                            {--start= : Optional start date (YYYY-MM-DD) for backfill}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill daily profit records for specified user IDs from a given start date until today.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Get the user IDs from option or ask if not provided.
        $userIdsOption = $this->option('userIds');
        if ($userIdsOption) {
            $userIdsArray = array_map('trim', explode(',', $userIdsOption));
            if (count($userIdsArray) === 2) {
                $startRange = (int) $userIdsArray[0];
                $endRange   = (int) $userIdsArray[1];
                $userIds = range($startRange, $endRange);
            } else {
                $userIds = $userIdsArray;
            }
        } else {
            $input = $this->ask('Please enter the user IDs (comma separated)');
            $userIdsArray = array_map('trim', explode(',', $input));
            if (count($userIdsArray) === 2) {
                $startRange = (int) $userIdsArray[0];
                $endRange   = (int) $userIdsArray[1];
                $userIds = range($startRange, $endRange);
            } else {
                $userIds = $userIdsArray;
            }
        }

        // Fetch the users based on the IDs provided.
        $users = User::whereIn('id', $userIds)->get();

        if ($users->isEmpty()) {
            $this->error("No users found with the provided IDs.");
            return 1;
        }

        // Set the start date; if not provided, default to today.
        $startInput = $this->option('start');
        $startDate = $startInput ? Carbon::parse($startInput) : Carbon::today();
        $endDate = Carbon::today();

        foreach ($users as $user) {
            $this->info("Processing profit records for user ID: {$user->id}");

            // Iterate over each day from the start date until today.
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $currentDate = $date->toDateString();

                // Calculate profit for the day from completed orders.
                // Sum half of the 'earning' field for orders with status "completed"
                $dailyProfit = DB::table('orders')
                    ->where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->whereDate('created_at', '=', $currentDate)
                    ->sum(DB::raw('earning/2'));

                // Check if a profit record already exists for this user on this record_date.
                $record = ProfitRecord::where('user_id', $user->id)
                    ->where('record_date', $currentDate)
                    ->first();

                if ($record) {
                    // Update the existing record.
                    $record->update(['value' => $dailyProfit]);
                    $this->info("Updated profit record for user ID {$user->id} on {$currentDate}.");
                } else {
                    // Create a new profit record.
                    ProfitRecord::create([
                        'user_id'     => $user->id,
                        'value'       => $dailyProfit,
                        'record_date' => $currentDate,
                    ]);
                    $this->info("Created profit record for user ID {$user->id} on {$currentDate}.");
                }
            }
        }

        $this->info('Backfill of profit records completed successfully.');
        return 0;
    }
}
