<?php

namespace App\Services;

use App\Models\User;
use App\Models\Payout;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;

class OptimizedUplineDistributor
{
    protected array $userTree;

    public function __construct(array $userTree)
    {
        $this->userTree = $userTree; // Output from BatchUserRangeCalculator
    }

    public function distribute($order, $baseClaimAmount, $user)
    {
        $currentMatching = 0;
        $currentUser = $user;

        while ($currentUser->referral) {
            $uplineId = $currentUser->referral;
            $uplineData = $this->userTree[$uplineId] ?? null;

            if (!$uplineData) break;

            $uplineMatching = $uplineData['matching_percentage'];
            $percentageDiff = $uplineMatching - $currentMatching;

            if ($percentageDiff > 0) {
                $amount = $baseClaimAmount * $percentageDiff;

                $wallet = $uplineData['wallet'];
                $wallet->affiliates_wallet += $amount;
                $wallet->save();

                do {
                    $txid = 'p_' . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
                } while (Payout::where('txid', $txid)->exists());

                Payout::create([
                    'user_id' => $uplineId,
                    'order_id' => $order->id,
                    'txid' => $txid,
                    'total' => $amount,
                    'actual' => $amount,
                    'type' => 'payout',
                    'wallet' => 'affiliates',
                    'status' => 1,
                ]);

                $currentMatching = $uplineMatching;
            }

            $currentUser = new User([
                'id' => $uplineId,
                'referral' => $uplineData['referral']
            ]);
        }
    }
}
