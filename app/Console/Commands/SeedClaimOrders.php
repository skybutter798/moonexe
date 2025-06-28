<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Order;
use App\Models\Payout;
use App\Models\Wallet;
use App\Models\Pair;
use App\Services\UserRangeCalculator;
use App\Services\ClaimService;
use App\Services\UplineDistributor;
use Carbon\Carbon;

class SeedClaimOrders extends Command
{
    // Accept two arguments: a starting and an ending user id.
    protected $signature = 'seed:claim-orders {start_user_id} {end_user_id}';
    protected $description = 'Simulate claim process for pending orders for a range of users';

    public function handle()
    {
        $startUserId = (int) $this->argument('start_user_id');
        $endUserId   = (int) $this->argument('end_user_id');

        $this->info("Starting claim process for users {$startUserId} to {$endUserId}...");

        for ($userId = $startUserId; $userId <= $endUserId; $userId++) {
            // Retrieve the user.
            if ($userId === 16) {
                $user = User::find(16);
            } else {
                $user = User::where('id', $userId)->where('status', 2)->first();
            }

            if (!$user) {
                $this->info("User {$userId} not found. Skipping.");
                continue;
            }

            $this->info("Processing claims for User {$user->id}");

            // Retrieve all pending orders for this user.
            $orders = Order::where('user_id', $user->id)
                           ->where('status', 'pending')
                           ->get();

            if ($orders->isEmpty()) {
                $this->info("No pending orders found for User {$user->id}. Skipping.");
                continue;
            }

            // Loop through each pending order.
            foreach ($orders as $order) {
                $pair = Pair::find($order->pair_id);
                if (!$pair) {
                    $this->info("Pair not found for Order ID {$order->id}. Skipping.");
                    continue;
                }
                
                $claimEligibleTime = Carbon::parse($pair->created_at)->addHours($pair->end_time);

                if (Carbon::now()->lt($claimEligibleTime)) {
                    $this->info("Order ID {$order->id} for User {$user->id} is not eligible for claim until {$claimEligibleTime}.");
                    continue;
                }
                
                $this->info("Processing Order ID {$order->id} for User {$user->id}");

                // Calculate user's total and percentages.
                $rangeCalculator = new UserRangeCalculator();
                $userRange = $rangeCalculator->calculate($user);
                $this->info("User {$user->id} - Total: {$userRange['total']}, Direct: {$userRange['direct_percentage']}, Matching: {$userRange['matching_percentage']}");

                // Calculate claim amounts.
                $claimService = new ClaimService();
                $claimAmounts = $claimService->calculate($order);
                $baseClaimAmount = $claimAmounts['base'];

                // Create a payout record.
                Payout::create([
                    'user_id'    => $user->id,
                    'order_id'   => $order->id,
                    'total'      => $order->earning,
                    'txid'       => $order->txid,
                    'actual'     => $baseClaimAmount,
                    'type'       => 'payout',
                    'wallet'     => 'earning',
                    'status'     => 1,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->created_at,
                ]);

                // Update the user's wallet.
                $wallet = Wallet::where('user_id', $user->id)->first();
                if ($wallet) {
                    $wallet->earning_wallet += $baseClaimAmount;
                    $wallet->trading_wallet += $order->buy;
                    $wallet->save();
                }

                // Mark the order as claimed (completed).
                $order->status = 'completed';
                $order->save();

                // Distribute income to the upline.
                $uplineDistributor = new UplineDistributor();
                $uplineDistributor->distribute($order, $baseClaimAmount, $user);

                $this->info("Order ID {$order->id} claim process completed for User {$user->id}");
            }

            $this->info("Claim process completed for User {$user->id}");
        }

        $this->info("Claim process finished for all users in the range.");
    }
}
