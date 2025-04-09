<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Currency;
use App\Models\Pair;
use App\Models\Order;
use App\Models\MarketData;
use App\Models\User;
use App\Services\UserRangeCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CreatePairsCommand extends Command
{
    protected $signature = 'pairs:create';

    protected $description = 'Auto create currency pairs based on each currency local trigger time set in the timezone column';

    public function handle()
    {
        // Use Malaysia time as the reference.
        $nowMYT = Carbon::now('Asia/Kuala_Lumpur');
        //Log::channel('pair')->info("--------------->Current MYT time: " . $nowMYT->toTimeString());
        $this->info("Current MYT time: " . $nowMYT->toTimeString());

        // Get all active currencies (status = 1) but skip id = 1.
        $currencies = Currency::where('status', 1)
                        ->where('id', '>', 1)
                        ->get();

        foreach ($currencies as $currency) {
            // Parse the timezone value to derive the trigger time in MYT.
            // Assume the value is like "16", "11", "12.30", etc.
            $timezoneValue = (string) $currency->timezone;
            $hour = 0;
            $minute = 0;

            if (strpos($timezoneValue, '.') !== false) {
                $parts = explode('.', $timezoneValue);
                $hour = (int)$parts[0];
                // If the part after the decimal has 2 digits (e.g. "30"), use that as minutes;
                // otherwise treat it as a fraction of an hour.
                if (strlen($parts[1]) == 2) {
                    $minute = (int)$parts[1];
                } else {
                    $minute = (int) round(((float)('0.' . $parts[1])) * 60);
                }
            } else {
                $hour = (int)$timezoneValue;
                $minute = 0;
            }

            $triggerMYT = Carbon::today('Asia/Kuala_Lumpur')->setTime($hour, $minute, 0);

            // Check if current MYT time is within a 1-minute window of triggerMYT.
            if ($nowMYT->format('H:i') === $triggerMYT->format('H:i')) {
                Log::channel('pair')->info("Creating pair for currency {$currency->c_name} at trigger time {$triggerMYT->toTimeString()}");
                $this->info("Creating pair for currency {$currency->c_name} at trigger time {$triggerMYT->toTimeString()}");

                // --- Calculate the available volume ---
                // Step 1: Calculate user group total for user id = 2 using our service.
                $user = User::find(2);
                if (!$user) {
                    Log::channel('pair')->error("User with id=2 not found.");
                    $this->error("User with id=2 not found.");
                    continue;
                }
                $calculator = new UserRangeCalculator();
                $calculation = $calculator->calculate($user);
                $total = $calculation['total'];

                // Step 2: Sum pending orders' buy amounts.
                $pendingSum = Order::where('status', 'pending')
                            ->where('user_id', '>=', 9)
                            ->sum('buy');

                // Log the total and pending sum.
                Log::channel('pair')->info("User total: {$total}, Pending orders sum: {$pendingSum}");

                // Step 3: Calculate available volume as total divided by a random value between 2 and 4.
                $divisor = rand(5, 10);
                $availableVolume = intval(($total + $pendingSum) / $divisor);
                Log::channel('pair')->info("Calculated available volume using divisor {$divisor}: {$availableVolume}");

                // --- Get the market rate for the currency ---
                // Look for a market_data record using either "CURUSD" or "USDCUR"
                $marketData = MarketData::where('symbol', $currency->c_name . 'USD')
                            ->orWhere('symbol', 'USD' . $currency->c_name)
                            ->first();

                if ($marketData && isset($marketData->mid)) {
                    $volrate = $marketData->mid;
                    Log::channel('pair')->info("Using market rate for {$currency->c_name}: {$volrate}");
                    $this->info("Using market rate for {$currency->c_name}: {$volrate}");
                } else {
                    // Log error and move on to the next currency if market data is not found.
                    Log::channel('pair')->error("No market data found for {$currency->c_name}. Skipping this currency.");
                    $this->error("No market data found for {$currency->c_name}. Skipping this currency.");
                    continue;
                }
                
                $symbol = $marketData->symbol;
                if (strpos($symbol, 'USD') === 0) {
                    $volume = $availableVolume * $volrate;
                } elseif (substr($symbol, -3) === 'USD') {
                    $volume = $availableVolume / $volrate;
                } else {
                    $volume = $availableVolume * $volrate;
                }


                // --- Determine random gate_time and end_time ---
                // gate_time: random multiple of 60 between 240 and 480.
                $gate_time = 60;
                
                $rate = mt_rand(45, 70) / 100;
                $end_time = 24;

                // Create the pair record.
                Pair::create([
                    'currency_id' => $currency->id,
                    'pair_id'     => 1,
                    'min_rate'    => 0,
                    'rate'        => $rate,
                    'max_rate'    => 0,
                    'volume'      => $volume,
                    'gate_time'   => $gate_time,
                    'end_time'    => $end_time,
                ]);

                Log::channel('pair')->info("Pair created for currency {$currency->c_name} with volume {$volume}");
                $this->info("Pair created for currency {$currency->c_name} with volume {$volume}");
            } else {
                $this->line("Skipping {$currency->c_name}: trigger MYT " . $triggerMYT->toTimeString() . ", current MYT " . $nowMYT->toTimeString());
            }
        }

        return 0;
    }
}
