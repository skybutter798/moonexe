<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payout;
use App\Models\MatchingRecord;
use App\Models\User;
use Carbon\Carbon;
use DB;

class AggregateMatchingRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Options:
     *   --date: The date for which to aggregate records (YYYY-MM-DD). Defaults to yesterday.
     *   --user: The main user id for which to aggregate matching records.
     *   --start-date and --end-date: (Optional) Specify a date range.
     *   --start-user and --end-user: (Optional) Specify a range of user ids.
     *
     * @var string
     */
    protected $signature = 'aggregate:matching 
                            {--date= : The date for which to aggregate records (YYYY-MM-DD)}
                            {--user= : The main user id for which to aggregate matching records}
                            {--start-date= : The start date for a range of aggregation (YYYY-MM-DD)}
                            {--end-date= : The end date for a range of aggregation (YYYY-MM-DD)}
                            {--start-user= : The starting user id for a range}
                            {--end-user= : The ending user id for a range}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate matching payout records into the matchingrecords table for a given date or date range and user or user range, grouped by first-level referral. Also record extra data from direct payouts (referral breakdown).';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Check if a date range is provided.
        $startDateOption = $this->option('start-date');
        $endDateOption   = $this->option('end-date');
        
        if ($startDateOption && $endDateOption) {
            $startDate = Carbon::parse($startDateOption);
            $endDate   = Carbon::parse($endDateOption);
        } else {
            // Fallback to single date option (default to yesterday).
            $date = $this->option('date') ?: Carbon::yesterday()->toDateString();
            $startDate = Carbon::parse($date);
            $endDate   = Carbon::parse($date);
        }

        // Check if a user range is provided.
        $startUserOption = $this->option('start-user');
        $endUserOption   = $this->option('end-user');
        
        if ($startUserOption && $endUserOption) {
            $userIds = range($startUserOption, $endUserOption);
        } else {
            // Fallback to single user option.
            $user = $this->option('user');
            if (!$user) {
                $this->error("You must provide either a single --user or both --start-user and --end-user.");
                return 1;
            }
            $userIds = [$user];
        }

        // Loop over dates
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $currentDate = $date->toDateString();
            // Loop over each user
            foreach ($userIds as $mainUserId) {
                $this->info("Aggregating for main user {$mainUserId} on {$currentDate}");
                
                

                DB::beginTransaction();
                try {
                    /*
                     * 1. Aggregate matching payouts (type 'payout') for the main user.
                     *    Group by first-level referral determined via the order relationship.
                     */
                    $matchingPayouts = Payout::with('order')
                                ->where('user_id', $mainUserId)
                                ->whereDate('created_at', $currentDate)
                                ->where('type', 'payout')
                                ->where('wallet', 'affiliates')
                                ->where('status', 1)
                                ->get();

                    $matchingGrouped = []; // referralId => sum of matching payout amounts
                    $matchingCount   = []; // referralId => count of matching transactions

                    foreach ($matchingPayouts as $payout) {
                        $order = $payout->order;
                        if (!$order) {
                            continue;
                        }
                        // Determine the first-level referral for the order's user.
                        $topReferral = $this->findTopReferral($order->user_id, $mainUserId);
                        if (!$topReferral) {
                            continue;
                        }
                        $referralId = $topReferral->id;
                        if (!isset($matchingGrouped[$referralId])) {
                            $matchingGrouped[$referralId] = 0;
                            $matchingCount[$referralId]   = 0;
                        }
                        $matchingGrouped[$referralId] += $payout->actual;
                        $matchingCount[$referralId]++;
                    }

                    /*
                     * 2. Aggregate direct payouts (type 'direct') for the main user.
                     *    These are used to build the referral breakdown.
                     */
                    $directPayouts = Payout::with('transfer')
                                ->where('user_id', $mainUserId)
                                ->whereDate('created_at', $currentDate)
                                ->where('type', 'direct')
                                ->where('wallet', 'affiliates')
                                ->where('status', 1)
                                ->get();

                    $directGrouped = []; // referralId => ['total' => sum of direct amounts, 'deposit_count' => count of direct transactions]
                    foreach ($directPayouts as $payout) {
                        $transfer = $payout->transfer;
                        if (!$transfer) {
                            continue;
                        }
                        // Use the transfer to find the first-level referral.
                        $topReferral = $this->findTopReferral($transfer->user_id, $mainUserId);
                        if (!$topReferral) {
                            continue;
                        }
                        $referralId = $topReferral->id;
                        if (!isset($directGrouped[$referralId])) {
                            $directGrouped[$referralId] = ['total' => 0, 'deposit_count' => 0];
                        }
                        $directGrouped[$referralId]['total'] += $payout->actual;
                        $directGrouped[$referralId]['deposit_count']++;
                    }

                    /*
                     * 3. Merge the two groupings.
                     */
                    $allReferralIds = array_unique(array_merge(array_keys($matchingGrouped), array_keys($directGrouped)));

                    foreach ($allReferralIds as $referralId) {
                        $matchingTotal    = $matchingGrouped[$referralId] ?? 0;
                        $matchingCountVal = $matchingCount[$referralId] ?? 0;
                        $directTotal      = $directGrouped[$referralId]['total'] ?? 0;
                        $depositCount     = $directGrouped[$referralId]['deposit_count'] ?? 0;

                        // Fetch the last record for this main user and referral group, if any,
                        // to get the previous cumulative balances.
                        $lastRecord = MatchingRecord::where('user_id', $mainUserId)
                                        ->where('referral_group', $referralId)
                                        ->orderBy('record_date', 'desc')
                                        ->first();

                        $previousMatchingBalance = $lastRecord ? $lastRecord->matching_balance : 0;
                        $previousRefBalance      = $lastRecord ? $lastRecord->ref_balance : 0;

                        $matchingBalance = $previousMatchingBalance + $matchingTotal;
                        $refBalance      = $previousRefBalance + $directTotal;

                        // Insert aggregated data into the matchingrecords table.
                        MatchingRecord::updateOrCreate(
                            [
                                'user_id'        => $mainUserId,
                                'referral_group' => $referralId,
                                'record_date'    => $currentDate,
                            ],
                            [
                                'total_contribute'     => $matchingTotal,
                                'total_trade'          => $matchingCountVal,
                                'total_ref_contribute' => $directTotal,
                                'total_deposit'        => $depositCount,
                                'matching_balance'     => $matchingBalance,
                                'ref_balance'          => $refBalance,
                            ]
                        );
                    }

                    DB::commit();
                    $this->info("  Aggregation for main user {$mainUserId} on {$currentDate} completed successfully.");
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("  Aggregation for main user {$mainUserId} on {$currentDate} failed: " . $e->getMessage());
                }
            }
        }

        return 0;
    }

    /**
     * Find the first-level referral for a given user in the referral chain.
     *
     * @param int $userId The starting user id (for example, the order's user id or transfer's user id).
     * @param int $mainUserId The main user id (the root of the referral chain).
     * @return \App\Models\User|null
     */
    private function findTopReferral($userId, $mainUserId)
    {
        $user = User::find($userId);
        if (!$user) {
            return null;
        }
        if ($user->referral == $mainUserId) {
            return $user;
        }
        while ($user && $user->referral != $mainUserId) {
            $user = User::find($user->referral);
        }
        return $user;
    }
}
