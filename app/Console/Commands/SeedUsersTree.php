<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SeedUsersTree extends Command
{
    protected $signature = 'seed:users-tree {root=16} {total=167}';
    protected $description = 'Seed a referral tree of new users under a given root user.';

    // Sample names for usernames.
    protected $sampleNames = [
        'sky007', 'yuli_01', 'yangyong', 'alex', 'jordan', 'lisa', 'mike', 'john', 'alice', 'bob', 'charlie', 'david', 'eva'
    ];

    public function handle()
    {
        $rootId = $this->argument('root');
        $totalToCreate = (int) $this->argument('total');

        $rootUser = User::find($rootId);
        if (!$rootUser) {
            $this->error("Root user with id {$rootId} not found.");
            return 1;
        }

        // We'll use a queue to manage the referral tree.
        $queue = [$rootUser];
        $createdCount = 0;

        while ($createdCount < $totalToCreate && !empty($queue)) {
            // Pop the next parent from the queue.
            $parent = array_shift($queue);

            // Determine how many children to create.
            // For the root, create 3-4; for others, 1-5.
            $min = ($parent->id == $rootId) ? 3 : 1;
            $max = ($parent->id == $rootId) ? 4 : 5;
            // Limit children if we're near our total target.
            $remaining = $totalToCreate - $createdCount;
            $numChildren = rand($min, $max);
            $numChildren = min($numChildren, $remaining);

            for ($i = 0; $i < $numChildren; $i++) {
                // Generate a random username from our list with a random numeric suffix.
                $baseName = $this->sampleNames[array_rand($this->sampleNames)];
                $username = $baseName . rand(10, 99);

                // Generate email using username and a counter to keep it unique.
                $email = $username . '_' . time() . rand(100, 999) . '@example.com';

                // Generate a unique referral code.
                $referralCode = $this->generateUniqueReferralCode();

                // Build referral link using your app URL (adjust if needed).
                $referralLink = config('app.url') . '/register?ref=' . $referralCode;

                // Create the new user.
                $user = User::create([
                    'name'          => $username,
                    'email'         => $email,
                    'password'      => Hash::make('password'), // default password, adjust if needed.
                    'referral'      => $parent->id,
                    'referral_code' => $referralCode,
                    'referral_link' => $referralLink,
                    // No promotion bonus.
                    'bonus'         => null,
                ]);

                // Create a wallet for the user with all balances set to 0.
                Wallet::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'cash_wallet'       => 0,
                        'trading_wallet'    => 0,
                        'earning_wallet'    => 0,
                        'affiliates_wallet' => 0,
                        'bonus_wallet'      => 0,
                    ]
                );

                $createdCount++;
                $this->info("Created user {$user->id} under parent {$parent->id}: {$username}");

                // Add the new user to the queue so that they in turn can have referrals.
                $queue[] = $user;

                if ($createdCount >= $totalToCreate) {
                    break;
                }
            }
        }

        $this->info("Seeded {$createdCount} new users under user id {$rootId}.");
        return 0;
    }

    /**
     * Generate a unique referral code.
     *
     * @return string
     */
    protected function generateUniqueReferralCode()
    {
        do {
            $code = strtoupper(Str::random(7));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }
}
