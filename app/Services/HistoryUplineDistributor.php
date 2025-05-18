<?php

namespace App\Services;

use App\Models\User;
use App\Models\Payout;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;
use App\Services\UserRangeCalculator;

class HistoryUplineDistributor
{
    protected $userRangeCalculator;

    public function __construct()
    {
        $this->userRangeCalculator = new UserRangeCalculator();
    }

    public function distribute($order, $baseClaimAmount, $user, $createdAt)
    {
        Log::channel('payout')->info("-------> Starting distribution for User {$user->id} with base claim amount: {$baseClaimAmount}");

        $currentMatching = 0;
        $currentUser = $user;

        // Loop through the referral chain until no further upline is found.
        while ($currentUser->referral) {
            $upline = User::find($currentUser->referral);
            if (!$upline) {
                Log::warning("UplineDistributor: Referral user not found for User {$currentUser->id}");
                break;
            }

            $uplineCalculation = $this->userRangeCalculator->calculate($upline);
            $uplineMatching = $uplineCalculation['matching_percentage'];

            // Calculate the difference between the upline matching percentage and the current one.
            $percentageDiff = $uplineMatching - $currentMatching;

            // Only log when there is a positive difference and a payout is processed.
            if ($percentageDiff > 0) {
                $amount = $baseClaimAmount * ($percentageDiff);

                $wallet = Wallet::where('user_id', $upline->id)->first();
                if ($wallet) {
                    $wallet->affiliates_wallet += $amount;
                    $wallet->save();
                } else {
                    Log::warning("UplineDistributor: Wallet not found for upline User {$upline->id}");
                }

                do {
                    $txid = 'p_' . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
                } while (Payout::where('txid', $txid)->exists());

                $payout = new Payout();
                $payout->user_id    = $upline->id;
                $payout->order_id   = $order->id;
                $payout->txid       = $txid;
                $payout->total      = $amount;
                $payout->actual     = $amount;
                $payout->type       = 'payout';
                $payout->wallet     = 'affiliates';
                $payout->status     = 1;
                $payout->created_at = $createdAt;
                $payout->updated_at = $createdAt;
                $payout->save();

                
                $currentMatching = $uplineMatching;

                Log::channel('payout')->info("UplineDistributor: User {$upline->id} received affiliate payout of {$amount} (TXID: {$txid})");
            }
            
            $currentUser = $upline;
        }

        Log::channel('payout')->info("UplineDistributor: Distribution completed for base user {$user->id}");
    }
    
    public function distributeDirect($transfer, $newPackage, $user)
    {
        Log::channel('payout')->info("-------> UplineDistributor (Direct): User {$user->id} with range: {$newPackage->min} - {$newPackage->max}");
    
        // Set your direct percentage to 0, as you are at the bottom.
        $currentDirect = 0;
        $currentUser = $user;
    
        // Loop through the referral chain
        while ($currentUser->referral) {
            $upline = User::find($currentUser->referral);
            if (!$upline) {
                Log::warning("UplineDistributor (Direct): Referral user not found for User {$currentUser->id}");
                break;
            }
    
            // Get the upline's direct percentage using the UserRangeCalculator.
            $uplineCalculation = $this->userRangeCalculator->calculate($upline);
            $uplineDirect = $uplineCalculation['direct_percentage'];
    
            // Calculate the difference between upline's direct percentage and the current level.
            $percentageDiff = $uplineDirect - $currentDirect;
    
            if ($percentageDiff > 0) {
                // Calculate payout amount: package eshare * difference
                $payoutAmount = $transfer->amount * $percentageDiff;

                // Create payout record
                Payout::create([
                    'user_id'  => $upline->id,
                    'order_id' => $transfer->id,
                    'txid'     => $transfer->txid,
                    'total'    => $payoutAmount,
                    'actual'   => $payoutAmount,
                    'type'     => 'direct',
                    'wallet'   => 'affiliates',
                    'status'   => 1,
                    'created_at' => $createdAtPlusOneDay,
                    'updated_at' => $createdAtPlusOneDay,
                ]);
    
                // Update upline wallet
                $wallet = Wallet::where('user_id', $upline->id)->first();
                if ($wallet) {
                    $wallet->affiliates_wallet += $payoutAmount;
                    $wallet->save();
                }
                
                $currentDirect = $uplineDirect;
    
                Log::channel('payout')->info("UplineDistributor (Direct): User {$upline->id} received direct payout of {$payoutAmount} with {$uplineDirect}% (TXID: {$transfer->txid})");
            }
            // Move up one level.
            
            $currentUser = $upline;
        }
    
        Log::channel('payout')->info("UplineDistributor (Direct): Direct distribution completed for base User {$user->id}");
    }

}
