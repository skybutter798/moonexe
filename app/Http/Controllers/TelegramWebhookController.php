<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Optionally log the update
        //Log::channel('telegram')->info('Telegram Webhook:', $request->all());

        // Access message or command sent
        $update = $request->all();

        if (isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $text = $update['message']['text'] ?? '';

            // Process commands, messages, etc.
            // Example: reply or trigger an action
        }

        return response()->json(['status' => 'ok']);
    }
}
