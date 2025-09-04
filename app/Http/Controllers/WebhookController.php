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
        Log::channel('admin')->info('[Webhook] Received payload', $request->all());
    
        $payload = $request->all();
        
        if (!isset($payload['type'])) {
            Log::channel('admin')->warning('[Webhook] Missing "type" field', $payload);
            return response()->json(['message' => 'OK - Missing type'], 200);
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
            $walletName   = $payload['wallet_name'] ?? null;
    
            // ✅ SUPPORT THE NEW RETURN FORMAT
            [$deposit, $isNew] = $coinService->depositToUser(
                $user->id,
                $amount,
                $payload['address'],
                $externalTxid,
                $walletName
            );
    
            if (! $isNew) {
                Log::channel('admin')->info('[Webhook] Duplicate TXID - already processed', [
                    'txid' => $externalTxid,
                    'user_id' => $user->id,
                ]);
                return response()->json(['message' => 'Duplicate TXID'], 200);
            }
    
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
    
        $currencyCode = strtoupper(trim($payload['currency'] ?? 'USD'));
    
        // ✅ Find the latest matching pair by currency (no date filter)
        $pair = \App\Models\Pair::whereHas('currency', function ($q) use ($currencyCode) {
                $q->whereRaw('LOWER(c_name) = ?', [strtolower($currencyCode)]);
            })
            ->latest('created_at')
            ->first();
    
        if (! $pair) {
            Log::warning("Webhook received but no pair found for currency: {$currencyCode}");
            return response()->json(['error' => 'No pair found for currency'], 422);
        }
    
        try {
            DB::transaction(function () use ($payload, $pair) {
                $createdAt = isset($payload['created'])
                    ? Carbon::parse($payload['created'])->format('Y-m-d H:i:s')
                    : now()->format('Y-m-d H:i:s');
    
                $now = now()->format('Y-m-d H:i:s');
                $amount = (float) ($payload['amount'] ?? 0);
    
                // ✅ Insert payment with correct pair ID
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
                    $payload['currency'] ?? 'USD',
                    $createdAt,
                    $now
                ]);
    
                // Optional: add amount directly to pair here
                $pair->volume += $amount;
                $pair->save();
            });
    
            \Artisan::call('pairs:update');
            return response()->json(['message' => 'Volume updated live'], 200);
        } catch (\Exception $e) {
            Log::error('Webhook insert failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Insert failed'], 500);
        }
    }
    
    public function payment(Request $request, CoinDepositService $coinService)
    {
        Log::channel('admin')->info('[Webhook] Received payload', $request->all());
    
        return response()->json(['status' => 'success'], 200);
    }
    
    public function trxPayment(Request $request, CoinDepositService $coinService)
    {
        Log::info('[TRX Webhook] Raw payload', [
            'headers'   => $request->headers->all(),
            'payload'   => $request->all(),
            'raw'       => $request->getContent(),
            'ip'        => $request->ip(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    
        $payload = $request->all();
    
        if (
            !isset($payload['status']) || $payload['status'] !== 'success' ||
            !isset($payload['data']) || !is_array($payload['data']) || empty($payload['data'])
        ) {
            Log::warning('[TRX Webhook] Invalid or missing payload structure', $payload);
            return response()->json(['error' => 'Invalid structure'], 400);
        }
    
        foreach ($payload['data'] as $tx) {
            $hash  = $tx['hash'] ?? null;
            $from  = $tx['from'] ?? null;
            $to    = $tx['to'] ?? null;
            $value = $tx['value'] ?? null;
            $token = $tx['tokenCode'] ?? null;
    
            if (!$to || !$value || !$token || !$hash) {
                Log::warning('[TRX Webhook] Skipped TX due to missing fields', $tx);
                continue;
            }
    
            $user = User::where('trx_address', $to)->first();
            if (!$user) {
                Log::warning('[TRX Webhook] No user found for TRX address', ['address' => $to]);
                continue;
            }
    
            try {
                $amount = (float) $value;
                if ($amount <= 0) {
                    Log::warning('[TRX Webhook] Invalid amount', ['amount' => $value]);
                    continue;
                }
    
                [$deposit, $isNew] = $coinService->depositToUser(
                    $user->id,
                    $amount,
                    $to,
                    $hash,
                    'TRX-USDT'
                );
    
                if (!$isNew) {
                    Log::info('[TRX Webhook] Duplicate TXID ignored', [
                        'user_id' => $user->id,
                        'txid'    => $hash,
                    ]);
                } else {
                    Log::info('[TRX Webhook] Deposit successful', [
                        'user_id'    => $user->id,
                        'amount'     => $amount,
                        'hash'       => $hash,
                        'deposit_id' => $deposit->id,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('[TRX Webhook] Exception during deposit', [
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                    'txid'    => $hash,
                ]);
            }
        }
    
        return response()->json(['status' => 'processed'], 200);
    }

}
