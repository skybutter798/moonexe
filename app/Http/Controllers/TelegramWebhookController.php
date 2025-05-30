<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $update = $request->all();
    
        if (isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $text = strtolower(trim($update['message']['text'] ?? ''));
            $botToken = env('TELEGRAM_BOT_TOKEN');
    
            if (strpos($text, 'chatid') !== false) {
                Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => "Chat ID is: {$chatId}"
                ]);
            }
    
            if (str_starts_with($text, 'check ')) {
                $username = trim(str_replace('check ', '', $text));
    
                // Optionally validate user exists
                $user = User::where('name', $username)->first();
                if (!$user) {
                    Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                        'chat_id' => $chatId,
                        'text' => "❌ User '{$username}' not found."
                    ]);
                    return response()->json(['status' => 'ok']);
                }
    
                // Run artisan command
                Artisan::call("check:real-wallet {$username}");
    
                
            }
        }
    
        return response()->json(['status' => 'ok']);
    }
}
