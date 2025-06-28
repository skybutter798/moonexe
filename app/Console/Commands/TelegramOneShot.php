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
                 . "User ID: 616\n"
                 . "Name: Carol\n"
                 . "Email: carolphong18@gmail.com\n"
                 . "Credited: 100.00 USDT\n"
                 . "Address: TNyJb5zh32tkhtED3GmShV1SjyUJRDgFg2\n"
                 . "Referral: Ng Siew Cheng\n"
                 . "Top Referral: Ronald\n"
                 . "TXID: d_16916616";

        $chatId = '-1002643026089';

        $telegram = new TelegramService();
        $result = $telegram->sendMessage($message, $chatId);

        $this->info($result ? '✅ Message sent successfully!' : '❌ Failed to send message.');
    }
}
