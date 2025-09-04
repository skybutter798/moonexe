<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PushTelegramTransferNotification extends Command
{
    protected $signature = 'push:telegram-transfer';
    protected $description = 'Manually push a Telegram notification for a terminated trading transfer';

    public function handle()
    {
        $user = \App\Models\User::find(223);
        $txid = 't_56186914';
        $netAmount = 952.0;
        $fee = 238.0;
        $chatId = '-1002643026089';

        $message = "<b>🚫 Account Terminated</b>\n"
                 . "User: <b>{$user->name}</b>\n"
                 . "ID: <code>{$user->id}</code>\n"
                 . "Email: <code>{$user->email}</code>\n"
                 . "Transferred: <b>" . number_format($netAmount, 2) . " USDT</b>\n"
                 . "Fee: <b>" . number_format($fee, 2) . " USDT</b>\n"
                 . "TXID: <code>{$txid}</code>\n"
                 . "Status: <b>Deactivated</b>";

        (new \App\Services\TelegramService())->sendMessage($message, $chatId);

        $this->info('Telegram message pushed successfully.');
    }
}