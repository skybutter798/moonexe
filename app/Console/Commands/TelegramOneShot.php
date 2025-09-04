<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Withdrawal;
use App\Models\User;
use App\Services\TelegramService;

class TelegramOneShot extends Command
{
    // Usage: php artisan telegram:push-approved w_59826891 w_98953102
    protected $signature = 'telegram:push-approved
        {txid* : One or more withdrawal TXIDs (e.g. w_59826891)}
        {--chat=-1002643026089 : Telegram chat ID}
        {--force : Send even if status is not Completed}';

    protected $description = 'Re-send APPROVED withdrawal Telegram messages by TXID.';

    public function handle()
    {
        $chatId = $this->option('chat');
        $txids  = (array) $this->argument('txid');
        $force  = (bool) $this->option('force');

        $telegram = new TelegramService();
        $sent = 0;

        foreach ($txids as $txid) {
            $w = Withdrawal::where('txid', $txid)->first();
            if (!$w) { $this->error("TXID not found: {$txid}"); continue; }

            if ($w->status !== 'Completed' && !$force) {
                $this->warn("Skip {$txid}: status is '{$w->status}'. Use --force to send anyway.");
                continue;
            }

            $user = User::find($w->user_id);
            if (!$user) { $this->error("No user for TXID {$txid}"); continue; }

            // Direct referral
            $referralUser  = User::find($user->referral);
            $referralName  = $referralUser ? $referralUser->name : 'N/A';

            // Top referral (max 2 levels up; stop at ID 2)
            $current = $user; $prev1 = null; $prev2 = null;
            while ($current && $current->referral && $current->referral != 2) {
                $prev2 = $prev1;
                $prev1 = User::find($current->referral);
                $current = $prev1;
                if ($current && $current->id == 2) break;
            }
            $topReferralName = $prev2 ? $prev2->name : ($prev1 ? $prev1->name : 'N/A');

            $scanLink = "https://tronscan.org/#/address/{$w->trc20_address}";

            // EXACT format you want (no HTML <a>, show URL in parentheses)
            $message = "Withdrawal Approved ✅\n"
                     . "User ID: {$user->id}\n"
                     . "Name: {$user->name}\n"
                     . "Email: {$user->email}\n"
                     . "Amount: {$w->amount} USDT\n"
                     . "Fee: {$w->fee} USDT\n"
                     . "Referral: {$referralName}\n"
                     . "Top Referral: {$topReferralName}\n"
                     . "Address: {$w->trc20_address} ({$scanLink})\n"
                     . "TXID: {$w->txid}";

            $ok = $telegram->sendMessage($message, $chatId);
            $this->info(($ok ? '✅' : '❌') . " Sent: {$txid}");
            if ($ok) $sent++;
        }

        $this->info("Done. Successfully sent: {$sent}/" . count($txids));
        return 0;
    }
}
