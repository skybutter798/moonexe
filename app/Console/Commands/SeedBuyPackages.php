<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transfer;
use App\Services\UserRangeCalculator;
use App\Services\UplineDistributor;

class SeedBuyPackages extends Command
{
    protected $signature = 'seed:buy-packages {from?} {to?}';
    protected $description = 'Seed package purchases based on users’ wallet balance and real package logic';

    public function handle()
    {
        $from = $this->argument('from');
        $to   = $this->argument('to');

        $users = ($from && $to)
            ? User::whereBetween('id', [$from, $to])->get()
            : User::all();

        foreach ($users as $user) {
            $wallet = Wallet::where('user_id', $user->id)->first();
            if (!$wallet) {
                $this->error("Wallet not found for user ID: {$user->id}");
                continue;
            }

            $cash = $wallet->cash_wallet;
            if ($cash < 10 || $cash % 10 !== 0) {
                $this->info("User ID {$user->id} skipped (invalid cash wallet amount: {$cash})");
                continue;
            }

            // Determine first-time activation vs top-up
            $isFirstTime = !$user->package;
            $amount = $cash;

            // Check and assign direct range
            if ($isFirstTime) {
                $range = DB::table('directranges')
                    ->whereIn('id', [1, 2, 3])
                    ->where('min', '<=', $amount)
                    ->where(function ($q) use ($amount) {
                        $q->where('max', '>=', $amount)->orWhereNull('max');
                    })->first();
            } else {
                $range = DB::table('directranges')->where('id', $user->package)->first();
            }

            if (!$range) {
                $this->info("User ID {$user->id} skipped (no range matched for amount: {$amount})");
                continue;
            }

            // Perform transfer
            $wallet->cash_wallet -= $amount;
            $wallet->trading_wallet += $amount;
            $wallet->save();

            do {
                $txid = 't_' . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            } while (Transfer::where('txid', $txid)->exists());

            $transfer = Transfer::create([
                'user_id'     => $user->id,
                'txid'        => $txid,
                'from_wallet' => 'cash_wallet',
                'to_wallet'   => 'trading_wallet',
                'amount'      => $amount,
                'status'      => 'Completed',
                'remark'      => 'package (seeded)',
            ]);

            // Recalculate total for range adjustment
            $rangeData = (new UserRangeCalculator())->calculate($user);
            $total = $rangeData['total'];

            $newRange = DB::table('directranges')
                ->where('min', '<=', $total)
                ->where(function ($q) use ($total) {
                    $q->where('max', '>=', $total)->orWhereNull('max');
                })->first();

            if ($newRange && $newRange->id !== $user->package) {
                $user->package = $newRange->id;
                $user->save();
            }

            // Distribute to upline
            (new UplineDistributor())->distributeDirect($transfer, $range, $user);

            // Bonus logic
            if (!empty($user->bonus)) {
                $bonusCode = strtoupper($user->bonus);
                $promotion = DB::table('promotions')
                    ->whereRaw('LOWER(code) = ?', [strtolower($bonusCode)])
                    ->first();

                if ($promotion) {
                    $bonusAmount = $amount * $promotion->multiply;
                    $wallet->bonus_wallet += $bonusAmount;
                    $wallet->save();

                    do {
                        $bonusTxid = 'b_' . rand(10000, 99999);
                    } while (Transfer::where('txid', $bonusTxid)->exists());

                    Transfer::create([
                        'user_id'     => $user->id,
                        'txid'        => $bonusTxid,
                        'from_wallet' => 'trading_wallet',
                        'to_wallet'   => 'bonus_wallet',
                        'amount'      => $bonusAmount,
                        'status'      => 'Completed',
                        'remark'      => 'bonus (seeded)',
                    ]);
                }
            }

            $this->info("User {$user->id} seeded with range ID {$range->id} and amount {$amount}");
        }

        $this->info("All seeding completed.");
        return 0;
    }
}
