<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pair;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\Asset;
use Illuminate\Support\Str; // Import the Str helper

class OrderController extends Controller
{
    public function index()
    {
        // Eager load orders with each pair to avoid N+1 queries.
        $pairs = \App\Models\Pair::with('orders', 'currency', 'pairCurrency')->get();
    
        // Also retrieve the current user’s trading wallet balance.
        $wallet = \App\Models\Wallet::where('user_id', auth()->id())->first();
        $tradingBalance = $wallet ? $wallet->trading_wallet : 0;
    
        // Optionally, load the current user's orders for the "My Exchange Orders" section.
        $userOrders = \App\Models\Order::where('user_id', auth()->id())->orderBy('created_at', 'desc')->get();
    
        return view('user.order', compact('pairs', 'tradingBalance', 'userOrders'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'pair_id'    => 'required|exists:pairs,id',
            'order_type' => 'required|in:buy,sell',
            'amount'     => 'required|numeric|min:0.01',
        ]);

        $user = auth()->user();
        $pair = Pair::findOrFail($request->pair_id);
        $rate = $pair->rate;
        $orderType = $request->order_type;
        $amount = $request->amount;

        // Generate a random transaction ID with a prefix "o_"
        $txid = 'o_' . Str::random(10);

        // Retrieve the user's wallet.
        $wallet = Wallet::where('user_id', $user->id)->first();
        if (!$wallet) {
            return response()->json(['success' => false, 'error' => 'Wallet not found.']);
        }

        if ($orderType === 'buy') {
            if ($wallet->trading_wallet < $amount) {
                return response()->json(['success' => false, 'error' => 'Insufficient USD balance in trading wallet.']);
            }
            $assetReceived = $amount / $rate;
            $wallet->trading_wallet -= $amount;
            $wallet->save();

            Order::create([
                'user_id' => $user->id,
                'pair_id' => $pair->id,
                'txid'    => $txid,
                'buy'     => $amount,      // USD spent
                'sell'    => null,
                'receive' => $assetReceived, // Asset received
                'status'  => 'completed',
            ]);

            $asset = Asset::firstOrNew([
                'user_id'  => $user->id,
                'currency' => $pair->currency->c_name, // e.g., MYR
            ]);
            $asset->amount = ($asset->amount ?? 0) + $assetReceived;
            $asset->status = 'active';
            $asset->save();

            return response()->json(['success' => true, 'message' => 'Buy order executed successfully.']);

        } elseif ($orderType === 'sell') {
            $asset = Asset::where([
                ['user_id', '=', $user->id],
                ['currency', '=', $pair->currency->c_name],
            ])->first();

            if (!$asset || $asset->amount < $amount) {
                return response()->json(['success' => false, 'error' => 'Insufficient asset balance to sell.']);
            }
            $usdReceived = $amount * $rate;
            $asset->amount -= $amount;
            $asset->save();

            $wallet->trading_wallet += $usdReceived;
            $wallet->save();

            Order::create([
                'user_id' => $user->id,
                'pair_id' => $pair->id,
                'txid'    => $txid,
                'buy'     => null,
                'sell'    => $amount,    // Asset sold
                'receive' => $usdReceived, // USD received
                'status'  => 'completed',
            ]);

            return response()->json(['success' => true, 'message' => 'Sell order executed successfully.']);
        }

        return response()->json(['success' => false, 'error' => 'Invalid order type.']);
    }
}
