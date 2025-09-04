<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\TelegramService;

class ResendWithdrawalMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Example:
     * php artisan resend:withdrawal 490 w_97745839
     */
    protected $signature = 'resend:withdrawal {user_id} {txid}';

    /**
     * The console command description.
     */
    protected $description = 'Resend Telegram withdrawal message for a given user and TXID';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $txid   = $this->argument('txid');

        $withdrawal = Withdrawal::where('user_id', $userId)
            ->where('txid', $txid)
            ->first();

        if (!$withdrawal) {
            $this->error("Withdrawal not found for user_id {$userId}, txid {$txid}");
            return 1;
        }

        $user = User::find($userId);
        if (!$user) {
            $this->error("User {$userId} not found");
            return 1;
        }

        // Referral info
        $referralUser = User::find($user->referral);
        $referralName = $referralUser ? $referralUser->name : 'N/A';

        $current = $user;
        $prev1 = null;
        $prev2 = null;
        while ($current && $current->referral && $current->referral != 2) {
            $prev2 = $prev1;
            $prev1 = User::find($current->referral);
            $current = $prev1;
            if ($current && $current->id == 2) {
                break;
            }
        }
        $topReferralName = $prev2 ? $prev2->name : ($prev1 ? $prev1->name : 'N/A');

        $message = "<b>Withdrawal Request 🧾</b>\n"
                 . "User ID: {$user->id}\n"
                 . "Name: {$user->name}\n"
                 . "Email: {$user->email}\n"
                 . "Request: " . ($withdrawal->amount + $withdrawal->fee) . " USDT\n"
                 . "Fee: {$withdrawal->fee} USDT\n"
                 . "Net Amount: {$withdrawal->amount} USDT\n"
                 . "To Address: {$withdrawal->trc20_address}\n"
                 . "Referral: {$referralName}\n"
                 . "Top Referral: {$topReferralName}\n"
                 . "TXID: {$withdrawal->txid}";

        $chatId = '-1002643026089';
        if (!in_array($user->id, [1, 2])) {
            (new TelegramService())->sendMessage($message, $chatId);
        }

        $this->info("Resent Telegram message for withdrawal {$txid} (user {$userId})");
        return 0;
    }
}
