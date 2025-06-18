<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TransactionWebhookController extends Controller
{
    public function receive(Request $request)
    {
        // You can log or process the payload here
        \Log::info('Webhook received', $request->all());

        // Example payload validation (optional)
        $validated = $request->validate([
            'pay_id' => 'required|string',
            'trx_hash' => 'required|string',
            'amount_usd' => 'required|numeric',
            'amount_usdt' => 'required|numeric',
            'wallet' => 'required|string',
            'method' => 'required|string',
        ]);

        // TODO: save to DB or fire event, etc.

        return response()->json(['status' => 'ok']);
    }
}
