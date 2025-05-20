<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transfer;
use Illuminate\Support\Facades\Log;

class CampaignAddToTradingWallet extends Command
{
    protected $signature = 'campaign:add-to-trading-wallet';
    protected $description = 'Add $100 to trading_wallet for all active users (status = 1) and create a transfer record.';

    public function handle()
    {
        $finalAmount = 100;

        $users = User::where('status', 1)
             ->whereNotIn('id', [1, 2])
             ->get();

        foreach ($users as $user) {
            // Check if campaign transfer already exists for this user
            $exists = Transfer::where('user_id', $user->id)
                ->where('amount', 100)
                ->where('remark', 'campaign')
                ->exists();
        
            if ($exists) {
                Log::channel('cronjob')->info("⏩ User ID {$user->id} already received campaign funds. Skipped.");
                continue;
            }
        
            $wallet = Wallet::where('user_id', $user->id)->first();
        
            if (!$wallet) {
                Log::channel('cronjob')->warning("User ID {$user->id} has no wallet. Skipped.");
                continue;
            }
        
            $oldBalance = $wallet->trading_wallet;
        
            $txid = 'b_' . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        
            Transfer::create([
                'user_id'     => $user->id,
                'txid'        => $txid,
                'from_wallet' => 'cash_wallet',
                'to_wallet'   => 'trading_wallet',
                'amount'      => $finalAmount,
                'status'      => 'Completed',
                'remark'      => 'campaign',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        
            $wallet->increment('trading_wallet', $finalAmount);
        
            $newBalance = $wallet->fresh()->trading_wallet;
        
            $logMsg = "✅ User ID {$user->id} trading_wallet updated: {$oldBalance} ➝ {$newBalance}";
            $this->info($logMsg);
            Log::channel('cronjob')->info($logMsg);
        }


        $this->info('🎉 Campaign funds added to all active users.');
        Log::channel('cronjob')->info('Campaign funds added to all active users.');
    }
}
