<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pair;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\MarketData;
use Illuminate\Support\Str;

class SeedRandomOrders extends Command
{
    protected $signature = 'seed:random-orders {start_user_id} {end_user_id} {pair_start_id} {pair_end_id}';
    protected $description = 'Seed 1 order per user per date group of pair created_at using market data and trading_wallet';

    public function handle()
    {
        $startUserId = (int) $this->argument('start_user_id');
        $endUserId   = (int) $this->argument('end_user_id');
        $pairStartId = (int) $this->argument('pair_start_id');
        $pairEndId   = (int) $this->argument('pair_end_id');

        $reverseCurrencies = ['LKR', 'VND', 'IDR', 'COP'];

        // Step 1: Get all pairs and group by date
        $pairs = Pair::whereBetween('id', [$pairStartId, $pairEndId])
                     ->orderBy('created_at', 'asc')
                     ->get()
                     ->groupBy(fn($pair) => $pair->created_at->format('Y-m-d'));

        if ($pairs->isEmpty()) {
            $this->error("No pairs found between ID {$pairStartId} and {$pairEndId}.");
            return 1;
        }

        // Step 2: Loop each user
        for ($userId = $startUserId; $userId <= $endUserId; $userId++) {
            $this->info("User: {$userId}");

            $wallet = Wallet::where('user_id', $userId)->first();
            if (!$wallet || $wallet->trading_wallet <= 0) {
                $this->warn("  Skipping user {$userId} due to no wallet or zero balance.");
                continue;
            }

            $amount = $wallet->trading_wallet;

            // Step 3: Loop each date group
            foreach ($pairs as $date => $pairGroup) {
                // Skip if user already has at least one order on this date
                $hasOrder = Order::where('user_id', $userId)
                    ->whereDate('created_at', $date)
                    ->exists();
            
                if ($hasOrder) {
                    $this->warn("  Skipping user {$userId} on {$date} — order already exists.");
                    continue;
                }
                
                $pair = $pairGroup->random();
                $currency = $pair->currency;

                $symbol = in_array($currency->c_name, $reverseCurrencies)
                        ? 'USD' . $currency->c_name
                        : $currency->c_name . 'USD';

                $marketData = MarketData::where('symbol', $symbol)
                                        ->orderBy('created_at', 'desc')
                                        ->first();

                if (!$marketData) {
                    $this->warn("  No market data for {$symbol}. Skipping date {$date}.");
                    continue;
                }

                $estimatedReceive = strpos($marketData->symbol, 'USD') === 0
                    ? round($amount * $marketData->mid, 2)
                    : round($amount / $marketData->mid, 2);

                $earning = $amount * ($pair->rate / 100);
                $existingOrder = Order::where('pair_id', $pair->id)->first();
                $est_rate = $existingOrder ? $existingOrder->est_rate : null;

                $txid = 'ro_' . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

                $order = new Order();
                $order->user_id   = $userId;
                $order->pair_id   = $pair->id;
                $order->txid      = $txid;
                $order->buy       = $amount;
                $order->sell      = null;
                $order->receive   = $estimatedReceive;
                $order->est_rate  = $est_rate;
                $order->status    = 'pending';
                $order->earning   = $earning;
                $order->time      = random_int(10800, 64800);
                $order->created_at = $pair->created_at;
                $order->save();

                $this->info("  ✅ Order created on {$date} using pair {$pair->id}.");
            }
        }

        return 0;
    }
}
