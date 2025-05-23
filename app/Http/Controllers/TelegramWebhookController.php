<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $update = $request->all();
    
        if (isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $text = strtolower($update['message']['text'] ?? '');
    
            if (strpos($text, 'chatid') !== false) {
                $botToken = env('TELEGRAM_BOT_TOKEN');
    
                Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => "Chat ID is: {$chatId}"
                ]);
            }
        }
    
        return response()->json(['status' => 'ok']);
    }
}
