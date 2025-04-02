<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pair;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\Asset;
use App\Models\MarketData;

class SeedOrdersForUser extends Command
{
    // Accept four arguments: starting and ending user ids and pair range (start and end).
    protected $signature = 'seed:orders {start_user_id} {end_user_id} {pair_start_id} {pair_end_id}';
    protected $description = 'Create one buy order per user picking one pair within a given id range using market data for estimated receive. The order amount is taken from the user wallet trading_wallet and deducted from it.';

    public function handle()
    {
        $startUserId = (int) $this->argument('start_user_id');
        $endUserId   = (int) $this->argument('end_user_id');
        $pairStartId = (int) $this->argument('pair_start_id');
        $pairEndId   = (int) $this->argument('pair_end_id');

        // Loop through each user in the range.
        for ($userId = $startUserId; $userId <= $endUserId; $userId++) {
            $this->info("Processing order for user id: {$userId}");

            // Retrieve pairs in the given range.
            $pairs = Pair::whereBetween('id', [$pairStartId, $pairEndId])
                         ->orderBy('created_at', 'asc')
                         ->get();

            if ($pairs->isEmpty()) {
                $this->error("No pairs found with id between {$pairStartId} and {$pairEndId}.");
                continue;
            }

            // Get a random pair from the retrieved set.
            $pair = $pairs->random();

            // Retrieve the latest market data for the pair's currency.
            $currency = $pair->currency;
            $reverseCurrencies = ['LKR', 'VND', 'IDR', 'COP'];
            if (in_array($currency->c_name, $reverseCurrencies)) {
                $symbol = 'USD' . $currency->c_name;
            } else {
                $symbol = $currency->c_name . 'USD';
            }

            $marketData = MarketData::where('symbol', $symbol)
                                    ->orderBy('created_at', 'desc')
                                    ->first();

            if (!$marketData) {
                $this->warn("No market data found for symbol: {$symbol} for pair id {$pair->id}. Skipping order.");
                continue;
            }

            // Generate a unique transaction id.
            $txid = 'o_' . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

            // Check if the current user is admin (user id 1).
            if ($userId === 1) {
                // For admin, ignore the wallet (which is always 0)
                // Determine the remaining volume for this pair:
                //   remainingVolume = pair->volume - sum(orders->receive) for this pair
                $sumOrdersReceive = Order::where('pair_id', $pair->id)->sum('receive');
                $remainingVolume = $pair->volume - $sumOrdersReceive;

                if ($remainingVolume <= 0) {
                    $this->warn("No remaining volume for pair id {$pair->id}. Skipping admin order for user id {$userId}.");
                    continue;
                }

                // Reverse the calculation:
                // If market symbol starts with 'USD', compute buy amount by dividing remainingVolume by mid,
                // otherwise multiply remainingVolume by mid.
                if (strpos($marketData->symbol, 'USD') === 0) {
                    $adminBuy = round($remainingVolume / $marketData->mid, 2);
                } else {
                    $adminBuy = round($remainingVolume * $marketData->mid, 2);
                }

                // Compute earning using the pair rate.
                $earning = $adminBuy * ($pair->rate / 100);

                // Create the order for admin.
                $order = Order::create([
                    'user_id' => $userId,
                    'pair_id' => $pair->id,
                    'txid'    => $txid,
                    'buy'     => $adminBuy,
                    'sell'    => null,
                    'receive' => $remainingVolume,
                    'status'  => 'pending',
                    'earning' => $earning,
                ]);

                $this->info("Admin order created for user id {$userId} (pair id {$pair->id}) with remaining volume: {$remainingVolume} and buy amount: {$adminBuy}.");
            } else {
                // For normal users, get the user's wallet.
                $wallet = Wallet::where('user_id', $userId)->first();
                if (!$wallet) {
                    $this->error("Wallet not found for user id {$userId}. Skipping.");
                    continue;
                }

                // Use the user's trading wallet as the order amount.
                $amount = $wallet->trading_wallet;
                if (!$amount || $amount <= 0) {
                    $this->warn("Trading wallet amount is 0 for user id {$userId}. Skipping.");
                    continue;
                }

                // Calculate the estimated receive using the market data.
                if (strpos($marketData->symbol, 'USD') === 0) {
                    $estimatedReceive = round($amount * $marketData->mid, 2);
                } else {
                    $estimatedReceive = round($amount / $marketData->mid, 2);
                }

                $earning = $amount * ($pair->rate / 100);

                $order = Order::create([
                    'user_id' => $userId,
                    'pair_id' => $pair->id,
                    'txid'    => $txid,
                    'buy'     => $amount,
                    'sell'    => null,
                    'receive' => $estimatedReceive,
                    'status'  => 'pending',
                    'earning' => $earning,
                ]);

                // Deduct the amount from the user's trading wallet.
                $wallet->trading_wallet -= $amount;
                $wallet->save();

                $this->info("Created order (pair id {$pair->id}) with estimated receive: {$estimatedReceive} for user id {$userId}.");
            }

            // Update or create an asset record for the user for the given currency.
            $asset = Asset::firstOrNew([
                'user_id'  => $userId,
                'currency' => $currency->id,
            ]);
            // For both admin and normal users, add the receive amount to the asset.
            $asset->amount = ($asset->amount ?? 0) + ($order->receive);
            $asset->status = 'active';
            $asset->save();
        }
    }
}