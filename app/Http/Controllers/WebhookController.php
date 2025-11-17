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
use Illuminate\Support\Facades\Artisan;
use App\Services\PairExpiryService;

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
            Log::channel('pair')->warning('[Webhook] Unauthorized attempt', [
                'ip'    => $request->ip(),
                'token' => $receivedToken,
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        $payload = $request->all();
    
        // 🔥 Debug who is sending
        Log::channel('pair')->info('[Webhook][DEBUG Incoming]', [
            'ip'      => $request->ip(),
            'agent'   => $request->header('User-Agent'),
            'payload' => $payload,
        ]);
    
        // 🔥 1. BLOCK internal update pushes to avoid infinite loop
        if (($payload['source'] ?? null) === 'internal-update') {
            Log::channel('pair')->info('[Webhook] Skipped internal-update webhook');
            return response()->json(['status' => 'ok'], 200);
        }
    
        Log::channel('pair')->info('[Webhook] ✅ Authorized webhook received', [
            'ip'       => $request->ip(),
            'pay_id'   => $payload['pay_id'] ?? null,
            'amount'   => $payload['amount'] ?? null,
            'currency' => $payload['currency'] ?? 'USD',
            'method'   => $payload['method'] ?? null,
            'status'   => $payload['status'] ?? null,
        ]);
    
        if (($payload['status'] ?? null) !== 'Paid') {
            Log::channel('pair')->info('[Webhook] Ignored non-paid transaction', [
                'status' => $payload['status'] ?? null,
            ]);
            return response()->json(['status' => 'ignored'], 200);
        }
    
        $currencyCode = strtoupper(trim($payload['currency'] ?? 'USD'));
    
        $pair = \App\Models\Pair::whereHas('currency', function ($q) use ($currencyCode) {
            $q->whereRaw('LOWER(c_name) = ?', [strtolower($currencyCode)]);
        })
        ->latest('created_at')
        ->first();
    
        if (!$pair) {
            Log::channel('pair')->warning('[Webhook] No active pair found', [
                'currency' => $currencyCode,
                'payload'  => $payload,
            ]);
            return response()->json(['error' => 'No pair found for currency'], 422);
        }
    
        $createdAtUtc = Carbon::parse($pair->created_at)->timezone('UTC');
        $nowUtc       = Carbon::now('UTC');
        $gateMinutes  = (int) $pair->gate_time;
    
        $effectiveMinutes = $gateMinutes < 300 ? 300 : $gateMinutes - 120;
    
        $cutoffTimeUtc = $createdAtUtc->copy()->addMinutes($effectiveMinutes);
        $expiryTimeUtc = $createdAtUtc->copy()->addMinutes($gateMinutes);
    
        if ($nowUtc->greaterThanOrEqualTo($expiryTimeUtc)) {
            Log::channel('pair')->info('[Webhook] Pair fully expired, skipping volume update', [
                'pair_id'      => $pair->id,
                'currency'     => $currencyCode,
                'created_at'   => $pair->created_at,
                'gate_minutes' => $gateMinutes,
                'expiry_time'  => $expiryTimeUtc->toDateTimeString(),
                'now'          => $nowUtc->toDateTimeString(),
            ]);
            return response()->json(['status' => 'skipped (pair expired)'], 200);
        }
    
        if ($nowUtc->greaterThanOrEqualTo($cutoffTimeUtc)) {
            Log::channel('pair')->info('[Webhook] Pair near expiry, skipping volume update', [
                'pair_id'      => $pair->id,
                'currency'     => $currencyCode,
                'created_at'   => $pair->created_at,
                'gate_minutes' => $gateMinutes,
                'cutoff_time'  => $cutoffTimeUtc->toDateTimeString(),
                'now'          => $nowUtc->toDateTimeString(),
            ]);
            return response()->json(['status' => 'skipped (pair near expiry)'], 200);
        }
    
        try {
            DB::transaction(function () use ($payload, $pair) {
    
                $createdAt = isset($payload['created'])
                    ? Carbon::parse($payload['created'])->format('Y-m-d H:i:s')
                    : now()->format('Y-m-d H:i:s');
    
                $now = now()->format('Y-m-d H:i:s');
                $amount = (float) ($payload['amount'] ?? 0);
    
                Log::channel('pair')->info('[Webhook] Recording new payment', [
                    'pair_id'    => $pair->id,
                    'amount_usd' => $amount,
                    'currency'   => $payload['currency'] ?? 'USD',
                    'method'     => $payload['method'] ?? null,
                    'created_at' => $createdAt,
                ]);
    
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
                    $now,
                ]);
            });
    
            Log::channel('pair')->info('[Webhook] Triggering pairs:update');
    
            // 🔥 include source tag to stop loop
            Artisan::call('pairs:update', [
                '--pair_volume' => $payload['pair_volume'] ?? null,
                '--currency'    => $payload['currency'] ?? null,
                '--source'      => 'webhook', // optional
            ]);
    
            Log::channel('pair')->info('[Webhook] Done.');
            return response()->json(['message' => 'Payment recorded & update triggered'], 200);
    
        } catch (\Exception $e) {
            Log::channel('pair')->error('[Webhook] Insert failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Insert failed'], 500);
        }
    }

    
    public function checkPairExpiry(Request $request, PairExpiryService $expiry)
    {
        try {
            $currency = $request->query('currency');
            if (!$currency) {
                return response()->json(['status' => 'error', 'reason' => 'currency is required'], 200);
            }
    
            $probe = (float) $request->query('probe', 0);
            $res   = $expiry->check($currency, $probe);
    
            // Always 200 so callers never see a transport error; they act on "status"
            return response()->json($res, 200);
    
        } catch (\Throwable $e) {
            \Log::channel('pair')->error('[PairCheck] Exception', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'status' => 'error',
                'reason' => 'internal error',
            ], 200);
        }
    }
    
    public function payment(Request $request, CoinDepositService $coinService)
    {
        Log::channel('admin')->info('[Webhook] Received payload', $request->all());
    
        return response()->json(['status' => 'success'], 200);
    }
    
    public function trxPayment(Request $request, CoinDepositService $coinService)
    {
        Log::channel('trx')->info('[TRX Webhook] Raw payload', [
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
            Log::channel('trx')->warning('[TRX Webhook] Invalid or missing payload structure', $payload);
            return response()->json(['error' => 'Invalid structure'], 400);
        }
    
        foreach ($payload['data'] as $tx) {
            $hash  = $tx['hash'] ?? null;
            $from  = $tx['from'] ?? null;
            $to    = $tx['to'] ?? null;
            $value = $tx['value'] ?? null;
            $token = $tx['tokenCode'] ?? null;
    
            if (!$to || !$value || !$token || !$hash) {
                Log::channel('trx')->warning('[TRX Webhook] Skipped TX due to missing fields', $tx);
                continue;
            }
    
            $user = User::where('trx_address', $to)->first();
            if (!$user) {
                Log::channel('trx')->warning('[TRX Webhook] No user found for TRX address', ['address' => $to]);
                continue;
            }
    
            try {
                $amount = (float) $value;
                if ($amount <= 0) {
                    Log::channel('trx')->warning('[TRX Webhook] Invalid amount', ['amount' => $value]);
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
                    Log::channel('trx')->info('[TRX Webhook] Duplicate TXID ignored', [
                        'user_id' => $user->id,
                        'txid'    => $hash,
                    ]);
                } else {
                    Log::channel('trx')->info('[TRX Webhook] Deposit successful', [
                        'user_id'    => $user->id,
                        'amount'     => $amount,
                        'hash'       => $hash,
                        'deposit_id' => $deposit->id,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::channel('trx')->error('[TRX Webhook] Exception during deposit', [
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                    'txid'    => $hash,
                ]);
            }
        }
    
        Log::channel('trx')->info('[TRX Webhook] Processing completed successfully');
        return response()->json(['status' => 'processed'], 200);
    }

}
