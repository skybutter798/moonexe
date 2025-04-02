<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Wallet;
use App\Models\User;
use App\Models\Promotion;
use App\Models\Transfer;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        $wallets = Wallet::where('user_id', $userId)->first();
        
        if (!$wallets) {
            $wallets = (object) [
                'cash_wallet'       => 0,
                'trading_wallet'    => 0,
                'earning_wallet'    => 0,
                'affiliates_wallet' => 0,
            ];
        }
        
        // Calculate the total from the wallets
        $total_balance = (float)$wallets->cash_wallet +
                         (float)$wallets->trading_wallet +
                         (float)$wallets->earning_wallet +
                         (float)$wallets->affiliates_wallet;
        
        // Sum the 'buy' amounts from pending orders for the given user
        $pendingBuy = \DB::table('orders')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->sum('buy');
        
        // Add the pending 'buy' amount to the total balance
        $total_balance += (float)$pendingBuy;
        $user = auth()->user();
        
        $rangeData = (new \App\Services\UserRangeCalculator())->calculate($user);
        $directRanges = DB::table('directranges')->orderBy('min')->get();
        $currentRange = null;
        if ($user->package) {
            $currentRange = DB::table('directranges')->where('id', $user->package)->first();
        }
        
        // Check if a transfer with remark 'package' exists for this user.
        $hasPackageTransfer = DB::table('transfers')
            ->where('user_id', $userId)
            ->where('remark', 'package')
            ->exists();
        
        $data = [
            'title'              => 'Dashboard',
            'wallets'            => $wallets,
            'total_balance'      => $total_balance,
            'directRanges'       => $directRanges,
            'currentRange'       => $currentRange,
            'hasPackageTransfer' => $hasPackageTransfer,
            'user'               => $user,
            'rangeData'          => $rangeData,
            'pendingBuy'         => $pendingBuy,
        ];
        
        if ($user->isAdmin) {
            return view('admin.dashboard', $data);
        } else {
            return view('user.dashboard_v2', $data);
        }
    }
    
    public function applyPromotion(Request $request)
    {
        $request->validate([
            'promotion_code' => 'required|string|exists:promotions,code',
        ]);
    
        // Look up the promotion (code stored in uppercase)
        $promotion = \App\Models\Promotion::where('code', strtoupper($request->promotion_code))->first();
        
        if ($promotion) {
            // Check if the promotion can still be used
            if ($promotion->used >= $promotion->max_use) {
                return redirect()->back()->withErrors(['promotion_code' => 'This promotion code has reached its maximum usage.']);
            }
            
            // Update the user's bonus field with the promotion code.
            $user = auth()->user();
            $user->bonus = $promotion->code;
            $user->save();
            
            // Increment the promotion's used count.
            $promotion->increment('used');
            
            // Update the user's wallet bonus_wallet by adding the promotion's amount.
            $wallet = \App\Models\Wallet::where('user_id', $user->id)->first();
            if ($wallet) {
                // Get the promotion amount, defaulting to 0 if null.
                $amount = $promotion->amount ?? 0;
                // Add the promotion amount to bonus_wallet (4 decimal places).
                $wallet->bonus_wallet = $wallet->bonus_wallet + $amount;
                $wallet->save();
    
                // Create a record in transfers table for the bonus transfer.
                \App\Models\Transfer::create([
                    'user_id'     => $user->id,
                    'txid'        => 'b_' . rand(10000, 99999),
                    'from_wallet' => 'trading_wallet',
                    'to_wallet'   => 'bonus_wallet',
                    'amount'      => $amount,
                    'status'      => 'Completed',
                    'remark'      => 'bonus',
                ]);
            }
            
            return redirect()->back()->with('success', 'Promotion code applied successfully!');
        }
        
        return redirect()->back()->withErrors(['promotion_code' => 'Invalid promotion code.']);
    }

}
