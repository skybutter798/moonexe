<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Deposit;
use App\Models\Wallet;

class SeedDeposits extends Command
{
    protected $signature = 'seed:deposits {start} {end}';
    protected $description = 'Seed deposits for users in a given ID range using a distribution of 40% with 10,000, 40% with 5,000, and 20% with 1,000.';

    public function handle()
    {
        $start = $this->argument('start');
        $end   = $this->argument('end');

        // Retrieve users in the specified range.
        $users = User::whereBetween('id', [$start, $end])->get();

        if ($users->isEmpty()) {
            $this->error("No users found between ID {$start} and {$end}.");
            return 1;
        }

        $totalUsers = $users->count();

        // Calculate the number of users for each deposit tier.
        $numHigh = floor($totalUsers * 0.4);   // 40% get 10,000
        $numMid  = floor($totalUsers * 0.4);   // 40% get 5,000
        $numLow  = $totalUsers - ($numHigh + $numMid); // Rest get 1,000

        // Create an array of user IDs and shuffle it.
        $userIds = $users->pluck('id')->toArray();
        shuffle($userIds);

        // Assign deposit amounts.
        $depositAmounts = [];
        // 40% get 10,000
        foreach (array_slice($userIds, 0, $numHigh) as $id) {
            $depositAmounts[$id] = 10000;
        }
        // 40% get 5,000
        foreach (array_slice($userIds, $numHigh, $numMid) as $id) {
            $depositAmounts[$id] = 5000;
        }
        // Remaining users get 1,000
        foreach (array_slice($userIds, $numHigh + $numMid) as $id) {
            $depositAmounts[$id] = 1000;
        }

        $this->info("Total users: {$totalUsers}");
        $this->info("Users receiving 10,000: " . implode(', ', array_keys(array_filter($depositAmounts, function($amt) { return $amt == 10000; }))));
        $this->info("Users receiving 5,000: " . implode(', ', array_keys(array_filter($depositAmounts, function($amt) { return $amt == 5000; }))));
        $this->info("Users receiving 1,000: " . implode(', ', array_keys(array_filter($depositAmounts, function($amt) { return $amt == 1000; }))));

        // Fixed TRC20 address for deposits.
        $sampleTRC20 = 'TR9wHy8rF89a59gD3dmMPhrtPhtu6n5U5H';

        foreach ($users as $user) {
            // Determine deposit amount for this user based on our mapping.
            $amount = $depositAmounts[$user->id] ?? 1000;

            // Generate a unique txid.
            do {
                $randomNumber = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
                $txid = 'd_' . $randomNumber;
            } while (Deposit::where('txid', $txid)->exists());

            // Create the deposit record with status "Completed".
            $deposit = Deposit::create([
                'user_id'       => $user->id,
                'txid'          => $txid,
                'amount'        => $amount,
                'trc20_address' => $sampleTRC20,
                'status'        => 'Completed',
            ]);

            // Find or create the user's wallet.
            $wallet = Wallet::firstOrNew(['user_id' => $user->id]);
            if (!$wallet->exists) {
                $wallet->cash_wallet       = 0;
                $wallet->trading_wallet    = 0;
                $wallet->earning_wallet    = 0;
                $wallet->affiliates_wallet = 0;
                $wallet->bonus_wallet      = 0;
            }

            // Add the deposit amount to the cash wallet.
            $wallet->cash_wallet += $amount;
            $wallet->save();

            $this->info("Deposited {$amount} to user ID {$user->id}.");
        }

        $this->info("Deposits seeded successfully for users between ID {$start} and {$end}.");
        return 0;
    }
}