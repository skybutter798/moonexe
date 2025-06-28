<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramService
{
    protected $botToken;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
    }

    public function sendMessage($message, $chatId = null)
    {
        $chatId = $chatId ?? config('services.telegram.chat_id');
    
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
    
        $response = Http::post($url, [
            'chat_id'    => $chatId,
            'text'       => $message,
            'parse_mode' => 'HTML',
        ]);
    
        \Log::channel('admin')->info('[TelegramService] Response', [
            'chat_id'  => $chatId,
            'url'      => $url,
            'status'   => $response->status(),
            'response' => $response->json(),
        ]);
    
        return $response->successful();
    }

}
