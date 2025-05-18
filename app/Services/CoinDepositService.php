<?php
namespace App\Services;

use App\Models\Deposit;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Services\TelegramService;
use App\Models\User;
use Carbon\Carbon;

class CoinDepositService
{
    
    protected function truncate2(float $value): float
    {
        return floor($value * 100) / 100;
    }
    
    public function depositToUser(int $userId, float $amount, string $address = null, string $externalTxid = null)
    {
        Log::channel('admin')->info('[Service] depositToUser called', compact('userId','amount','address','externalTxid'));
    
        $amount = $this->truncate2($amount);
    
        if ($externalTxid && Deposit::where('external_txid', $externalTxid)->exists()) {
            Log::channel('admin')->warning('[Service] Duplicate external_txid, skipping', ['external_txid' => $externalTxid]);
            return Deposit::where('external_txid', $externalTxid)->first();
        }
    
        // 🕒 START FEE DEDUCTION FROM MAY 20, 2025
        $now = Carbon::now('Asia/Kuala_Lumpur'); // or just Carbon::now() if default is MYT
        $feeStartDate = Carbon::create(2025, 5, 20, 0, 0, 0, 'Asia/Kuala_Lumpur');
    
        if ($now->greaterThanOrEqualTo($feeStartDate)) {
            $fee = min($amount, 7); // don't allow fee to exceed deposit
        } else {
            $fee = 0;
        }
    
        $netAmount = max(0, $amount - $fee);
    
        // Generate internal txid
        do {
            $txid = 'd_' . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Deposit::where('txid', $txid)->exists());
    
        // Create deposit record
        $deposit = Deposit::create([
            'user_id'        => $userId,
            'txid'           => $txid,
            'external_txid'  => $externalTxid,
            'amount'         => $netAmount,
            'fee'            => $fee,
            'trc20_address'  => $address,
            'status'         => 'Completed',
        ]);
    
        // Update wallet
        $wallet = Wallet::firstOrNew(['user_id' => $userId]);
        $wallet->cash_wallet = ($wallet->cash_wallet ?? 0) + $netAmount;
        $wallet->save();
    
        // Telegram notification
        try {
            $user = User::find($userId);
            $chatId = '-1002561840571';
    
            $message = "<b>Deposit Completed 💰</b>\n"
                     . "User ID: {$user->id}\n"
                     . "Name: {$user->name}\n"
                     . "Email: {$user->email}\n"
                     . "Gross Amount: {$amount} USDT\n"
                     . "Fee Deducted: {$fee} USDT\n"
                     . "Credited: {$netAmount} USDT\n"
                     . "Address: {$address}\n"
                     . "TXID: {$txid}";
    
            (new TelegramService())->sendMessage($message, $chatId);
        } catch (Exception $e) {
            Log::channel('admin')->error('[Service] Telegram notification failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    
        return $deposit;
    }

}
