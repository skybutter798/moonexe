<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Pair;
use App\Models\Order;
use App\Models\MarketData;
use Carbon\Carbon;
use Log;
use App\Events\OrderUpdated;

class SimulateFakeUserBuyOrders extends Command
{
    protected $signature = 'simulate:fake-user-buy';
    protected $description = 'Simulate fake user buy orders for users with status = 2';

    public function handle()
    {
        // Retrieve all users with status = 2, excluding id 16
        $users = User::where('status', 2)
            ->where('id', '!=', 16)
            ->get();

        if ($users->isEmpty()) {
            $this->info("No users with status 2 found.");
            Log::channel('order')->info("No users with status 2 found.");
            return;
        }

        // Randomly select between 8 and 30 users (capped by available users)
        $numberToProcess = min($users->count(), rand(8, 30));

        // Pick random users directly
        $selectedUsers = $users->shuffle()->take($numberToProcess);

        foreach ($selectedUsers as $user) {
            $message = "--------> Processing fake orders for user id: {$user->id}";
            $this->info($message);
            log::channel('order')->info($message);


            // Retrieve the user's wallet
            $wallet = Wallet::where('user_id', $user->id)->first();
            if (!$wallet) {
                $message = "Wallet not found for user id: {$user->id}. Skipping.";
                $this->warn($message);
                Log::channel('order')->warning($message);
                continue;
            }

            if ($wallet->trading_wallet <= 0) {
                $message = "User id {$user->id} has insufficient trading wallet funds. Skipping.";
                $this->warn($message);
                Log::channel('order')->warning($message);
                continue;
            }

            // Retrieve pairs that have not expired
            $availablePairs = Pair::all()->filter(function ($pair) {
                $expirationTime = Carbon::parse($pair->created_at)->addMinutes($pair->gate_time);
                return Carbon::now()->lte($expirationTime);
            });

            if ($availablePairs->isEmpty()) {
                $message = "No available pairs found for user id {$user->id}.";
                $this->warn($message);
                Log::channel('order')->warning($message);
                continue;
            }

            // Loop through each available pair
            foreach ($availablePairs->shuffle() as $pair) {
                // Retrieve market data for the pair's currency
                $currency = $pair->currency;
                $marketData = MarketData::where('symbol', $currency->c_name . 'USD')
                    ->orWhere('symbol', 'USD' . $currency->c_name)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if (!$marketData || !isset($marketData->mid)) {
                    $message = "No valid market data for pair id {$pair->id} (currency: {$currency->c_name}). Skipping.";
                    $this->warn($message);
                    Log::channel('order')->warning($message);
                    continue;
                }

                // Calculate remaining asset volume
                $sumOrdersReceive = Order::where('pair_id', $pair->id)->sum('receive');
                $remainingAssetVolume = $pair->volume - $sumOrdersReceive;

                if ($remainingAssetVolume <= 0) {
                    $message = "Pair id {$pair->id} has no remaining asset volume. Skipping.";
                    $this->info($message);
                    continue;
                }

                // Convert remaining asset volume to USD using the market mid rate
                if (strpos($marketData->symbol, 'USD') === 0) {
                    $remainingUSDVolume = round($remainingAssetVolume / $marketData->mid, 2);
                } else {
                    $remainingUSDVolume = round($remainingAssetVolume * $marketData->mid, 2);
                }

                if ($remainingUSDVolume <= 0) {
                    $message = "Pair id {$pair->id} has no remaining USD volume after conversion. Skipping.";
                    $this->info($message);
                    continue;
                }

                // Determine the order amount
                $orderAmount = min($wallet->trading_wallet, $remainingUSDVolume);

                // Ensure order meets minimum
                if ($orderAmount < 10) {
                    $message = "Calculated order amount is below the minimum for pair id {$pair->id}. Skipping.";
                    $this->info($message);
                    continue;
                }

                $existingOrder = Order::where('pair_id', $pair->id)->first();
                $randomDelta = mt_rand(1, 4) / 100;
                $shouldAdd = mt_rand(0, 1) == 1;

                $est_rate = ($existingOrder && $existingOrder->est_rate !== null)
                    ? $existingOrder->est_rate
                    : ($shouldAdd ? $pair->rate + $randomDelta : $pair->rate - $randomDelta);

                $rate = $est_rate / 100;

                // Calculate estimated receive using market data
                if (strpos($marketData->symbol, 'USD') === 0) {
                    $estimatedReceive = round($orderAmount * $marketData->mid, 2);
                } else {
                    $estimatedReceive = round($orderAmount / $marketData->mid, 2);
                }

                // Compute earning
                $earning = $orderAmount * $rate;

                // Generate a unique transaction id
                $txid = 'o_' . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

                sleep(20);

                // Create the fake buy order
                $order = Order::create([
                    'user_id'   => $user->id,
                    'pair_id'   => $pair->id,
                    'txid'      => $txid,
                    'buy'       => $orderAmount,
                    'sell'      => null,
                    'est_rate'  => $est_rate,
                    'receive'   => $estimatedReceive,
                    'status'    => 'pending',
                    'earning'   => $earning,
                    'time'      => random_int(3, 18),
                ]);

                // Deduct the order amount from the user's trading wallet
                $wallet->trading_wallet -= $orderAmount;
                $wallet->save();

                $message = "Created fake order for user id {$user->id} on pair id {$pair->id} with order amount: {$orderAmount} and estimated receive: {$estimatedReceive}";
                $this->info($message);

                // Fire OrderUpdated event in USDT terms
                $isReversed = strpos($marketData->symbol, 'USD') === 0;
                $rate = $marketData->mid;

                $totalUSDT = $isReversed
                    ? $pair->volume / $rate
                    : $pair->volume * $rate;

                $usedLocal = Order::where('pair_id', $pair->id)->sum('receive');

                $usedUSDT = $isReversed
                    ? $usedLocal / $rate
                    : $usedLocal * $rate;

                $remainingUSDT = max($totalUSDT - $usedUSDT, 0);

                event(new OrderUpdated($pair->id, $remainingUSDT, $totalUSDT));
                Log::channel('order')->info("📢 Broadcasted OrderUpdated → Pair #{$pair->id}, Remaining USDT: {$remainingUSDT}, Total USDT: {$totalUSDT}");
            }
        }
    }
}