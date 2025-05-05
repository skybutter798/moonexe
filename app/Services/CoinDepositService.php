<?php
namespace App\Services;

use App\Models\Deposit;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;
use Exception;

class CoinDepositService
{
    
    protected function truncate2(float $value): float
    {
        return floor($value * 100) / 100;
    }
    
    public function depositToUser(int $userId, float $amount, string $address = null, string $externalTxid = null)
    {
        Log::channel('admin')->info('[Service] depositToUser called', compact('userId','amount','address','externalTxid'));

        // Truncate to 2 decimals
        $amount = $this->truncate2($amount);
        Log::channel('admin')->info('[Service] Amount truncated to two decimals', ['amount' => $amount]);

        // Idempotency check
        if ($externalTxid && Deposit::where('external_txid', $externalTxid)->exists()) {
            Log::channel('admin')->warning('[Service] Duplicate external_txid, skipping', ['external_txid' => $externalTxid]);
            return Deposit::where('external_txid', $externalTxid)->first();
        }

        // Generate internal txid
        do {
            $txid = 'd_' . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Deposit::where('txid', $txid)->exists());

        // Create deposit record with truncated amount
        $deposit = Deposit::create([
            'user_id'        => $userId,
            'txid'           => $txid,
            'external_txid'  => $externalTxid,
            'amount'         => $amount,
            'trc20_address'  => $address,
            'status'         => 'Completed',
        ]);
        Log::channel('admin')->info('[Service] Deposit record created', ['deposit_id' => $deposit->id]);

        // Update wallet balance with truncated amount
        $wallet     = Wallet::firstOrNew(['user_id' => $userId]);
        $oldBalance = $wallet->cash_wallet ?? 0;
        $wallet->cash_wallet = $oldBalance + $amount;
        $wallet->save();
        Log::channel('admin')->info('[Service] Wallet updated', [
            'user_id'     => $userId,
            'old_balance' => $oldBalance,
            'new_balance' => $wallet->cash_wallet
        ]);

        return $deposit;
    }
}
