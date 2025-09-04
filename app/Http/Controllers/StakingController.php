<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Staking;
use App\Models\Wallet;
use App\Models\Transfer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet || $wallet->trading_wallet < $request->amount) {
                return back()->with('error', 'Insufficient trading wallet balance.');
            }

            // get last active row & new running balance
            $last = Staking::where('user_id', $user->id)
                ->where('status', 'active')
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

            // ✅ Determine DAILY rate from latest running balance using Settings
            $weeklyRateDecimal = $this->weeklyRateForBalance($newBalance); // e.g. 0.0140
            $dailyRateDecimal  = $weeklyRateDecimal / 7;                   // e.g. 0.0020

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
            // Lock wallet
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet) {
                return back()->with('error', 'Wallet not found.');
            }

            // Lock last active staking row to read current running balance
            $last = Staking::where('user_id', $user->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $currentBalance = (int) ($last->balance ?? 0);

            if ($currentBalance <= 0) {
                return back()->with('error', 'No active staking balance to unstake.');
            }
            if ($request->amount > $currentBalance) {
                return back()->with('error', 'Requested amount exceeds current staked balance.');
            }

            $newBalance = $currentBalance - (int) $request->amount;

            // Credit back to trading wallet
            $wallet->trading_wallet += (int) $request->amount;
            $wallet->save();

            // Append a negative staking ledger row (running balance)
            $txid = strtoupper(uniqid('UNSTK'));
            Staking::create([
                'user_id'  => $user->id,
                'txid'     => $txid,
                'amount'   => -1 * (int) $request->amount, // negative
                'interest' => 0,
                'balance'  => $newBalance,                 // running total after this op
                'status'   => 'active', // optional
            ]);

            // Log a transfer
            Transfer::create([
                'user_id'     => $user->id,
                'txid'        => $txid,
                'from_wallet' => 'staking_wallet',
                'to_wallet'   => 'trading_wallet',
                'amount'      => (int) $request->amount,
                'status'      => 'Completed',
                'remark'      => 'unstake',
            ]);

            return back()->with([
                'unstake_success' => true,
                'unstake_amount'  => (int) $request->amount,
            ]);
        });
    }
}