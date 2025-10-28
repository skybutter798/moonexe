<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramService;
use App\Models\User;
use Carbon\Carbon;

class RepushTerminateNotice extends Command
{
    protected $signature = 'repush:terminate {user_id} {transferred} {fee}';
    protected $description = 'Repush account termination notice to Telegram';

    public function handle()
    {
        $userId      = $this->argument('user_id');
        $transferred = $this->argument('transferred');
        $fee         = $this->argument('fee');

        $user = User::find($userId);
        if (!$user) {
            $this->error("User not found");
            return;
        }

        // Referral lookup
        $referralUser = User::find($user->referral);
        $referralName = $referralUser ? $referralUser->name : 'N/A';

        // Top referral lookup
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

        // Join date
        $joinDate = Carbon::parse($user->created_at)->format('Y-m-d H:i:s');

        // Telegram message
        $message = "<b>🚫 Account Terminated</b>\n"
                 . "User: <b>{$user->name}</b>\n"
                 . "ID: <code>{$user->id}</code>\n"
                 . "Email: <code>{$user->email}</code>\n"
                 . "Joined: <b>{$joinDate}</b>\n"
                 . "Referral: <b>{$referralName}</b>\n"
                 . "Top Referral: <b>{$topReferralName}</b>\n"
                 . "Transferred: <b>" . number_format($transferred, 2) . " USDT</b>\n"
                 . "Fee: <b>" . number_format($fee, 2) . " USDT</b>\n"
                 . "Status: <b>Deactivated</b>";

        (new TelegramService())->sendMessage($message, '-1002643026089');

        $this->info("Termination notice repushed for user {$userId}");
    }
}