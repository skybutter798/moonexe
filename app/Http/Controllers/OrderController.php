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
        
        if ($user->status == 0) {
            Log::channel('admin')->warning("Blocked access to /order for deactivated user", ['user_id' => $user->id]);
            return redirect()->route('user.dashboard')->withErrors('Your account has been deactivated and cannot access the trading page. Any balance from unclaimed orders will be returned to your cash wallet once the order pair completes within 24 hours.');
        }
        
    
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
                return $b->closingTimestamp <=> $a->closingTimestamp;
            }
        
            // If one is expired and the other is not, put the non-expired one first.
            return $aExpired ? 1 : -1;
        });

    
        // Also retrieve the current user’s trading wallet balance.
        $wallet = Wallet::where('user_id', $user->id)->first();
        $tradingBalance = $wallet ? $wallet->trading_wallet : 0;
        $bonusBalance = $wallet ? $wallet->bonus_wallet : 0;
    
        // Retrieve the current user's orders for "My Exchange Orders" section.
        $userOrders = Order::where('user_id', $user->id)
                           ->orderBy('created_at', 'desc')
                           ->with('pair')
                           ->paginate(10);
    
        // Check if the user already made an order today.
        $hasOrderToday = Order::where('user_id', $user->id)
                              ->whereDate('created_at', date('Y-m-d'))
                              ->exists();
    
        return view('user.order_v2', compact('pairs', 'tradingBalance', 'userOrders', 'hasOrderToday', 'package', 'bonusBalance'));
    }

    public function store(Request $request)
    {
        // Log the start of the order creation along with request data.
        Log::channel('order')->info('Order process initiated.', [
            'user_id' => auth()->id(),
            'request_data' => $request->all(),
        ]);
    
        // Validate input
        $request->validate([
            'pair_id'           => 'required|exists:pairs,id',
            'order_type'        => 'required|in:buy,sell',
            'amount'            => 'required|numeric|min:0.01',
            'estimated_receive' => 'sometimes|required_if:order_type,buy|numeric|min:0.01',
        ]);
    
        $user = auth()->user();
        Log::channel('order')->info('User passed validation.', ['user_id' => $user->id]);
    
        // Calculate total available amount from transfers.
        $totalStepOne = Transfer::where('user_id', $user->id)
            ->where('from_wallet', 'cash_wallet')
            ->where('to_wallet', 'trading_wallet')
            ->where('status', 'Completed')
            //->where('remark', 'package')
            ->sum('amount');
    
        $totalStepTwo = Transfer::where('user_id', $user->id)
            ->where('from_wallet', 'trading_wallet')
            ->where('to_wallet', 'cash_wallet')
            ->where('status', 'Completed')
            ->sum('amount');
    
        $totalAvailable = $totalStepOne - $totalStepTwo;
    
        // Get total pending buy orders for today.
        $totalPendingBuy = Order::where('user_id', $user->id)
            ->where('status', 'pending')
            ->whereDate('created_at', Carbon::today())
            ->sum('buy');
    
        $orderAmount = $request->amount;
        $availableToBuy = $totalAvailable - $totalPendingBuy;
    
        // For buy orders, validate available limit.
        if ($request->order_type === 'buy' && ($totalPendingBuy + $orderAmount) > $totalAvailable) {
            Log::channel('order')->warning('Buy order exceeds available limit.', [
                'user_id'           => $user->id,
                'total_pending_buy' => $totalPendingBuy,
                'total_available'   => $totalAvailable,
                'order_amount'      => $orderAmount,
                'available_to_buy'  => $availableToBuy,
            ]);
    
            return response()->json([
                'success' => false,
                'error'   => 'Your available limit for buy orders is ' . $availableToBuy . '. You cannot place an order of ' . $orderAmount . '.'
            ], 422);
        }
    
        // Retrieve pair
        $pair = Pair::findOrFail($request->pair_id);
        if (!isset($pair->rate)) {
            Log::channel('order')->error('Pair rate not found.', [
                'user_id' => $user->id,
                'pair_id' => $pair->id,
            ]);
            return response()->json(['success' => false, 'error' => 'Pair rate not found.']);
        }
        
        // Determine the estimated rate
        $existingOrder = Order::where('pair_id', $pair->id)->first();
        if ($existingOrder && $existingOrder->est_rate !== null) {
            $est_rate = $existingOrder->est_rate;
        } else {
            $randomDelta = mt_rand(1, 4) / 100;
            // Decide randomly whether to add or subtract the random delta.
            $shouldAdd = mt_rand(0, 1) == 1;
            $est_rate = $shouldAdd ? $pair->rate + $randomDelta : $pair->rate - $randomDelta;
        }
        
        $rate = $est_rate / 100;
    
        $orderType = $request->order_type;
        $amount    = $request->amount;
        $txid = 'o_' . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    
        $wallet = \App\Models\Wallet::where('user_id', $user->id)->first();
        if (!$wallet) {
            Log::channel('order')->error('Wallet not found.', ['user_id' => $user->id]);
            return response()->json(['success' => false, 'error' => 'Wallet not found.']);
        }
    
        // For buy orders, process wallet deductions.
        if ($orderType === 'buy') {
            if ($wallet->trading_wallet < $amount) {
                Log::channel('order')->error('Insufficient trading wallet balance.', [
                    'user_id'        => $user->id,
                    'order_amount'   => $amount,
                    'wallet_balance' => $wallet->trading_wallet
                ]);
                return response()->json(['success' => false, 'error' => 'Insufficient USD balance in trading wallet.']);
            }
            $assetReceived = $request->estimated_receive;
            $wallet->trading_wallet -= $amount;
            $wallet->save();
    
            Log::channel('order')->info('Wallet debited for buy order.', [
                'user_id'                   => $user->id,
                'order_amount'              => $amount,
                'new_trading_wallet_balance'=> $wallet->trading_wallet,
            ]);
        }
    
        // Calculate earning for the order.
        $earning = $amount * $rate;
    
        // Create the order.
        try {
            $order = Order::create([
                'user_id'  => $user->id,
                'pair_id'  => $pair->id,
                'txid'     => $txid,
                'buy'      => $orderType === 'buy' ? $amount : null,
                'sell'     => $orderType === 'sell' ? $amount : null,
                'receive'  => $orderType === 'buy' ? $assetReceived : null,
                'status'   => 'pending',
                'earning'  => $earning,
                'est_rate' => $est_rate,
                'time' => random_int(10800, 64800),
            ]);
    
            Log::channel('order')->info('Order successfully created.', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'txid'     => $txid,
            ]);
        } catch (\Exception $ex) {
            // Log any exception during order creation.
            Log::channel('order')->error('Order creation failed.', [
                'user_id'   => $user->id,
                'exception' => $ex->getMessage(),
            ]);
            return response()->json(['success' => false, 'error' => 'Order creation failed.'], 500);
        }
    
        // Calculate the remaining volume for the pair.
        $sumOrdersReceive = Order::where('pair_id', $pair->id)->sum('receive');
        $remainingVolume = $pair->volume - $sumOrdersReceive;
    
        // Trigger any post-order event and log the event trigger.
        event(new OrderUpdated($pair->id, $remainingVolume, $pair->volume));
        Log::channel('order')->info('OrderUpdated event triggered.', [
            'pair_id'         => $pair->id,
            'remaining_volume'=> $remainingVolume,
            'total_volume'    => $pair->volume,
        ]);
    
        return response()->json([
            'success' => true,
            'message' => ucfirst($orderType).' order executed successfully.'
        ]);
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
        
        $claimReadyAt = $order->created_at->addSeconds($order->time);
        
        if (now()->lessThan($claimReadyAt)) {
            Log::warning("Order ID: {$order->id} cannot be claimed yet. Claim ready at: {$claimReadyAt}, now: " . now());
            return response()->json([
                'success' => false, 
                'error' => 'Order is not ready to be claimed yet.'
            ]);
        }

        // Step 2: Calculate user's total and percentages using the UserRangeCalculator.
        $rangeCalculator = new \App\Services\UserRangeCalculator();
        $userRange = $rangeCalculator->calculate($user);
        Log::info("User {$user->id} - Total: {$userRange['total']}, Direct Percentage: {$userRange['direct_percentage']}, Matching Percentage: {$userRange['matching_percentage']}");
        
        // Step 3: Calculate claim amounts and create payout record.
        $claimService = new \App\Services\ClaimService();
        $claimAmounts = $claimService->calculate($order);
        $baseClaimAmount = $claimAmounts['base'];
        $percentage = $claimAmounts['percentage'] ?? 50;
        
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
            'redirect_url' => route('user.assets'),
            'claim_amount' => $baseClaimAmount,
            'percentage' => $percentage ?? 50 // default if undefined
        ]);


    }

}
