<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramService;
use App\Models\User;
use App\Models\Withdrawal;
use App\Models\Staking;

class TelegramMessage extends Command
{
    protected $signature = 'telegram:msg
        {id : The ID of the withdrawal or stake}
        {--type=withdrawal : Message type (withdrawal|stake|unstake)}
        {--chat= : (Optional) Override Telegram chat ID}';

    protected $description = 'Send a withdrawal, stake, or unstake message to Telegram.';

    public function handle()
    {
        $id   = $this->argument('id');
        $type = $this->option('type');

        // ✅ Default chat IDs depending on type
        if (in_array($type, ['stake', 'unstake'])) {
            $chatId = $this->option('chat') ?: '-4807439791';  // staking channel
        } else {
            $chatId = $this->option('chat') ?: '-1002643026089'; // withdrawal channel
        }

        $telegram = new TelegramService();

        if ($type === 'withdrawal') {
            $withdrawal = Withdrawal::with('user')->find($id);
            if (!$withdrawal) {
                $this->error("❌ Withdrawal ID {$id} not found.");
                return 1;
            }

            $user = $withdrawal->user;

            // Direct referral
            $referralUser = User::find($user->referral);
            $referralName = $referralUser ? $referralUser->name : 'N/A';

            // Top referral
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

            $requestAmount = $withdrawal->amount + $withdrawal->fee;

            $message = "Withdrawal Request 🧾\n"
                     . "User ID: {$user->id}\n"
                     . "Name: {$user->name}\n"
                     . "Email: {$user->email}\n"
                     . "Request: {$requestAmount} USDT\n"
                     . "Fee: {$withdrawal->fee} USDT\n"
                     . "Net Amount: {$withdrawal->amount} USDT\n"
                     . "To Address: {$withdrawal->trc20_address}\n"
                     . "Referral: {$referralName}\n"
                     . "Top Referral: {$topReferralName}\n"
                     . "TXID: {$withdrawal->txid}";
        }

        elseif ($type === 'stake') {
            $stake = Staking::with('user')->find($id);
            if (!$stake) {
                $this->error("❌ Stake ID {$id} not found.");
                return 1;
            }

            $user = $stake->user;

            $message = "📥 New Stake\n"
                     . "ID: {$user->id}\n"
                     . "User: {$user->name}\n"
                     . "Email: {$user->email}\n"
                     . "Amount: {$stake->amount} USDT\n"
                     . "TXID: {$stake->txid}";
        }

        elseif ($type === 'unstake') {
            $unstake = Staking::with('user')->find($id);
            if (!$unstake) {
                $this->error("❌ Unstake ID {$id} not found.");
                return 1;
            }

            $user = $unstake->user;

            $releaseTime = now()->addDay()->toDateTimeString();

            $message = "📤 Unstake\n"
                     . "User: {$user->name}\n"
                     . "ID: {$user->id}\n"
                     . "Email: {$user->email}\n"
                     . "Amount: " . abs($unstake->amount) . " USDT\n"
                     . "TXID: {$unstake->txid}\n"
                     . "Release: {$releaseTime}";
        }

        else {
            $this->error("❌ Unknown type: {$type}");
            return 1;
        }

        // ✅ Send to correct chat
        $ok = $telegram->sendMessage($message, $chatId);
        $this->info($ok ? "✅ Sent to {$chatId}" : '❌ Failed');
        return $ok ? 0 : 1;
    }
}
