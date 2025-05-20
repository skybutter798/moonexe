<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Wallet;
use App\Models\User;
use App\Models\Promotion;
use App\Models\Annoucement;
use App\Models\Transfer;
use Carbon\Carbon;

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
        
        $assetsRecords = \App\Models\AssetsRecord::where('user_id', $userId)
            ->orderBy('record_date', 'asc')
            ->get();
            
        $profitRecords = \App\Models\ProfitRecord::where('user_id', $userId)
            ->orderBy('record_date', 'asc')
            ->get();
            
            
        
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
        $forexRecords = \App\Models\MarketData::orderBy('symbol')->get();
        $announcement = Annoucement::where('status', 1)
                       ->orderBy('updated_at', 'desc')
                       ->first();
                       
        // Set the MEGADROP campaign time (converted from New York to Malaysia time)
        $startMY = Carbon::createFromFormat('Y-m-d H:i:s', '2025-05-20 12:01:00', 'Asia/Kuala_Lumpur');
        $endMY   = Carbon::createFromFormat('Y-m-d H:i:s', '2025-06-06 11:59:59', 'Asia/Kuala_Lumpur');

        
        // Get the user
        $user = \App\Models\User::find($userId);
        
        // Bonus funds (campaign bonus)
        $campaignTradingBonus = \App\Models\Transfer::where('user_id', $userId)
            ->where('from_wallet', 'cash_wallet')
            ->where('to_wallet', 'trading_wallet')
            ->where('status', 'Completed')
            ->where('remark', 'campaign')
            ->sum('amount');
            
        // Base sum of completed package transfers during MEGADROP
        $megadropDeposit = \App\Models\Transfer::where('user_id', $userId)
                    ->where('status', 'Completed')
                    ->where('remark', 'package')
                    ->whereBetween('created_at', [$startMY, $endMY])
                    ->sum('amount');
        
        // Apply logic based on user registration date
        if ($user && $user->created_at < $startMY) {
            $megadropDeposit = ($megadropDeposit * 1.5) + $campaignTradingBonus;
        } elseif ($user && $user->created_at >= $startMY) {
            $megadropDeposit += $campaignTradingBonus;
        }
        
        
        // Real trading balance = total - campaign bonus
        $realTradingBalance = max(0, $wallets->trading_wallet - $campaignTradingBonus);
        
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
            'assetsRecords'      => $assetsRecords,
            'profitRecords'      => $profitRecords,
            'forexRecords'       => $forexRecords,
            "megadropDeposit"      => $megadropDeposit,
            'realTradingBalance'     => $realTradingBalance,
            'campaignTradingBonus'   => $campaignTradingBonus,
        ];
        
        $data['tradermadeApiKey'] = config('services.tradermade.key');
        $data['announcement'] = $announcement;
        
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
    
    public function showAnnouncements(Request $request)
    {
        $query = Annoucement::query()->orderBy('created_at', 'desc');
    
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }
    
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }
    
        $announcements = $query->get();
    
        return view('user.announcements', compact('announcements'));
    }



}
