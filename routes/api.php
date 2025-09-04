<?php

use Illuminate\Http\Request;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Models\WebhookPayment;
use App\Http\Controllers\Api\TransactionWebhookController;

use App\Models\Promotion;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/market-data', function () {
    $data = \Cache::get('market_data', []);
    if (empty($data)) {
        // Fallback to data from the database
        $marketData = \App\Models\MarketData::all();
        $data = [];
        foreach ($marketData as $entry) {
            $data[$entry->symbol] = [
                'bid'       => $entry->bid,
                'ask'       => $entry->ask,
                'mid'       => $entry->mid,
                'timestamp' => $entry->updated_at->toDateTimeString(),
            ];
        }
    }
    return response()->json($data);
});

Route::get('/promotion-info', function (Request $request) {
    $code = $request->query('code');
    $promotion = Promotion::where('code', strtoupper($code))->first();
    if ($promotion) {
        $left = $promotion->max_use - $promotion->used;
        return response()->json([
            'code' => $promotion->code,
            'left' => $left,
            'multiply' => $promotion->multiply,
        ]);
    }
    return response()->json(['error' => 'Promotion code not found'], 404);
});

/*Route::post('/response', function (Request $request) {
    Log::info('Received Webhook:', $request->all());
    return response()->json(['message' => 'Webhook received'], 200);
});*/

Route::post('/response', [WebhookController::class, 'handle']);
Route::post('/payment', [WebhookController::class, 'payment']);
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);
Route::post('/payhook', [WebhookController::class, 'receive']);

Route::get('/pair/{pair}/latest-payment', function ($pairId) {
    $payment = \App\Models\WebhookPayment::where('pair_id', $pairId)
                ->orderByDesc('created_at')
                ->first();

    if ($payment) {
        return response()->json([
            'success' => true,
            'pay_id' => $payment->pay_id,
            'amount' => $payment->amount,
            'method' => $payment->method,
        ]);
    }

    return response()->json(['success' => false]);
});

Route::post('/erc20response', function (Request $request) {
    $ip = $request->ip();
    Log::info('[ERC20 Response] IP: ' . $ip);
    Log::info('[ERC20 Response] Payload:', $request->all());

    return response()->json(['status' => 'ok']);
});

Route::post('/bep20response', function (Request $request) {
    $ip = $request->ip();
    Log::info('[BEP20 Response] IP: ' . $ip);
    Log::info('[BEP20 Response] Payload:', $request->all());

    return response()->json(['status' => 'ok']);
});

Route::post('/tx/receive', [TransactionWebhookController::class, 'receive']) ->middleware('restrict.ip');