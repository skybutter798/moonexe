<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AssetsRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BackfillAssetsRecords extends Command
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
    protected $signature = 'record:assets:backfill 
                            {--userIds= : Comma-separated list of user IDs or a range (e.g. "2,195")}
                            {--start= : Optional start date (YYYY-MM-DD) for backfill}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill daily assets records for specified user IDs from a given start date until today.';

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
            // Remove spaces and split by comma.
            $userIdsArray = array_map('trim', explode(',', $userIdsOption));
            if (count($userIdsArray) === 2) {
                // If two numbers are provided, treat them as a range.
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
            $this->info("Processing user ID: {$user->id}");

            // Iterate over each day from the start date until today.
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $currentDate = $date->toDateString();

                // Calculate Cash Wallet for the day:
                $cashDeposits = DB::table('deposits')
                    ->where('user_id', $user->id)
                    ->where('status', 'Completed')
                    ->whereDate('updated_at', '<=', $currentDate)
                    ->sum('amount');

                $cashWithdrawals = DB::table('withdrawals')
                    ->where('user_id', $user->id)
                    ->where('status', 'Completed')
                    ->whereDate('updated_at', '<=', $currentDate)
                    ->sum('amount');

                $cashWallet = (float)$cashDeposits - (float)$cashWithdrawals;

                // Calculate Trading Wallet Deduction for the day:
                $tradingTransfers = DB::table('transfers')
                    ->where('user_id', $user->id)
                    ->where('status', 'Completed')
                    ->where('from_wallet', 'trading_wallet')
                    ->where('to_wallet', 'cash_wallet')
                    ->whereDate('updated_at', '<=', $currentDate)
                    ->sum('amount');

                $tradingDeduction = (float)$tradingTransfers * 0.20;

                // Calculate Earning Wallet for the day:
                $earningOrders = DB::table('orders')
                    ->where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->whereDate('updated_at', '<=', $currentDate)
                    ->sum(DB::raw('earning/2'));

                // Calculate Affiliate Wallet for the day:
                $affiliatePayouts = DB::table('payouts')
                    ->where('user_id', $user->id)
                    ->where('status', 1)
                    ->whereDate('updated_at', '<=', $currentDate)
                    ->sum('actual');

                // Compute the total balance for the day using the formula.
                $totalBalance = $cashWallet + $affiliatePayouts - $tradingDeduction;

                // Check if an asset record already exists for this user on this record_date.
                $record = AssetsRecord::where('user_id', $user->id)
                    ->where('record_date', $currentDate)
                    ->first();

                if ($record) {
                    // Update the existing record.
                    $record->update(['value' => $totalBalance]);
                    $this->info("Updated record for user ID {$user->id} on {$currentDate}.");
                } else {
                    // Create a new record, explicitly setting the record_date.
                    AssetsRecord::create([
                        'user_id'     => $user->id,
                        'value'       => $totalBalance,
                        'record_date' => $currentDate,
                    ]);
                    $this->info("Created record for user ID {$user->id} on {$currentDate}.");
                }
            }
        }

        $this->info('Backfill of assets records completed successfully.');
        return 0;
    }
}
