<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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