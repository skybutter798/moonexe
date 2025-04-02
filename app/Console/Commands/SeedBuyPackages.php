<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transfer;

class SeedBuyPackages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Run with optional user id range:
     * php artisan seed:buy-packages {from?} {to?}
     *
     * @var string
     */
    protected $signature = 'seed:buy-packages {from? : Starting user ID} {to? : Ending user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed package purchases for users based on their cash wallet amount, with an optional user ID range';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Get optional user ID range arguments
        $from = $this->argument('from');
        $to   = $this->argument('to');

        if ($from && $to) {
            $users = User::whereBetween('id', [$from, $to])->get();
            $this->info("Processing users with IDs between {$from} and {$to}");
        } else {
            $users = User::all();
            $this->info("Processing all users");
        }

        foreach ($users as $user) {
            // Fetch user's wallet
            $wallet = Wallet::where('user_id', $user->id)->first();
            if (!$wallet) {
                $this->error("Wallet not found for user ID: {$user->id}");
                continue;
            }

            // Determine the package based on the user's cash wallet amount
            $cashAmount = $wallet->cash_wallet;
            $selectedPackageId = null;

            if ($cashAmount == 1000) {
                $selectedPackageId = 1;
            } elseif ($cashAmount == 5000) {
                $selectedPackageId = 2;
            } elseif ($cashAmount == 10000) {
                $selectedPackageId = 3;
            } else {
                $this->info("User ID {$user->id} with cash wallet {$cashAmount} does not match any predefined package.");
                continue;
            }

            // Retrieve the chosen package (the "new package")
            $newPackage = DB::table('packages')->where('id', $selectedPackageId)->first();
            if (!$newPackage) {
                $this->error("Package ID {$selectedPackageId} not found for user ID: {$user->id}");
                continue;
            }

            // Override the package's eshare value with the full cash wallet amount
            $newPackage->eshare = $cashAmount;

            // Check that the user has enough funds (should be true if the amount matches)
            if ($wallet->cash_wallet < $newPackage->eshare) {
                $this->error("Insufficient funds for user ID: {$user->id}");
                continue;
            }

            // Perform the transfer: deduct from cash_wallet and add to trading_wallet
            $wallet->cash_wallet -= $newPackage->eshare;
            $wallet->trading_wallet += $newPackage->eshare;
            $wallet->save();

            // Generate a unique transaction id for the package transfer
            do {
                $randomNumber = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
                $txid = 't_' . $randomNumber;
            } while (Transfer::where('txid', $txid)->exists());

            // Record the transfer with a remark indicating this is from the seed command
            $transfer = Transfer::create([
                'user_id'     => $user->id,
                'txid'        => $txid,
                'from_wallet' => 'cash_wallet',
                'to_wallet'   => 'trading_wallet',
                'amount'      => $newPackage->eshare,
                'status'      => 'Completed',
                'remark'      => 'package seed',
            ]);

            // Check current package profit (if any) to decide whether to update package.
            $currentPackageProfit = 0;
            if ($user->package) {
                $currentPackage = DB::table('packages')->where('id', $user->package)->first();
                if ($currentPackage) {
                    $currentPackageProfit = $currentPackage->eshare;
                }
            }
            $updatePackage = true;
            if ($currentPackageProfit > $newPackage->eshare) {
                $updatePackage = false;
            }
            if ($updatePackage) {
                $user->package = $newPackage->id;
                $user->save();
            }

            // New Direct Upline Distribution
            // Make sure the UplineDistributor service exists and is properly configured.
            $uplineDistributor = new \App\Services\UplineDistributor();
            $uplineDistributor->distributeDirect($transfer, $newPackage, $user);

            // --- BONUS TRANSFER LOGIC ---
            if (!empty($user->bonus)) {
                // Normalize the bonus code (example: uppercase)
                $bonusCode = strtoupper($user->bonus);
                
                // Find the promotion record using a case-insensitive search
                $promotion = DB::table('promotions')
                    ->whereRaw('LOWER(code) = ?', [strtolower($bonusCode)])
                    ->first();
                    
                if ($promotion) {
                    // Calculate bonus amount using the promotion's multiply factor
                    $multiply = $promotion->multiply; // e.g., 1.0
                    $bonusAmount = $newPackage->eshare * $multiply;
        
                    // Update the bonus wallet by adding the bonus amount
                    $wallet->bonus_wallet += $bonusAmount;
                    $wallet->save();
        
                    // Generate a bonus transfer txid ensuring uniqueness
                    do {
                        $bonusTxid = 'b_' . rand(10000, 99999);
                    } while (Transfer::where('txid', $bonusTxid)->exists());
        
                    // Create a bonus transfer record
                    Transfer::create([
                        'user_id'     => $user->id,
                        'txid'        => $bonusTxid,
                        'from_wallet' => 'trading_wallet',
                        'to_wallet'   => 'bonus_wallet',
                        'amount'      => $bonusAmount,
                        'status'      => 'Completed',
                        'remark'      => 'bonus',
                    ]);
                }
            }
            // --- END BONUS LOGIC ---

            $this->info("Processed user ID {$user->id} using package ID {$newPackage->id} for amount {$newPackage->eshare}");
        }

        $this->info("Package seeding completed.");
        return 0;
    }
}