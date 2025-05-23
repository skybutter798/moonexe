<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Promotion;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeMail;

use App\Models\Wallet;
use App\Models\Transfer;


class RegisterController extends Controller
{
    use RegistersUsers;
    
    protected $redirectTo = '/user-dashboard/dashboard';
    
    public function __construct()
    {
        $this->middleware('guest');
    }
    
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name'          => ['required', 'string', 'max:255', 'unique:users'],
            'email'         => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'      => ['required', 'string', 'min:8', 'confirmed'],
            'referral_code' => ['required', 'string', 'exists:users,referral_code'],
            'promotion_code'=> ['nullable', 'string', 'exists:promotions,code'],
        ]);
    }
    
    protected function create(array $data)
    {
        // Find the upline user using the referral code provided in the form
        $upline = User::where('referral_code', $data['referral_code'])->first();
    
        // Generate a unique referral code for the new user
        $newReferralCode = $this->generateUniqueReferralCode();
        // Build the referral link using your app URL (adjust if necessary)
        $newReferralLink = config('app.url') . '/register?ref=' . $newReferralCode;
    
        // Process promotion code if provided.
        $bonus = null;
        $promotion = null;
        if (!empty($data['promotion_code'])) {
            // Look up the promotion using the provided code (assume codes are stored in uppercase)
            $promotion = Promotion::where('code', strtoupper($data['promotion_code']))->first();
            if ($promotion) {
                // Check if the promotion can still be used.
                if ($promotion->used < $promotion->max_use) {
                    $bonus = $promotion->code;
                    // Increment the used count.
                    $promotion->increment('used');
                } else {
                    // Optionally, you can add an error or simply leave bonus as null.
                    $bonus = null;
                }
            }
        }
    
        // Create the user.
        $user = User::create([
            'name'          => $data['name'],
            'email'         => $data['email'],
            'password'      => Hash::make($data['password']),
            // Set the upline (referral) user ID; you might use a column like 'referral' or 'upline_id'
            'referral'      => $upline ? $upline->id : null,
            // Save the new user's unique referral code and link
            'referral_code' => $newReferralCode,
            'referral_link' => $newReferralLink,
            // Store the promotion code (if valid) into the bonus column.
            'bonus'         => $bonus,
        ]);
    
        // If a valid promotion was applied, update the user's wallet and record a transfer.
        if (!is_null($bonus) && $promotion) {
            // Try to find the wallet record for the user; assume it may be created automatically or create one if missing.
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'cash_wallet'       => 0,
                    'trading_wallet'    => 0,
                    'earning_wallet'    => 0,
                    'affiliates_wallet' => 0,
                    'bonus_wallet'      => 0,
                ]
            );
            // Add the promotion's amount to the bonus_wallet. (Ensure promotion->amount is a numeric value.)
            $wallet->bonus_wallet = bcadd($wallet->bonus_wallet, $promotion->amount, 4);
            $wallet->save();
    
            // Create a record in the transfers table for the bonus.
            Transfer::create([
                'user_id'     => $user->id,
                'txid'        => 'b_' . rand(10000, 99999),
                'from_wallet' => 'trading_wallet',
                'to_wallet'   => 'bonus_wallet',
                'amount'      => $promotion->amount,
                'status'      => 'Completed',
                'remark'      => 'bonus',
            ]);
        }
        Mail::to($user->email)->send(new WelcomeMail($user));
        return $user;
    }

    
    protected function generateUniqueReferralCode()
    {
        do {
            // Generate a random alphanumeric 7-character code.
            $code = strtoupper(substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 7));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }
}
