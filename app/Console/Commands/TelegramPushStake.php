<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Staking;
use App\Models\User;
use App\Services\TelegramService;
use Carbon\Carbon;

class TelegramPushStake extends Command
{
    // Usage example:
    // php artisan telegram:push-stake UNSTK6906D7F042077
    // php artisan telegram:push-stake STK684F33A12D390 --chat=-1002720623603

    protected $signature = 'telegram:push-stake
        {txid* : One or more staking TXIDs (e.g. STK684F33A12D390 or UNSTK6906D7F042077)}
        {--chat=-4807439791 : Telegram chat ID to send to}';

    protected $description = 'Re-send missing Stake or Unstake Telegram messages manually by TXID.';

    public function handle()
    {
        $chatId = $this->option('chat');
        $txids  = (array) $this->argument('txid');
        $telegram = new TelegramService();
        $sent = 0;

        foreach ($txids as $txid) {
            $staking = Staking::where('txid', $txid)->first();

            if (!$staking) {
                $this->error("TXID not found: {$txid}");
                continue;
            }

            $user = User::find($staking->user_id);
            if (!$user) {
                $this->error("No user found for TXID: {$txid}");
                continue;
            }

            // Build message depending on stake / unstake type
            $isUnstake = str_starts_with($staking->txid, 'UNSTK');
            $amount = abs((int) $staking->amount);

            if ($isUnstake) {
                $releaseTime = Carbon::parse($staking->created_at)->addDay()->toDateTimeString();
                $message = "<b>📥 Unstake</b>\n"
                         . "User: <b>{$user->name}</b>\n"
                         . "ID: <code>{$user->id}</code>\n"
                         . "Email: <code>{$user->email}</code>\n"
                         . "Amount: <b>{$amount} USDT</b>\n"
                         . "TXID: <code>{$txid}</code>\n"
                         . "Release: {$releaseTime}";
            } else {
                $message = "<b>📥 New Stake</b>\n"
                         . "ID: <code>{$user->id}</code>\n"
                         . "User: <b>{$user->name}</b>\n"
                         . "Email: <b>{$user->email}</b>\n"
                         . "Amount: <b>{$amount} USDT</b>\n"
                         . "TXID: <code>{$txid}</code>";
            }

            $ok = $telegram->sendMessage($message, $chatId);
            $this->info(($ok ? '✅' : '❌') . " Sent: {$txid}");
            if ($ok) $sent++;
        }

        $this->info("Done. Successfully sent: {$sent}/" . count($txids));
        return 0;
    }
}