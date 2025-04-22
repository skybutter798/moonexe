<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Pair;
use App\Models\Order;
use App\Models\Asset;
use App\Models\MarketData;
use Illuminate\Support\Facades\Log;

use App\Events\OrderUpdated;

class SeedAdminOrdersCron extends Command
{
    protected $signature = 'seed:admin-orders';
    protected $description = 'Simulate admin orders (user id 1) every minute for open pairs using a random percentage of remaining volume.';

    public function handle()
    {
        $now = Carbon::now();

        // Retrieve pairs that are still open.
        $openPairs = Pair::whereRaw("DATE_ADD(created_at, INTERVAL gate_time MINUTE) > ?", [$now])
                         ->orderBy('created_at', 'asc')
                         ->get();

        if ($openPairs->isEmpty()) {
            $this->info("No open pairs found at " . $now->toDateTimeString() . ".");
            Log::channel('order')->info("No open pairs found at " . $now->toDateTimeString() . ".");
            return;
        }

        // Process each open pair.
        foreach ($openPairs as $pair) {
            $this->info("Processing admin order for pair id: {$pair->id}");
            Log::channel('order')->info("---------> Processing admin order for pair id: {$pair->id}");

            // Check the remaining volume for the pair.
            $sumOrdersReceive = Order::where('pair_id', $pair->id)->sum('receive');
            $remainingVolume = $pair->volume - $sumOrdersReceive;
            if ($remainingVolume <= 0) {
                $this->warn("No remaining volume for pair id {$pair->id}. Skipping.");
                Log::channel('order')->warning("No remaining volume for pair id {$pair->id}. Skipping.");
                continue;
            }

            // Retrieve market data for the pair's currency.
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
                $this->warn("No market data found for symbol: {$symbol} for pair id {$pair->id}. Skipping.");
                Log::channel('order')->warning("No market data found for symbol: {$symbol} for pair id {$pair->id}. Skipping.");
                continue;
            }

            // Calculate how many minutes have passed since creation
            $createdAt      = Carbon::parse($pair->created_at);
            $minutesPassed  = $createdAt->diffInMinutes(now());
            
            if ($minutesPassed >= 45) {
                // After 45 minutes — buy all remaining
                $randomVolume = round($remainingVolume, 2);
            } else {
                // Before 45 minutes — buy small amount (1–2%)
                $percentage   = random_int(1, 2) / 100;
                $randomVolume = round($remainingVolume * $percentage, 2);
            }
            
            //$percentage   = random_int(1, 2) / 100;
            //$randomVolume = round($remainingVolume * $percentage, 2);

            // Ensure that the random volume is not 0.
            if ($randomVolume <= 0) {
                $this->warn("Calculated random volume is zero for pair id {$pair->id}. Skipping.");
                Log::channel('order')->warning("Calculated random volume is zero for pair id {$pair->id}. Skipping.");
                continue;
            }

            $estimatedReceive = round($randomVolume, 2);

            $existingOrder = Order::where('pair_id', $pair->id)->first();
            $randomDelta = mt_rand(1, 4) / 100;
            $shouldAdd = mt_rand(0, 1) == 1;
            
            $est_rate = ($existingOrder && $existingOrder->est_rate !== null)
                            ? $existingOrder->est_rate
                            : ($shouldAdd ? $pair->rate + $randomDelta : $pair->rate - $randomDelta);
            
            $rate = $est_rate / 100;
            
            $earning = round($randomVolume * $rate, 2);


            // Generate a unique transaction id.
            $txid = 'o_' . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

            // Check if an admin order for this pair already exists (pending).
            $order = Order::where('user_id', 1)
                          ->where('pair_id', $pair->id)
                          ->where('status', 'pending')
                          ->first();

            if ($order) {
                // Update the existing order by adding the new volumes.
                $order->buy     = round($order->buy + $randomVolume, 2);
                $order->receive = round($order->receive + $estimatedReceive, 2);
                $order->earning = round($order->earning + $earning, 2);
                $order->txid    = $txid; // Optionally update the txid.
                $order->save();

                $message = "Updated admin order for pair id {$pair->id}: added buy {$randomVolume}, receive {$estimatedReceive}.";
                $this->info($message);
                Log::channel('order')->info($message);
            } else {
                // Create a new order for admin.
                $order = Order::create([
                    'user_id' => 1,
                    'pair_id' => $pair->id,
                    'txid'    => $txid,
                    'buy'     => $randomVolume,
                    'sell'    => null,
                    'est_rate' => $est_rate,
                    'receive' => $estimatedReceive,
                    'status'  => 'pending',
                    'earning' => $earning,
                    'time' => random_int(3, 18),
                ]);

                $message = "Created new admin order for pair id {$pair->id} with buy {$randomVolume} and receive {$estimatedReceive}.";
                $this->info($message);
                Log::channel('order')->info($message);
            }

            // Update or create the asset record for admin (user id 1) for the given currency.
            $asset = Asset::firstOrNew([
                'user_id'  => 1,
                'currency' => $currency->id,
            ]);
            $asset->amount = ($asset->amount ?? 0) + $estimatedReceive;
            $asset->status = 'active';
            $asset->save();

            $message = "Updated asset for admin with currency id {$currency->id} by adding {$estimatedReceive}.";
            $this->info($message);
            Log::channel('order')->info($message);
            
            $updatedRemain = $remainingVolume-$estimatedReceive;
            event(new OrderUpdated($pair->id, $updatedRemain, $pair->volume));
        }
    }
}
