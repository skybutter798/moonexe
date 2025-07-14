<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\WebhookPayment;
use App\Models\Pair;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
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

        if (! isset($payload['type'])) {
            Log::channel('admin')->warning('[Webhook] Missing "type" field', $payload);
            return response()->json(['error' => 'Missing type'], 400);
        }

        if ($payload['type'] !== 'receive') {
            Log::channel('admin')->info("[Webhook] Ignored type={$payload['type']}");
            return response()->json(['message' => 'Ignored'], 200);
        }

        if (empty($payload['address'])) {
            Log::channel('admin')->error('[Webhook] Missing address in payload', $payload);
            return response()->json(['error' => 'Missing address'], 400);
        }

        $user = User::where('wallet_address', $payload['address'])->first();
        if (! $user) {
            Log::channel('admin')->warning('[Webhook] No user for address', ['address' => $payload['address']]);
            return response()->json(['error' => 'Unknown address'], 404);
        }
        
        try {
            $amount = (float) ($payload['amount'] ?? 0);
            if ($amount <= 0) {
                Log::channel('admin')->warning('[Webhook] Invalid amount', ['amount' => $payload['amount']]);
                return response()->json(['error' => 'Invalid amount'], 400);
            }

            $externalTxid = $payload['txid'] ?? null;
            $walletName = $payload['wallet_name'] ?? null;

            $deposit = $coinService->depositToUser(
                $user->id,
                (float) $payload['amount'],
                $payload['address'],
                $externalTxid,
                $walletName
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
    
    public function receive(Request $request)
    {
        $expectedToken = env('PAYMENT_WEBHOOK_TOKEN');
        $receivedToken = $request->bearerToken();
    
        if ($receivedToken !== $expectedToken) {
            Log::warning('Unauthorized webhook attempt', [
                'ip'     => $request->ip(),
                'token'  => $receivedToken,
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        $payload = $request->all();
        //Log::info('[Webhook] Received JSON:', $payload);
    
        if (($payload['status'] ?? null) !== 'Paid') {
            return response()->json(['status' => 'ignored'], 200);
        }
    
        // Step 1: Find random pair created today
        $today = now('Asia/Kuala_Lumpur')->toDateString();
        $pair = Pair::whereDate('created_at', $today)->inRandomOrder()->first();
    
        if (! $pair) {
            //Log::warning('Webhook received but no active pair found for today');
            return response()->json(['error' => 'No active pair'], 422);
        }
    
        try {
            DB::transaction(function () use ($payload, $pair) {
                $createdAt = isset($payload['created'])
                    ? Carbon::parse($payload['created'])->format('Y-m-d H:i:s')
                    : now()->format('Y-m-d H:i:s');
        
                $now = now()->format('Y-m-d H:i:s');
                $amount = (float) ($payload['amount'] ?? 0);
        
                // Insert payment
                DB::insert("
                    INSERT INTO webhook_payments
                        (pay_id, pair_id, method, amount, status, currency, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $payload['pay_id'] ?? uniqid('pay_'),
                    $pair->id,
                    $payload['method'] ?? null,
                    $amount,
                    $payload['status'] ?? null,
                    $payload['currency'] ?? 'USD', // default fallback
                    $createdAt,
                    $now
                ]);

        
                // Update pair volume
                $pair->volume += $amount;
                $pair->save();
            });
        
            \Artisan::call('pairs:update');
            return response()->json(['message' => 'Volume updated live'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Insert failed'], 500);
        }
    
    }
    
    public function payment(Request $request, CoinDepositService $coinService)
    {
        Log::channel('admin')->info('[Webhook] Received payload', $request->all());
    
        return response()->json(['status' => 'success'], 200);
    }

}
