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
        $feeStartDate = Carbon::create(2026, 5, 20, 0, 0, 0, 'Asia/Kuala_Lumpur');
    
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
    
            // 1. Get direct referral
            $referralUser = User::find($user->referral);
            $referralName = $referralUser ? $referralUser->name : 'N/A';
    
            // 2. Get top referral (2 levels before reaching user ID 2 if possible)
            $current = $user;
            $prev1 = null;
            $prev2 = null;
            
            while ($current && $current->referral && $current->referral != 2) {
                $prev2 = $prev1;
                $prev1 = User::find($current->referral);
                $current = $prev1;
            
                // Stop if we�ve reached ID 2 now
                if ($current && $current->id == 2) {
                    break;
                }
            }
            
            // Prefer 2 levels before, fallback to 1
            $topReferralName = $prev2 ? $prev2->name : ($prev1 ? $prev1->name : 'N/A');

    
            $message = "<b>Deposit Completed 💰</b>\n"
                     . "User ID: {$user->id}\n"
                     . "Name: {$user->name}\n"
                     . "Email: {$user->email}\n"
                     . "Credited: {$netAmount} USDT\n"
                     . "Address: {$address}\n"
                     . "Referral: {$referralName}\n"
                     . "Top Referral: {$topReferralName}\n"
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
