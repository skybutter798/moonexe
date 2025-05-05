<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Services\CoinDepositService;

class WebhookController extends Controller
{
    public function handle(Request $request, CoinDepositService $coinService)
    {
        // 1. Log raw payload arrival
        Log::channel('admin')->info('[Webhook] Received payload', $request->all());

        $payload = $request->all();
        
        if (!isset($payload['type'])) {
            Log::channel('admin')->warning('[Webhook] Missing "type" field', $payload);
            return response()->json(['message' => 'OK - Missing type'], 200);
        }

        // 2. Log and exit if no type
        if (! isset($payload['type'])) {
            Log::channel('admin')->warning('[Webhook] Missing "type" field', $payload);
            return response()->json(['error' => 'Missing type'], 400);
        }

        // 3. Only care about "receive"
        if ($payload['type'] !== 'receive') {
            Log::channel('admin')->info("[Webhook] Ignored type={$payload['type']}");
            return response()->json(['message' => 'Ignored'], 200);
        }

        // 4. Log missing address
        if (empty($payload['address'])) {
            Log::channel('admin')->error('[Webhook] Missing address in payload', $payload);
            return response()->json(['error' => 'Missing address'], 400);
        }

        // 5. Find user
        $user = User::where('wallet_address', $payload['address'])->first();
        if (! $user) {
            Log::channel('admin')->warning('[Webhook] No user for address', ['address' => $payload['address']]);
            return response()->json(['error' => 'Unknown address'], 404);
        }
        
        // 6. Safely invoke the deposit service
        try {
            $amount = (float) ($payload['amount'] ?? 0);
            if ($amount <= 0) {
                Log::channel('admin')->warning('[Webhook] Invalid amount', ['amount' => $payload['amount']]);
                return response()->json(['error' => 'Invalid amount'], 400);
            }

            $externalTxid = $payload['txid'] ?? null;

            $deposit = $coinService->depositToUser(
                $user->id,
                (float) $payload['amount'],
                $payload['address'],
                $externalTxid
            );

            Log::channel('admin')->info('[Webhook] Deposit processed successfully', [
                'deposit_id' => $deposit->id,
                'user_id'    => $user->id,
                'amount'     => $amount,
            ]);

            return response()->json(['message' => 'Deposit processed'], 200);
        } catch (\Exception $e) {
            Log::channel('admin')->error('[Webhook] Exception in deposit processing', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Server error'], 500);
        }
    }
}
