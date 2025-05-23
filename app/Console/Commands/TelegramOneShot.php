<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramService;

class TelegramOneShot extends Command
{
    protected $signature = 'telegram:oneshot';
    protected $description = 'Send one-time Telegram message manually';

    public function handle()
    {
        $message = "<b>Deposit Completed 💰</b>\n"
                 . "User ID: 366\n"
                 . "Name: ailian\n"
                 . "Email: ailianchow@hotmail.com\n"
                 . "Credited: 100 USDT\n"
                 . "Address: TTYeGevcZxsNGQsPB2qyBCG1knrutgR1ez\n"
                 . "Referral: bhsoh\n"
                 . "Top Referral: Ronald\n"
                 . "TXID: d_38135763";

        $chatId = '-1002561840571';

        $telegram = new TelegramService();
        $result = $telegram->sendMessage($message, $chatId);

        $this->info($result ? '✅ Message sent successfully!' : '❌ Failed to send message.');
    }
}
