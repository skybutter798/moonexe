<?php
namespace App\Services;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessClaimPostActions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $orderId, public int $userId) {}

    public function handle()
    {
        $user  = \App\Models\User::find($this->userId);
        $order = \App\Models\Order::find($this->orderId);
        if (!$user || !$order) return;

        // Idempotency guard: if payout already exists for this order, skip.
        if (\App\Models\Payout::where('order_id', $order->id)->exists()) {
            return;
        }

        // --- Your original heavy logic moved here ---
        // 1) Campaign-only check
        $isCampaignUser = \DB::table('users')
            ->join('wallets', 'wallets.user_id', '=', 'users.id')
            ->whereBetween('users.created_at', ['2025-05-20 00:00:00', '2025-06-12 00:00:00'])
            ->where('users.status', 1)
            ->whereNotIn('users.id', function ($q) {
                $q->select('user_id')
                  ->from('transfers')
                  ->where('from_wallet', 'cash_wallet')
                  ->where('to_wallet', 'trading_wallet')
                  ->where('amount', 100)
                  ->where('remark', 'campaign');
            })
            ->where('users.id', $user->id)
            ->exists();

        // 2) Claim calculation
        $claimService    = new \App\Services\ClaimService();
        $claimAmounts    = $claimService->calculate($order);
        $baseClaimAmount = $claimAmounts['base'];
        $percentage      = $claimAmounts['percentage'] ?? 50;

        // 3) Create payout
        \App\Models\Payout::create([
            'user_id'  => $user->id,
            'order_id' => $order->id,
            'total'    => $order->earning,
            'txid'     => $order->txid,
            'actual'   => $baseClaimAmount,
            'type'     => 'payout',
            'wallet'   => 'earning',
            'status'   => 1,
        ]);

        // 4) Wallet updates + campaign reclaim
        $wallet = \App\Models\Wallet::where('user_id', $user->id)->first();
        if ($wallet) {
            $wallet->earning_wallet += $baseClaimAmount;

            if (!$isCampaignUser) {
                $wallet->trading_wallet += $order->buy;
            }
            $wallet->save();

            if ($isCampaignUser) {
                do {
                    $txid = 'b_' . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                } while (\App\Models\Transfer::where('txid', $txid)->exists());

                \App\Models\Transfer::create([
                    'user_id'     => $user->id,
                    'txid'        => $txid,
                    'from_wallet' => 'trading_wallet',
                    'to_wallet'   => 'system',
                    'amount'      => 100,
                    'status'      => 'Completed',
                    'remark'      => 'campaign',
                ]);
            }
        }

        // 5) Upline distribution + wallet recalc
        (new \App\Services\UplineDistributor())->distribute($order, $baseClaimAmount, $user);
        app(\App\Services\WalletRecalculator::class)->recalculate($user->id);

        Log::info("Post-claim processing completed for order {$order->id} / user {$user->id}");
        // Optionally: broadcast an event or send a notification to update UI if you use websockets.
    }
}