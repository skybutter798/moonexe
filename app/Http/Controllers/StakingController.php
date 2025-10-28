<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Staking;
use App\Models\Wallet;
use App\Models\Transfer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramService;
use Carbon\Carbon;

class StakingController extends Controller
{
    private function getWeeklyRate(string $name, float $default = 0.0): float
    {
        $val = DB::table('settings')->where('name', $name)->value('value');
        return is_null($val) ? $default : (float)$val; // e.g. 0.0140 for 1.40% weekly
    }

    private function weeklyRateForBalance(int|float $balance): float
    {
        // Highest tier first
        if ($balance >= 100000) {
            return $this->getWeeklyRate('staking_roi_100000', 0.0);
        } elseif ($balance >= 10000) {
            return $this->getWeeklyRate('staking_roi_10000', 0.0);
        } elseif ($balance >= 1000) {
            return $this->getWeeklyRate('staking_roi_1000', 0.0);
        } elseif ($balance >= 100) {
            return $this->getWeeklyRate('staking_roi_100', 0.0);
        }
        return 0.0;
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $user = Auth::user();

        return DB::transaction(function () use ($user, $request) {
            $recentStake = \App\Models\Staking::where('user_id', $user->id)
                ->where('created_at', '>', now()->subSeconds(5))
                ->exists();
        
            if ($recentStake) {
                return back()->with('error', 'Please wait a few seconds before submitting again.');
            }
            
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet || $wallet->trading_wallet < $request->amount) {
                return back()->with('error', 'Insufficient trading wallet balance.');
            }

            // get last active row & new running balance
            $last = Staking::where('user_id', $user->id)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $prevBalance = (int) ($last->balance ?? 0);
            $newBalance  = $prevBalance + (int) $request->amount;

            // move funds
            $wallet->trading_wallet -= (int) $request->amount;
            $wallet->save();

            $txid = strtoupper(uniqid('STK'));

            Staking::create([
                'user_id'  => $user->id,
                'txid'     => $txid,
                'amount'   => (int) $request->amount,
                'interest' => 0,
                'balance'  => $newBalance,
                'status'   => 'active',
            ]);

            Transfer::create([
                'user_id'     => $user->id,
                'txid'        => $txid,
                'from_wallet' => 'trading_wallet',
                'to_wallet'   => 'staking_wallet',
                'amount'      => (int) $request->amount,
                'status'      => 'Completed',
                'remark'      => 'staking',
            ]);
            
            Log::channel('order')->info('New stake created', [
                'user_id' => $user->id,
                'amount'  => (int) $request->amount,
                'balance' => $newBalance,
                'txid'    => $txid,
            ]);

            // âœ… Determine DAILY rate from latest running balance using Settings
            $weeklyRateDecimal = $this->weeklyRateForBalance($newBalance); // e.g. 0.0140
            $dailyRateDecimal  = $weeklyRateDecimal / 7;
            DB::afterCommit(function () use ($user) {
                Artisan::call('wallets:recalculate', [
                    'userRange' => $user->id,
                ]);
            });
            
            // Inside store()
            $chatId = ((int) $request->amount >= 2000)
                ? '-1002720623603'
                : '-4807439791';
            
            $message = "<b>📥 New Stake</b>\n"
                     . "ID: <code>{$user->id}</code>\n"
                     . "User: <b>{$user->name}</b>\n"
                     . "Email: <b>{$user->email}</b>\n"
                     . "Amount: <b>{$request->amount} USDT</b>\n"
                     . "TXID: <code>{$txid}</code>";
            
            (new TelegramService())->sendMessage($message, $chatId);


            return back()->with([
                'stake_success'    => true,
                'stake_amount'     => (int) $request->amount,
                'stake_total'       => $newBalance, 
                'stake_daily_rate' => $dailyRateDecimal,
                'stake_credit_day' => 'Monday',
            ]);
        });
    }

    public function unstake(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
        ]);
    
        $user = Auth::user();
    
        return DB::transaction(function () use ($user, $request) {
            $recentUnstake = \App\Models\Staking::where('user_id', $user->id)
                ->where('created_at', '>', now()->subSeconds(5))
                ->where('status', 'pending_unstake')
                ->exists();
        
            if ($recentUnstake) {
                return back()->with('error', 'Please wait a few seconds before submitting again.');
            }
            
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet) return back()->with('error', 'Wallet not found.');
    
            $last = Staking::where('user_id', $user->id)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

    
            $currentBalance = (int) ($last->balance ?? 0);
            $amount = (int) $request->amount;
    
            if ($currentBalance <= 0)       return back()->with('error', 'No active staking balance.');
            if ($amount > $currentBalance)  return back()->with('error', 'Amount exceeds staked balance.');
    
            $newBalance = $currentBalance - $amount;
    
            // NOTE: DO NOT credit trading_wallet here (we delay 24h)
            $txid = strtoupper(uniqid('UNSTK'));
    
            // negative ledger row, but mark as pending_unstake
            Staking::create([
                'user_id'  => $user->id,
                'txid'     => $txid,
                'amount'   => -1 * $amount,   // negative
                'interest' => 0,
                'balance'  => $newBalance,    // running total after this op
                'status'   => 'pending_unstake', // <-- key difference
            ]);
            
            Log::channel('order')->info('Unstake requested', [
                'user_id' => $user->id,
                'amount'  => $amount,
                'balance' => $newBalance,
                'txid'    => $txid,
            ]);
    
            DB::afterCommit(function () use ($user) {
                Artisan::call('wallets:recalculate', [
                    'userRange' => $user->id,
                ]);
            });
            
            // Inside unstake()
            $chatId = ((int) $amount >= 1000)
                ? '-1002720623603'
                : '-4807439791';
            
            $message = "<b>📥 Unstake</b>\n"
                     . "User: <b>{$user->name}</b>\n"
                     . "ID: <code>{$user->id}</code>\n"
                     . "Email: <code>{$user->email}</code>\n"
                     . "Amount: <b>{$amount} USDT</b>\n"
                     . "TXID: <code>{$txid}</code>\n"
                     . "Release: " . now()->addDay()->toDateTimeString();
            
            (new TelegramService())->sendMessage($message, $chatId);


    
            return back()->with([
                'unstake_success'  => true,
                'unstake_amount'   => $amount,
                'unstake_release'  => now()->addDay()->toDateTimeString(),
                'message'          => 'Unstake requested. Funds will be available in 24 hours.',
            ]);
        });
    }
    
    public function claim(Request $request)
    {
        $userId = auth()->id();
        $tz = 'Asia/Kuala_Lumpur';
        $now = Carbon::now($tz);
    
        Log::info("📌 Claim requested by user {$userId} at {$now->toDateTimeString()}");

    
        $lastMonday = $now->copy()->startOfWeek(Carbon::MONDAY)->subWeek();
        $shortWeekKey = $lastMonday->format('y') . 'W' . $lastMonday->format('W');
        $txid = "ws_{$shortWeekKey}_{$userId}";
    
        if (\App\Models\Payout::where('txid', $txid)->exists()) {
            Log::warning("🚫 Claim already exists for user {$userId}, txid={$txid}");
            return back()->with('error', 'You already claimed last week.');
        }
    
        Log::info("🚀 Dispatching DistributeUserStakingJob for user {$userId}");
        \App\Jobs\DistributeUserStakingJob::dispatch($userId);
    
        return back()->with('success', 'Your claim request has been queued. Please refresh in a moment to see the update.');
    }

}