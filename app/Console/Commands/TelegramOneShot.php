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
        $message = "<b>Withdrawal Request (Manual)</b>\n"
                 . "User ID: 211\n"
                 . "Name: Blueeyes\n"
                 . "Email: sj4eva@me.com\n"
                 . "Amount: 1031 USDT\n"
                 . "To Address: TM3Qw53yKgKALQeDvpwnTXyjbHNSSuMYJC\n"
                 . "TXID: w_97074048";

        $chatId = '-1002561840571';

        $telegram = new TelegramService();
        $result = $telegram->sendMessage($message, $chatId);

        $this->info($result ? '✅ Message sent successfully!' : '❌ Failed to send message.');
    }
}
