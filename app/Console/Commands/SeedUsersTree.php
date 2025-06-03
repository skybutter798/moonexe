<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SeedUsersTree extends Command
{
    protected $signature = 'seed:users-tree {root=254} {total=20}';
    protected $description = 'Seed a referral tree of new users under a given root user.';

    // Sample names for usernames.
    protected $sampleNames = [
        // Chinese (10 more)
        'liwei', 'meilin', 'xiaojun', 'zhangyu', 'huangmei',
        'weijie', 'xiaolin', 'minghao', 'fanying', 'liling',
        'cheng', 'yue', 'tao', 'rui', 'jin',
        
        // Japanese (10 more)
        'takashi', 'yukiko', 'haruto', 'sakura', 'renji',
        'kaito', 'hina', 'ryota', 'mina', 'yui',
        'kenta', 'kana', 'satoshi', 'mei', 'riku',
        
        // Korean (10 more)
        'minji', 'jongwoo', 'seojin', 'haeun', 'hyunwoo',
        'jiyoon', 'seungmin', 'jihye', 'minseok', 'yoona',
        'taeyang', 'soojin', 'junho', 'bora', 'woojin',
        
        // Western (10 more)
        'emily', 'michael', 'sophia', 'daniel', 'oliver',
        'chloe', 'jacob', 'ava', 'ethan', 'isabella',
        'olivia', 'liam', 'emma', 'noah', 'lucas',
        'mia', 'logan', 'harper', 'jack', 'amelia'
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
            $min = ($parent->id == $rootId) ? 4 : 2;
            $max = ($parent->id == $rootId) ? 4 : 5;
            // Limit children if we're near our total target.
            $remaining = $totalToCreate - $createdCount;
            $numChildren = rand($min, $max);
            $numChildren = min($numChildren, $remaining);

            for ($i = 0; $i < $numChildren; $i++) {
                // Generate a random username from our list with a random numeric suffix.
                $baseName = $this->sampleNames[array_rand($this->sampleNames)];
                $username = $baseName . rand(10, 256);

                // Generate email using username and a counter to keep it unique.
                $email = $username . '_' . time() . rand(100, 999) . '@gmail.com';

                // Generate a unique referral code.
                $referralCode = $this->generateUniqueReferralCode();

                // Build referral link using your app URL (adjust if needed).
                $referralLink = config('app.url') . '/register?ref=' . $referralCode;

                $user = User::create([
                    'name'           => $username,
                    'email'          => $email,
                    'role'           => 'user',
                    'status'         => '2',
                    'password'       => Hash::make('password'),
                    'referral'       => $parent->id,
                    'referral_code'  => $referralCode,
                    'referral_link'  => $referralLink,
                    'bonus'          => null,
                ]);


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
