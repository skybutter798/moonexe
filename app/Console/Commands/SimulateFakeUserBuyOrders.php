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
        // Retrieve all users with status = 2.
        $users = User::where('status', 2)
             ->orWhere('id', 16)
             ->get();

        if ($users->isEmpty()) {
            $this->info("No users with status 2 found.");
            log::channel('order')->info("No users with status 2 found.");
            return;
        }

        // Randomly select between 8 and 30 users.
        $numberToProcess = rand(8, 30);
        if ($users->count() < $numberToProcess) {
            $numberToProcess = $users->count();
        }
        
        $nonUser16 = $users->where('id', '!=', 16);
        $remainingCount = $numberToProcess - 1;
        
        $selectedUsers = collect([$users->firstWhere('id', 16)]);
        if ($remainingCount > 0 && $nonUser16->count() >= $remainingCount) {
            $selectedUsers = $selectedUsers->merge($nonUser16->random($remainingCount));
        } else {
            $selectedUsers = $selectedUsers->merge($nonUser16);
        }


        foreach ($selectedUsers as $user) {
            $message = "--------> Processing fake orders for user id: {$user->id}";
            $this->info($message);
            log::channel('order')->info($message);

            // Retrieve the user's wallet.
            $wallet = Wallet::where('user_id', $user->id)->first();
            if (!$wallet) {
                $message = "Wallet not found for user id: {$user->id}. Skipping.";
                $this->warn($message);
                Log::channel('order')->warning($message);
                continue;
            }

            if ($wallet->trading_wallet <= 0 || ($user->id == 16 && $wallet->trading_wallet <= 50000)) {
                $message = "User id {$user->id} has insufficient trading wallet funds. Skipping.";
                $this->warn($message);
                Log::channel('order')->warning($message);
                continue;
            }


            // Retrieve pairs that have not expired.
            // A pair is available if the current time is less than (created_at + gate_time minutes).
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

            // Loop through each available pair.
            foreach ($availablePairs->shuffle() as $pair) {
                // Retrieve market data for the pair's currency.
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

                // Calculate remaining asset volume based on previous orders.
                // Use the sum of orders' receive (asset quantity) since buy orders deposit USD and receive assets.
                $sumOrdersReceive = Order::where('pair_id', $pair->id)->sum('receive');
                $remainingAssetVolume = $pair->volume - $sumOrdersReceive;

                if ($remainingAssetVolume <= 0) {
                    $message = "Pair id {$pair->id} has no remaining asset volume. Skipping.";
                    $this->info($message);
                    //log::channel('order')->info($message);
                    continue;
                }

                // Convert the remaining asset volume to USD using the market mid rate.
                if (strpos($marketData->symbol, 'USD') === 0) {
                    $remainingUSDVolume = round($remainingAssetVolume / $marketData->mid, 2);
                    //log::channel('order')->info("Available check: {$remainingAssetVolume} / {$marketData->mid} = {$remainingUSDVolume}");
                } else {
                    $remainingUSDVolume = round($remainingAssetVolume * $marketData->mid, 2);
                    //log::channel('order')->info("Available check: {$remainingAssetVolume} * {$marketData->mid} = {$remainingUSDVolume}");
                }

                if ($remainingUSDVolume <= 0) {
                    $message = "Pair id {$pair->id} has no remaining USD volume after conversion. Skipping.";
                    $this->info($message);
                    //log::channel('order')->info($message);
                    continue;
                }

                // Determine the order amount as the lesser of the user's available funds and the pair's remaining USD volume.
                $orderAmount = min($wallet->trading_wallet, $remainingUSDVolume);

                // Ensure the order amount meets the minimum allowed (e.g., 10).
                if ($orderAmount < 10) {
                    $message = "Calculated order amount is below the minimum for pair id {$pair->id}. Skipping.";
                    $this->info($message);
                    //log::channel('order')->info($message);
                    continue;
                }
                
                $existingOrder = Order::where('pair_id', $pair->id)->first();
                $randomDelta = mt_rand(1, 4) / 100;
                $shouldAdd = mt_rand(0, 1) == 1;

                $est_rate = ($existingOrder && $existingOrder->est_rate !== null)
                            ? $existingOrder->est_rate
                            : ($shouldAdd 
                                ? $pair->rate + $randomDelta 
                                : $pair->rate - $randomDelta);

                $rate = $est_rate / 100;

                
                // Calculate estimated receive using market data.
                if (strpos($marketData->symbol, 'USD') === 0) {
                    $estimatedReceive = round($orderAmount * $marketData->mid, 2);
                } else {
                    $estimatedReceive = round($orderAmount / $marketData->mid, 2);
                }

                // Compute earning using the pair's rate.
                $earning = $orderAmount * $rate;

                // Generate a unique transaction id.
                $txid = 'o_' . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

                sleep(20);
                
                // Create the fake buy order.
                $order = Order::create([
                    'user_id' => $user->id,
                    'pair_id' => $pair->id,
                    'txid'    => $txid,
                    'buy'     => $orderAmount,
                    'sell'    => null,
                    'est_rate' => $est_rate,
                    'receive' => $estimatedReceive,
                    'status'  => 'pending',
                    'earning' => $earning,
                    'time' => random_int(3, 18),
                ]);

                // Deduct the order amount from the user's trading wallet.
                $wallet->trading_wallet -= $orderAmount;
                $wallet->save();

                $message = "Created fake order for user id {$user->id} on pair id {$pair->id} with order amount: {$orderAmount} and estimated receive: {$estimatedReceive}";
                $this->info($message);
                log::channel('order')->info($message);
                
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
                
                // ✅ Fire OrderUpdated event in USDT terms
                event(new OrderUpdated($pair->id, $remainingUSDT, $totalUSDT));
                Log::channel('order')->info("📢 Broadcasted OrderUpdated → Pair #{$pair->id}, Remaining USDT: {$remainingUSDT}, Total USDT: {$totalUSDT}");


            }
        }
    }
}