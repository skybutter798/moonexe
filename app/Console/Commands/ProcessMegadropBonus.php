<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Transfer;
use App\Models\Wallet;

use Illuminate\Support\Facades\Log;


class ProcessMegadropBonus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'megadrop:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Issue leverage bonus to eligible users for MEGADROP campaign';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing MEGADROP bonuses...');
        Log::channel('admin')->info('[MEGADROP] Processing started.');
    
        $startMY = Carbon::createFromFormat('Y-m-d H:i:s', '2025-05-19 12:00:00', 'Asia/Kuala_Lumpur');
        $endMY   = Carbon::createFromFormat('Y-m-d H:i:s', '2025-05-28 12:15:00', 'Asia/Kuala_Lumpur');
    
        $users = User::all();
    
        foreach ($users as $user) {
            $baseAmount = Transfer::where('user_id', $user->id)
                ->where('status', 'Completed')
                ->where('remark', 'package')
                ->whereBetween('created_at', [$startMY, $endMY])
                ->sum('amount');
    
            if ($baseAmount == 0) {
                continue;
            }
    
            $finalAmount = $baseAmount;
    
            if ($user->created_at < $startMY) {
                $finalAmount *= 1.5;
            } else {
                $finalAmount += 100;
            }
    
            $exists = Transfer::where('user_id', $user->id)
                ->where('remark', 'campaign')
                ->exists();
    
            if ($exists) {
                $msg = "Skipped user #{$user->id} — already received campaign bonus.";
                $this->warn($msg);
                Log::channel('admin')->warning("[MEGADROP] {$msg}");
                continue;
            }
    
            do {
                $txid = 'b_' . mt_rand(10000000, 99999999);
            } while (Transfer::where('txid', $txid)->exists());
    
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
    
            $wallet = Wallet::where('user_id', $user->id)->first();
    
            if ($wallet) {
                $wallet->increment('trading_wallet', $finalAmount);
            }
    
            $msg = "Bonus issued for user #{$user->id}, txid: {$txid}, amount: {$finalAmount}";
            $this->info($msg);
            Log::channel('admin')->info("[MEGADROP] {$msg}");
        }
    
        $this->info('MEGADROP processing completed.');
        Log::channel('admin')->info('[MEGADROP] Processing completed.');
    }

}
