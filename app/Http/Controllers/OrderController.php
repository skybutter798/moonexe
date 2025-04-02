<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pair;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\Asset;
use App\Models\Package;
use App\Models\Transfer;
use App\Models\DirectRange;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use App\Services\ClaimService;
use App\Services\UserRangeCalculator;
use App\Services\UplineDistributor;
use Carbon\Carbon;
use App\Events\OrderUpdated;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        // Get the date from the query string or default to today's date.
        $date = $request->query('date', date('Y-m-d'));
    
        // Get the current user and package profit (if available)
        $user = auth()->user();
        
    
        // Eager load orders with each pair and filter pairs created in the last 24 hours.
        $pairs = Pair::with('orders', 'currency', 'pairCurrency')
                     ->where('created_at', '>=', now()->subDay())
                     ->get();
                    
        $package = $user->package ? Directrange::find($user->package) : null;
        $packageProfit = $package ? $package->percentage : 0;
    
        // Transform pairs to add calculated fields.
        $pairs = $pairs->map(function ($pair) use ($packageProfit) {
            $pair->pairName = $pair->currency->c_name . ' / ' . $pair->pairCurrency->c_name;
        
            // Calculate gate close time using created_at and gate_time (in minutes)
            $gateClose = $pair->created_at->copy()->addMinutes($pair->gate_time);
            $pair->closingTimestamp = $gateClose->getTimestamp() * 1000;
        
            // Use the raw attribute for volume (avoiding any accessor modifications)
            $rawVolume = floatval($pair->getAttributes()['volume']);
        
            // Sum up all 'buy' values from orders where pair_id matches
            $tradedVolume = Order::where('pair_id', $pair->id)
                                 ->whereNotNull('buy')
                                 ->sum('receive');
        
            // Calculate remaining volume and assign it to the pair.
            $pair->remainingVolume = max($rawVolume - $tradedVolume, 0);
            $pair->rawVolume = $rawVolume;
        
            $pair->progressPercent = $rawVolume > 0 ? ($tradedVolume / $rawVolume) * 100 : 0;
        
            // Previous rate calculation.
            $buyOrders = $pair->orders->whereNotNull('buy')->sortByDesc('created_at');
            $previousBuyOrder = $buyOrders->skip(1)->first();
            $pair->previousRate = $previousBuyOrder ? number_format($previousBuyOrder->buy / $previousBuyOrder->receive, 3) : "No data found";
        
            return $pair;
        });
        
        $pairs = $pairs->sort(function($a, $b) {
            $now = now();
            // Convert closingTimestamp from milliseconds to a Carbon instance for comparison.
            $aClosing = \Carbon\Carbon::createFromTimestampMs($a->closingTimestamp);
            $bClosing = \Carbon\Carbon::createFromTimestampMs($b->closingTimestamp);
        
            // Determine if each pair is expired (closing time is in the past)
            $aExpired = $aClosing->lt($now);
            $bExpired = $bClosing->lt($now);
        
            // If both have the same expired status, sort by closingTimestamp.
            if ($aExpired === $bExpired) {
                return $a->closingTimestamp <=> $b->closingTimestamp;
            }
        
            // If one is expired and the other is not, put the non-expired one first.
            return $aExpired ? 1 : -1;
        });

    
        // Also retrieve the current user’s trading wallet balance.
        $wallet = Wallet::where('user_id', $user->id)->first();
        $tradingBalance = $wallet ? $wallet->trading_wallet : 0;
    
        // Retrieve the current user's orders for "My Exchange Orders" section.
        $userOrders = Order::where('user_id', $user->id)
                           ->orderBy('created_at', 'desc')
                           ->with('pair')
                           ->paginate(10);
    
        // Check if the user already made an order today.
        $hasOrderToday = Order::where('user_id', $user->id)
                              ->whereDate('created_at', date('Y-m-d'))
                              ->exists();
    
        return view('user.order_v2', compact('pairs', 'tradingBalance', 'userOrders', 'hasOrderToday', 'package'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'pair_id'           => 'required|exists:pairs,id',
            'order_type'        => 'required|in:buy,sell',
            'amount'            => 'required|numeric|min:0.01',
            'estimated_receive' => 'sometimes|required_if:order_type,buy|numeric|min:0.01',
        ]);
    
        $user = auth()->user();
        
        // Calculate total available amount from transfers.
        $totalStepOne = Transfer::where('user_id', $user->id)
            ->where('from_wallet', 'cash_wallet')
            ->where('to_wallet', 'trading_wallet')
            ->where('status', 'Completed')
            ->where('remark', 'package')
            ->sum('amount');

        $totalStepTwo = Transfer::where('user_id', $user->id)
            ->where('from_wallet', 'trading_wallet')
            ->where('to_wallet', 'cash_wallet')
            ->where('status', 'Completed')
            ->where('remark', 'package')
            ->sum('amount');
            
        $totalAvailable = $totalStepOne - $totalStepTwo;
        
        $totalPendingBuy = Order::where('user_id', $user->id)
                        ->where('status', 'pending')
                        ->whereDate('created_at', Carbon::today())
                        ->sum('buy');

        $orderAmount = $request->amount;
        
        // Calculate remaining amount available for buy orders.
        $availableToBuy = $totalAvailable - $totalPendingBuy;
        
        // For buy orders, ensure that adding the new order won't exceed the available limit.
        if ($request->order_type === 'buy' && ($totalPendingBuy + $orderAmount) > $totalAvailable) {
            return response()->json([
                'success' => false,
                'error' => 'Your available limit for buy orders is ' . $availableToBuy . '. You cannot place an order of ' . $orderAmount . '.'
            ], 422);
        }
        
        $pair = Pair::findOrFail($request->pair_id);
    
        // Check if the pair's rate exists.
        if (!isset($pair->rate)) {
            return response()->json(['success' => false, 'error' => 'Pair rate not found.']);
        }
        
        // Check if an order with the given pair_id already exists.
        $existingOrder = Order::where('pair_id', $pair->id)->first();
        if ($existingOrder) {
            $est_rate = $existingOrder->est_rate;
        } else {
            $randomDelta = mt_rand(1, 5) / 100;
            $est_rate = $pair->rate + $randomDelta;
        }
        $rate = $est_rate / 100;
    
        $orderType = $request->order_type;
        $amount = $request->amount;
        $txid = 'o_' . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

        $wallet = \App\Models\Wallet::where('user_id', $user->id)->first();
    
        if (!$wallet) {
            return response()->json(['success' => false, 'error' => 'Wallet not found.']);
        }
    
        // For buy orders, deduct from the trading wallet.
        if ($orderType === 'buy') {
            if ($wallet->trading_wallet < $amount) {
                return response()->json(['success' => false, 'error' => 'Insufficient USD balance in trading wallet.']);
            }
            $assetReceived = $request->estimated_receive;
            $wallet->trading_wallet -= $amount;
            $wallet->save();
        }
    
        // Calculate computed earning using the pair's rate.
        $earning = $amount * $rate;
    
        // Create the order record.
        $order = \App\Models\Order::create([
            'user_id'  => $user->id,
            'pair_id'  => $pair->id,
            'txid'     => $txid,
            'buy'      => $orderType === 'buy' ? $amount : null,
            'sell'     => $orderType === 'sell' ? $amount : null,
            'receive'  => $orderType === 'buy' ? $assetReceived : null,
            'status'   => 'pending',
            'earning'  => $earning,
            'est_rate' => $est_rate,
        ]);
        
        // Check the remaining volume for the pair.
        $sumOrdersReceive = Order::where('pair_id', $pair->id)->sum('receive');
        $remainingVolume = $pair->volume - $sumOrdersReceive;
    
        $currencyId = $pair->currency->id;
        event(new OrderUpdated($pair->id, $remainingVolume, $pair->volume));

        return response()->json(['success' => true, 'message' => ucfirst($orderType).' order executed successfully.']);
    }
    
    public function claim(Request $request)
    {
        // Step 1: Validate request and verify order ownership & status.
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);
        
        $user = auth()->user();
        Log::info("User {$user->id} is attempting to claim order ID: {$request->order_id}");
        
        $order = \App\Models\Order::where('id', $request->order_id)
                    ->where('user_id', $user->id)
                    ->first();
        
        if (!$order) {
            Log::warning("Order ID: {$request->order_id} not found or does not belong to user: {$user->id}");
            return response()->json(['success' => false, 'error' => 'Order not found.']);
        }
        
        if ($order->status !== 'pending') {
            Log::warning("Order ID: {$order->id} is not claimable. Status: {$order->status}");
            return response()->json(['success' => false, 'error' => 'Order has already been claimed or is not claimable.']);
        }
        // Step 2: Calculate user's total and percentages using the UserRangeCalculator.
        $rangeCalculator = new \App\Services\UserRangeCalculator();
        $userRange = $rangeCalculator->calculate($user);
        Log::info("User {$user->id} - Total: {$userRange['total']}, Direct Percentage: {$userRange['direct_percentage']}, Matching Percentage: {$userRange['matching_percentage']}");
        
        // Step 3: Calculate claim amounts and create payout record.
        $claimService = new \App\Services\ClaimService();
        $claimAmounts = $claimService->calculate($order);
        $baseClaimAmount = $claimAmounts['base'];
        
        \App\Models\Payout::create([
            'user_id'  => $user->id,
            'order_id' => $order->id,
            'total'    => $order->earning,
            'txid'   => $order->txid,
            'actual'   => $baseClaimAmount,
            'type'     => 'payout',
            'wallet'   => 'earning',
            'status'   => 1,
        ]);
        
        // Step 4: Update the user's wallet and adjust asset balances if needed.
        $wallet = \App\Models\Wallet::where('user_id', $user->id)->first();
        if ($wallet) {
            $wallet->earning_wallet += $baseClaimAmount;
            $wallet->trading_wallet += $order->buy;
            $wallet->save();
            Log::info("User ID: {$user->id}, Wallet updated. New Earning Wallet: {$wallet->earning_wallet}, Trading Wallet: {$wallet->trading_wallet}");
        }
        
        // Step 5: Update the order status to completed.
        $order->status = 'completed';
        $order->save();
        Log::info("Order ID: {$order->id} status updated to completed");
        
        // Step 6: Distribute income to upline ---
        $uplineDistributor = new \App\Services\UplineDistributor();
        $uplineDistributor->distribute($order, $baseClaimAmount, $user);
        
        return response()->json([
            'success' => true, 
            'message' => 'Claimed success!',
            'redirect_url' => route('user.assets')
        ]);

    }

}
