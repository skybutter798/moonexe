<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Currency;
use App\Models\Pair;
use App\Models\Order;
use App\Models\MarketData;
use App\Models\User;
use App\Models\Setting;
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
        $this->info("Current MYT time: " . $nowMYT->toTimeString());

        // Fetch settings from database
        $settings = Setting::where('status', 1)
            ->whereIn('name', [
                'pair_divisor_min',
                'pair_divisor_max',
                'pair_rate_min',
                'pair_rate_max',
                'pair_gate_time',
                'pair_end_time',
            ])
            ->pluck('value', 'name')
            ->toArray();

        // Set fallback default values if settings missing
        $divisorMin = isset($settings['pair_divisor_min']) ? (int)$settings['pair_divisor_min'] : 5;
        $divisorMax = isset($settings['pair_divisor_max']) ? (int)$settings['pair_divisor_max'] : 10;
        $rateMin = isset($settings['pair_rate_min']) ? (int)$settings['pair_rate_min'] : 45;
        $rateMax = isset($settings['pair_rate_max']) ? (int)$settings['pair_rate_max'] : 70;
        $defaultGateTime = isset($settings['pair_gate_time']) ? (int)$settings['pair_gate_time'] : 600;
        $defaultEndTime = isset($settings['pair_end_time']) ? (int)$settings['pair_end_time'] : 24;

        // Get all active currencies (status = 1) but skip id = 1.
        $currencies = Currency::where('status', 1)
                        ->where('id', '>', 1)
                        ->get();

        foreach ($currencies as $currency) {
            // Parse the timezone value to derive the trigger time in MYT.
            $timezoneValue = (string) $currency->timezone;
            $hour = 0;
            $minute = 0;

            if (strpos($timezoneValue, '.') !== false) {
                $parts = explode('.', $timezoneValue);
                $hour = (int)$parts[0];
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

            if ($nowMYT->format('H:i') === $triggerMYT->format('H:i')) {
                Log::channel('pair')->info("Creating pair for currency {$currency->c_name} at trigger time {$triggerMYT->toTimeString()}");
                $this->info("Creating pair for currency {$currency->c_name} at trigger time {$triggerMYT->toTimeString()}");

                $user = User::find(2);
                if (!$user) {
                    Log::channel('pair')->error("User with id=2 not found.");
                    $this->error("User with id=2 not found.");
                    continue;
                }

                $calculator = new UserRangeCalculator();
                $calculation = $calculator->calculate($user);
                $total = $calculation['total'];

                $pendingSum = Order::where('status', 'pending')
                            ->where('user_id', '>', 2)
                            ->sum('buy');

                Log::channel('pair')->info("User total: {$total}, Pending orders sum: {$pendingSum}");

                // Dynamic divisor
                $gateHours = max(intval($defaultGateTime / 60), 1);  
                $hours = max($gateHours - 1, 1); 
                $divisor = rand($divisorMin, $divisorMax);
                $availableVolume = intval((($total + $pendingSum) / $divisor) / $hours);
                
                if ($availableVolume <= 0) {
                    Log::channel('pair')->warning("Volume calculated as 0. Skipping.");
                    continue;
                }

                Log::channel('pair')->info("Calculated available volume using divisor {$divisor}: {$availableVolume}");

                $marketData = MarketData::where('symbol', $currency->c_name . 'USD')
                            ->orWhere('symbol', 'USD' . $currency->c_name)
                            ->first();

                if ($marketData && isset($marketData->mid)) {
                    $volrate = $marketData->mid;
                    Log::channel('pair')->info("Using market rate for {$currency->c_name}: {$volrate}");
                    $this->info("Using market rate for {$currency->c_name}: {$volrate}");
                } else {
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

                // Dynamic rate, gate_time, end_time
                $rate = mt_rand($rateMin, $rateMax) / 100;
                $gate_time = $defaultGateTime;
                $end_time = $defaultEndTime;
                
                if ($currency->c_name === 'COP') {
                    $volume = 60074000;
                    $rate = 0.62;
                
                    Log::channel('pair')->info("COP special case: volume adjusted to {$volume}, rate set to {$rate}");
                }


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
