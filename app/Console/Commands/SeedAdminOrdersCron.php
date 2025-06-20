<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Pair;
use App\Models\Order;
use App\Models\Asset;
use App\Models\MarketData;
use App\Models\Setting;

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
        
        $buySettings = Setting::where('status', 1)
        ->whereIn('name', [
            'admin_buy_min_percent_low',
            'admin_buy_max_percent_low',
            'admin_buy_min_percent_mid',
            'admin_buy_max_percent_mid',
            'admin_buy_min_percent_high',
            'admin_buy_max_percent_high',
        ])
        ->pluck('value', 'name')
        ->toArray();
        
        $minLow = (int)($buySettings['admin_buy_min_percent_low'] ?? 5);
        $maxLow = (int)($buySettings['admin_buy_max_percent_low'] ?? 10);
        $minMid = (int)($buySettings['admin_buy_min_percent_mid'] ?? 10);
        $maxMid = (int)($buySettings['admin_buy_max_percent_mid'] ?? 20);
        $minHigh = (int)($buySettings['admin_buy_min_percent_high'] ?? 30);
        $maxHigh = (int)($buySettings['admin_buy_max_percent_high'] ?? 50);



        // Process each open pair.
        foreach ($openPairs as $pair) {
            $this->info("Processing admin order for pair id: {$pair->id}");
            Log::channel('order')->info("---------> Processing admin order for pair id: {$pair->id}");

            // Check the remaining volume for the pair.
            $sumOrdersReceive = Order::where('pair_id', $pair->id)->sum('receive');
            $remainingVolume = $pair->volume - $sumOrdersReceive;
            if ($remainingVolume <= 0) {
                $this->warn("No remaining volume for pair id {$pair->id}. Skipping.");
                //Log::channel('order')->warning("No remaining volume for pair id {$pair->id}. Skipping.");
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
            $gateTimeMinutes = $pair->gate_time;
            $progress = ($minutesPassed / $gateTimeMinutes) * 100;
            
            if ($progress < 30) {
                $percentage = random_int($minLow, $maxLow) / 100;
            } elseif ($progress < 60) {
                $percentage = random_int($minMid, $maxMid) / 100;
            } elseif ($progress < 95) {
                $percentage = random_int($minHigh, $maxHigh) / 100;
            } else {
                $percentage = 1;
            }
            
            $randomVolume = round($remainingVolume * $percentage, 2);
            
            if ($randomVolume <= 0) {
                $randomVolume = round(min(0.01, $remainingVolume), 2);
            }

            // Ensure that the random volume is not 0.
            if ($randomVolume <= 0) {
                $this->warn("Calculated random volume is zero for pair id {$pair->id}. Skipping.");
                Log::channel('order')->warning("Calculated random volume is zero for pair id {$pair->id}. Skipping.");
                continue;
            }

            $buyInUSD = 0;
            if (in_array($currency->c_name, $reverseCurrencies)) {
                // USD / Local (e.g. 1 USD = 24,000 VND → 1/24000)
                $buyInUSD = round($randomVolume / $marketData->mid, 2);
            } else {
                // Local / USD (e.g. 1 TWD = 0.031 USD → 1 * 0.031)
                $buyInUSD = round($randomVolume * $marketData->mid, 2);
            }
            
            $estimatedReceive = round($randomVolume, 2); // This is still in local currency


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
                $order->buy     = round($order->buy + $buyInUSD, 2);
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
                    'buy'     => $buyInUSD,
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
