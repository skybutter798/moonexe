<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Wallet;
use App\Models\Deposit;
use App\Models\Withdrawal;
use App\Models\Transfer;
use App\Models\Asset;
use App\Models\User;
use App\Models\Payout;
use Illuminate\Support\Str;
use App\Services\UserRangeCalculator;

class AssetsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

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
    
        $total_balance = (float)$wallets->cash_wallet +
                         (float)$wallets->trading_wallet +
                         (float)$wallets->earning_wallet +
                         (float)$wallets->affiliates_wallet;
                         
        $roiRecords = \App\Models\Payout::with(['order.pair.currency'])
            ->where('user_id', $userId)
            ->where('type', 'payout')
            ->where('wallet', 'earning')
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'trading_page');
        
        foreach ($roiRecords as $roi) {
            if ($roi->order && $roi->order->pair && $roi->order->pair->currency) {
                $roi->cname = $roi->order->pair->currency->c_name;
            } else {
                $roi->cname = 'N/A';
            }
        }

    
        // Existing queries for deposits, withdrawals, and transfers…
        $depositRequests = Deposit::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
        $withdrawalRequests = Withdrawal::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
        $transferRecords = Transfer::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
    
        $transactions = collect();
        foreach ($depositRequests as $deposit) {
            if ($deposit->status === 'Completed') {
                $deposit->type = 'Deposit';
                $deposit->transaction_description = 'Deposit';
                $deposit->transaction_amount = '+' . number_format($deposit->amount, 4);
                $transactions->push($deposit);
            }
        }
        foreach ($withdrawalRequests as $withdrawal) {
            if ($withdrawal->status === 'Completed') {
                $withdrawal->type = 'Withdrawal';
                $withdrawal->transaction_description = 'Withdraw';
                $withdrawal->transaction_amount = '-' . number_format($withdrawal->amount, 4);
                $transactions->push($withdrawal);
            }
        }
        foreach ($transferRecords as $transfer) {
            if ($transfer->status === 'Completed') {
                // Check if it's a downline transfer (cash_wallet to cash_wallet with remark "downline")
                if ($transfer->from_wallet === 'cash_wallet' && $transfer->to_wallet === 'cash_wallet' && $transfer->remark === 'downline') {
                    $transfer->transaction_description = 'USDT → Downline';
                    $transfer->type = 'Transfer';
                } else {
                    $transfer->type = 'Transfer';
                    $walletMapping = [
                        'earning_wallet'    => 'Trade Profit',
                        'affiliates_wallet' => 'Affiliates',
                        'cash_wallet'       => 'USDT',
                        'trading_wallet'    => 'Trade Margin',
                    ];
                    $fromReadable = $walletMapping[$transfer->from_wallet] ?? ucfirst($transfer->from_wallet);
                    $toReadable   = $walletMapping[$transfer->to_wallet] ?? ucfirst($transfer->to_wallet);
                    $transfer->transaction_description = "{$fromReadable} → {$toReadable}";
                }
                $transfer->transaction_amount = number_format($transfer->amount, 4);
                $transactions->push($transfer);
            }
        }
    
        $transactions = $transactions->sortByDesc('created_at');
    
        $assets = \App\Models\Asset::with('currencyData')
            ->where('user_id', $userId)
            ->get();
    
        // (Optional) Price/Cost and 24H Change queries using orders, pairs, and currencies tables.
        $priceCostByCurrency = DB::table('orders')
            ->join('pairs', 'orders.pair_id', '=', 'pairs.id')
            ->join('currencies', 'pairs.currency_id', '=', 'currencies.id')
            ->select('currencies.c_name as currency', DB::raw('AVG(orders.buy) as avg_price'))
            ->where('orders.user_id', $userId)
            ->where('orders.buy', '>', 0)
            ->groupBy('currencies.c_name')
            ->pluck('avg_price', 'currency')
            ->toArray();
    
        $today = now()->startOfDay();
        $changeResults = DB::table('orders')
            ->join('pairs', 'orders.pair_id', '=', 'pairs.id')
            ->join('currencies', 'pairs.currency_id', '=', 'currencies.id')
            ->select(
                'currencies.c_name as currency',
                DB::raw("SUM(orders.buy) as total_buy"),
                DB::raw("SUM(orders.sell) as total_sell")
            )
            ->where('orders.user_id', $userId)
            ->where('orders.created_at', '>=', $today)
            ->groupBy('currencies.c_name')
            ->get();
    
        $netChangeByCurrency = [];
        foreach ($changeResults as $row) {
            $netChangeByCurrency[$row->currency] = $row->total_buy - $row->total_sell;
        }
    
        // Fetch packages (for the package modal). Here we assume you only show active packages.
        $packages = DB::table('packages')->where('status', '1')->get();
    
        $user = auth()->user();
        $currentPackage = null;
        if ($user->package) {
            $currentPackage = DB::table('packages')->where('id', $user->package)->first();
        }
    
        // --- Retrieve dynamic payout records based on payout type ---
        // Fetch payouts for the current user (assuming payout->wallet is used to differentiate earning vs. affiliates)
        $payoutRecords = \App\Models\Payout::where('user_id', $userId)
            ->whereIn('wallet', ['affiliates'])
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'payout_page');

    
        // Run the UserRangeCalculator to get the percentages
        $userRangeCalc = new \App\Services\UserRangeCalculator();
        $rangeCalculation = $userRangeCalc->calculate($user);
        $directPercentage = $rangeCalculation['direct_percentage'];
        $matchingPercentage = $rangeCalculation['matching_percentage'];
    
        // Enrich each payout record with additional data based on type.
        foreach ($payoutRecords as $payout) {
            if ($payout->type === 'direct') {
                // For direct payouts, find the transfer where transfers->txid matches the payout->txid.
                $transfer = \App\Models\Transfer::where('txid', $payout->txid)->first();
                if ($transfer) {
                    $payout->deposit_txid = $transfer->txid;
                    $payout->deposit_amount = number_format($transfer->amount, 4);
                    // You can also attach the user_id if needed:
                    // $payout->transfer_user_id = $transfer->user_id;
                } else {
                    $payout->deposit_txid = 'N/A';
                    $payout->deposit_amount = '0.0000';
                }
                $payout->direct_percentage = $directPercentage;
            } else {
                // For regular earning payouts, find the order details using order_id.
                $order = \App\Models\Order::find($payout->order_id);
                if ($order) {
                    $payout->txid = $order->txid;
                    $payout->buy = number_format($order->buy, 4);
                    $payout->earning = number_format($order->earning, 4);
                } else {
                    $payout->txid = 'N/A';
                    $payout->buy = '0.0000';
                    $payout->earning = '0.0000';
                }
                // For non-direct payouts, use matching_percentage.
                $payout->profit_sharing = $matchingPercentage;
            }
        }

    
        return view('user.assets_v2', [
            'title'               => 'My Assets',
            'wallets'             => $wallets,
            'total_balance'       => $total_balance,
            'depositRequests'     => $depositRequests,
            'withdrawalRequests'  => $withdrawalRequests,
            'transactions'        => $transactions,
            'assets'              => $assets,
            'priceCostByCurrency' => $priceCostByCurrency,
            'netChangeByCurrency' => $netChangeByCurrency,
            'packages'            => $packages,
            'currentPackage'      => $currentPackage,
            'payoutRecords'       => $payoutRecords,
            'roiRecords'          => $roiRecords,
        ]);
    }
    
    public function transferTrading(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);
    
        $userId = auth()->id();
        $wallet = Wallet::where('user_id', $userId)->first();
    
        if (!$wallet) {
            Log::channel('admin')->info("Wallet not found for user", ['user_id' => $userId]);
            return redirect()->back()->withErrors('Wallet not found.');
        }
        Log::channel('admin')->info("Wallet found", ['user_id' => $userId]);
    
        $amount = (float)$request->amount;
    
        // Check if the trading wallet has sufficient funds
        if ($wallet->trading_wallet < $amount) {
            Log::channel('admin')->info("Insufficient trading wallet funds", [
                'user_id' => $userId,
                'required' => $amount,
                'available' => $wallet->trading_wallet
            ]);
            return redirect()->back()->withErrors('Insufficient balance in Trading Margin.');
        }
        Log::channel('admin')->info("Sufficient funds confirmed", [
            'user_id' => $userId,
            'trading_wallet_balance' => $wallet->trading_wallet
        ]);
    
        // Calculate a 20% fee on the entered amount
        $feeRate = 0.20;
        $fee = $amount * $feeRate;
        $netAmount = $amount - $fee; // Amount to be credited to Cash Wallet
        Log::channel('admin')->info("Fee calculated", [
            'user_id' => $userId,
            'amount' => $amount,
            'fee' => $fee,
            'net_amount' => $netAmount
        ]);
    
        // Update wallet balances
        $wallet->trading_wallet -= $amount;
        $wallet->cash_wallet += $netAmount;
        $wallet->save();
        Log::channel('admin')->info("Wallet balances updated", [
            'user_id' => $userId,
            'new_trading_wallet_balance' => $wallet->trading_wallet,
            'new_cash_wallet_balance' => $wallet->cash_wallet
        ]);
    
        // Generate unique transaction ID
        do {
            $randomNumber = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            $txid = 't_' . $randomNumber;
        } while (Transfer::where('txid', $txid)->exists());
        Log::channel('admin')->info("Transaction ID generated", ['txid' => $txid]);
    
        // Record the transfer
        Transfer::create([
            'user_id'     => $userId,
            'txid'        => $txid,
            'from_wallet' => 'trading_wallet',
            'to_wallet'   => 'cash_wallet',
            'amount'      => $netAmount,
            'status'      => 'Completed',
            'remark'      => number_format($fee, 2),
        ]);
        Log::channel('admin')->info("Transfer record created", ['txid' => $txid]);
    
        return redirect()->back()->with('success', 'Trading wallet transfer completed successfully. Fee deducted: ' . number_format($fee, 2) . ' USDT');
    }
    
    public function transfer(Request $request)
    {
        // Validate required fields
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'transfer_type' => 'required|string|in:earning_to_cash,affiliates_to_cash'
        ]);
    
        $userId = auth()->id();
        $wallet = Wallet::where('user_id', $userId)->first();
    
        if (!$wallet) {
            Log::channel('admin')->info("Wallet not found for user", ['user_id' => $userId]);
            return redirect()->back()->withErrors('Wallet not found.');
        }
        Log::channel('admin')->info("Wallet found", ['user_id' => $userId]);
    
        $amount = (float) $request->amount;
        $fromWallet = '';
        
        // Process transfer based on the transfer type
        if ($request->transfer_type === 'earning_to_cash') {
            if ($wallet->earning_wallet < $amount) {
                Log::channel('admin')->info("Insufficient funds in Earning Wallet", [
                    'user_id' => $userId,
                    'required' => $amount,
                    'available' => $wallet->earning_wallet
                ]);
                return redirect()->back()->withErrors('Insufficient funds in Earning Wallet.');
            }
            $wallet->earning_wallet -= $amount;
            $fromWallet = 'earning_wallet';
        } elseif ($request->transfer_type === 'affiliates_to_cash') {
            if ($wallet->affiliates_wallet < $amount) {
                Log::channel('admin')->info("Insufficient funds in Affiliates Wallet", [
                    'user_id' => $userId,
                    'required' => $amount,
                    'available' => $wallet->affiliates_wallet
                ]);
                return redirect()->back()->withErrors('Insufficient funds in Affiliates Wallet.');
            }
            $wallet->affiliates_wallet -= $amount;
            $fromWallet = 'affiliates_wallet';
        }
        Log::channel('admin')->info("Wallet funds deducted", [
            'user_id' => $userId,
            'from_wallet' => $fromWallet,
            'amount_deducted' => $amount
        ]);
    
        // Credit the Cash Wallet with the transferred amount
        $wallet->cash_wallet += $amount;
        $wallet->save();
        Log::channel('admin')->info("Cash wallet credited", [
            'user_id' => $userId,
            'cash_wallet_balance' => $wallet->cash_wallet
        ]);
    
        // Generate unique transaction ID
        do {
            $randomNumber = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            $txid = 't_' . $randomNumber;
        } while (Transfer::where('txid', $txid)->exists());
        Log::channel('admin')->info("Transaction ID generated", ['txid' => $txid]);
        
        // Record the transfer
        Transfer::create([
            'user_id'     => $userId,
            'txid'        => $txid,
            'from_wallet' => $fromWallet,
            'to_wallet'   => 'cash_wallet',
            'amount'      => $amount,
            'status'      => 'Completed',
            'remark'      => 'Transfer from ' . $fromWallet,
        ]);
        Log::channel('admin')->info("Transfer record created", ['txid' => $txid]);
    
        // Prepare a success message
        $walletNameReadable = ucfirst(str_replace('_', ' ', $fromWallet));
        return redirect()->back()->with('success', "profit transfer completed successfully.");
    }
    
    public function buyPackage(Request $request)
    {
        $user = auth()->user();
        $userId = $user->id;
        Log::channel('payout')->info("--------> Buy package initiated", ['user_id' => $userId]);
        
        $wallet = Wallet::where('user_id', $userId)->first();
        if (!$wallet) {
            return redirect()->back()->withErrors('Wallet not found.');
        }
    
        // Determine whether this is a first-time activation or a top-up.
        if (!$user->package) {
            // First-time activation using activation_amount.
            $request->validate([
                'activation_amount' => 'required|numeric|min:1',
            ]);
            $activationAmount = (float) $request->activation_amount;
            
            // Check sufficient funds.
            if ($wallet->cash_wallet < $activationAmount) {
                return redirect()->back()->withErrors('Insufficient balance in Cash Wallet.');
            }
            
            // For first-time activation, we initially look among directranges with IDs 1, 2, 3.
            $initialRange = DB::table('directranges')
                ->whereIn('id', [1,2,3])
                ->where('min', '<=', $activationAmount)
                ->where(function($query) use ($activationAmount) {
                    $query->where('max', '>=', $activationAmount)
                          ->orWhereNull('max');
                })->first();
            
            if (!$initialRange) {
                return redirect()->back()->withErrors('Activation amount does not match any available range.');
            }
        } else {
            // Top-up for already activated user using topup_amount.
            $request->validate([
                'topup_amount' => 'required|numeric|min:1',
            ]);
            $topupAmount = (float) $request->topup_amount;
            
            if ($wallet->cash_wallet < $topupAmount) {
                return redirect()->back()->withErrors('Insufficient balance in Cash Wallet for top-up.');
            }
            
            // For top-ups, we use the user's current range.
            $initialRange = DB::table('directranges')->where('id', $user->package)->first();
            Log::channel('payout')->info("Using user's current range for top-up", ['range' => (array)$initialRange]);
        }
    
        // Determine the amount for transfer.
        $amount = !$user->package ? $activationAmount : $topupAmount;
        Log::channel('payout')->info("Amount determined for transfer", ['user_id' => $userId, 'amount' => $amount]);
    
        // Deduct from cash_wallet and add to trading_wallet.
        $wallet->cash_wallet -= $amount;
        $wallet->trading_wallet += $amount;
        $wallet->save();
        
        Log::channel('payout')->info("Wallet updated", [
            'user_id' => $userId,
            'new_cash_wallet' => $wallet->cash_wallet,
            'new_trading_wallet' => $wallet->trading_wallet
        ]);
    
        // Generate a unique transaction ID.
        do {
            $randomNumber = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            $txid = 't_' . $randomNumber;
        } while (Transfer::where('txid', $txid)->exists());
        
        // Record the transfer.
        $transfer = Transfer::create([
            'user_id'     => $userId,
            'txid'        => $txid,
            'from_wallet' => 'cash_wallet',
            'to_wallet'   => 'trading_wallet',
            'amount'      => $amount,
            'status'      => 'Completed',
            'remark'      => 'package',
        ]);
    
        // For first-time activation: recalc the user's current group total to determine the proper direct range.
        // This assumes you have a service that calculates the total group value.
        if (!$user->package) {
            $rangeData = (new \App\Services\UserRangeCalculator())->calculate($user);
            $total = $rangeData['total'];
            Log::channel('payout')->info("Calculated group total for activation", ['user_id' => $userId, 'total' => $total]);
    
            // Now, find the matching direct range based on the total.
            $newRange = DB::table('directranges')
                ->where('min', '<=', $total)
                ->where(function($query) use ($total) {
                    $query->where('max', '>=', $total)
                          ->orWhereNull('max');
                })->first();
    
            if (!$newRange) {
                return redirect()->back()->withErrors('Your total does not match any available range.');
            }
            Log::channel('payout')->info("New range determined", ['new_range' => (array)$newRange]);
    
            // Save the new direct range id to the user.
            $user->package = $newRange->id;
            $user->save();
            Log::channel('payout')->info("User package updated", ['user_id' => $userId, 'new_package' => $newRange->id]);
        } else {
            // Optionally, update the user's range if the top-up changes their group total.
            // Example:
            $rangeData = (new \App\Services\UserRangeCalculator())->calculate($user);
            $total = $rangeData['total'];
            Log::channel('payout')->info("Calculated group total for top-up", ['user_id' => $userId, 'total' => $total]);
    
            $newRange = DB::table('directranges')
                ->where('min', '<=', $total)
                ->where(function($query) use ($total) {
                    $query->where('max', '>=', $total)
                          ->orWhereNull('max');
                })->first();
            if ($newRange && $newRange->id !== $user->package) {
                $user->package = $newRange->id;
                $user->save();
                Log::channel('payout')->info("User package updated due to top-up", ['user_id' => $userId, 'new_package' => $newRange->id]);
            }
        }
        
        // Distribute the transfer to the direct upline.
        // Note: Depending on your business logic, you can pass $initialRange or $newRange.
        $uplineDistributor = new \App\Services\UplineDistributor();
        $uplineDistributor->distributeDirect($transfer, $initialRange, $user);
        
        // --- BONUS TRANSFER LOGIC ---
        if (!empty($user->bonus)) {
            $bonusCode = strtoupper($user->bonus);
            Log::channel('payout')->info("Bonus code found", ['user_id' => $userId, 'bonus_code' => $bonusCode]);
    
            $promotion = DB::table('promotions')
                ->whereRaw('LOWER(code) = ?', [strtolower($bonusCode)])
                ->first();
            if ($promotion) {
                $multiply = $promotion->multiply;
                $bonusAmount = $amount * $multiply;
                Log::channel('payout')->info("Bonus calculated", ['user_id' => $userId, 'bonus_amount' => $bonusAmount]);
    
                $wallet->bonus_wallet += $bonusAmount;
                $wallet->save();
                Log::channel('payout')->info("Bonus wallet updated", ['user_id' => $userId, 'new_bonus_wallet' => $wallet->bonus_wallet]);
    
                do {
                    $bonusTxid = 'b_' . rand(10000, 99999);
                } while (Transfer::where('txid', $bonusTxid)->exists());
                Log::channel('payout')->info("Bonus transaction ID generated", ['bonus_txid' => $bonusTxid]);
    
                Transfer::create([
                    'user_id'     => $user->id,
                    'txid'        => $bonusTxid,
                    'from_wallet' => 'trading_wallet',
                    'to_wallet'   => 'bonus_wallet',
                    'amount'      => $bonusAmount,
                    'status'      => 'Completed',
                    'remark'      => 'bonus',
                ]);
                Log::channel('payout')->info("Bonus transfer record created", ['bonus_txid' => $bonusTxid]);
            }
        }
        // --- END BONUS LOGIC ---
        
        return redirect()->back()->with('success', 'Trade activated successfully.');
    }
    
    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);
    
        $userId = auth()->id();
        Log::channel('admin')->info("--------> Deposit initiated", ['user_id' => $userId, 'amount' => $request->amount]);
    
        // Use a fixed (sample) TRC20 address for deposits (not editable in the form)
        $sampleTRC20 = 'TR9wHy8rF89a59gD3dmMPhrtPhtu6n5U5H';
    
        // Generate a unique transaction ID for the deposit.
        do {
            // Generate an 8-digit number with leading zeros if necessary
            $randomNumber = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            $txid = 'd_' . $randomNumber;
        } while (Deposit::where('txid', $txid)->exists());
        Log::channel('admin')->info("Deposit transaction ID generated", ['txid' => $txid]);
    
        // Create the deposit with status "Completed" for auto-approval.
        $deposit = Deposit::create([
            'user_id'       => $userId,
            'txid'          => $txid,
            'amount'        => $request->amount,
            'trc20_address' => $sampleTRC20,
            'status'        => 'Completed', // Set directly to Completed
        ]);
        Log::channel('admin')->info("Deposit record created", ['txid' => $txid]);
    
        // Find the user's wallet; create one if it doesn't exist.
        $wallet = Wallet::firstOrNew(['user_id' => $userId]);
        if (!$wallet->exists) {
            $wallet->cash_wallet = 0;
            $wallet->trading_wallet = 0;
            $wallet->earning_wallet = 0;
            $wallet->affiliates_wallet = 0;
            Log::channel('admin')->info("New wallet created for user", ['user_id' => $userId]);
        } else {
            Log::channel('admin')->info("Existing wallet retrieved", ['user_id' => $userId]);
        }
    
        // Add the deposit amount to the user's Cash Wallet.
        $wallet->cash_wallet += $deposit->amount;
        $wallet->save();
        Log::channel('admin')->info("Wallet updated with deposit", [
            'user_id' => $userId,
            'new_cash_wallet_balance' => $wallet->cash_wallet
        ]);
    
        return redirect()->back()->with('success', 'Deposit submitted and auto-approved successfully.');
    }
    
    public function withdrawal(Request $request)
    {
        $request->validate([
            'amount'          => 'required|numeric|min:0.01',
            'trc20_address'   => 'required|string',
        ]);

        $userId = auth()->id();

        // Get the wallet record for the authenticated user.
        $wallet = Wallet::where('user_id', $userId)->first();

        if (!$wallet) {
            return redirect()->back()->withErrors('Wallet not found.');
        }

        // Check that the cash wallet has enough funds.
        if ($wallet->cash_wallet < $request->amount) {
            return redirect()->back()->withErrors('Insufficient balance in USDT wallet.');
        }

        do {
            // Generate an 8-digit number with leading zeros if necessary
            $randomNumber = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            $txid = 'w_' . $randomNumber;
        } while (Withdrawal::where('txid', $txid)->exists());
        
        Withdrawal::create([
            'user_id'       => $userId,
            'txid'          => $txid,
            'amount'        => $request->amount,
            'trc20_address' => $request->trc20_address,
            'status'        => 'Pending',
        ]);


        // Optionally, deduct the requested amount from the cash wallet immediately.
        $wallet->cash_wallet = $wallet->cash_wallet - $request->amount;
        $wallet->save();

        return redirect()->back()->with('success', 'Withdrawal request submitted successfully.');
    }
    
    public function sendFunds(Request $request)
    {
        // Validate incoming data
        $request->validate([
            'downline_email' => 'required|email|exists:users,email',
            'amount' => 'required|numeric|min:0.01',
        ]);
        Log::channel('admin')->info("--------> sendFunds: Request validated", [
            'downline_email' => $request->downline_email,
            'amount' => $request->amount,
        ]);
    
        $user = auth()->user();
        $amount = (float) $request->amount; // e.g., 1000
    
        // Check if the sender has sufficient funds
        if ($user->wallet->cash_wallet < $amount) {
            Log::channel('admin')->info("sendFunds: Insufficient funds", [
                'user_id' => $user->id,
                'available_cash' => $user->wallet->cash_wallet,
                'requested_amount' => $amount,
            ]);
            return redirect()->back()->withErrors(['Insufficient funds in your USDT wallet.']);
        }
        Log::channel('admin')->info("sendFunds: Sufficient funds confirmed", [
            'user_id' => $user->id,
            'available_cash' => $user->wallet->cash_wallet,
        ]);
    
        // Retrieve the downline user by email
        $downline = User::where('email', $request->downline_email)->first();
        if (!$downline) {
            Log::channel('admin')->info("sendFunds: Downline not found", [
                'downline_email' => $request->downline_email,
            ]);
            return redirect()->back()->withErrors(['Downline user not found.']);
        }
        Log::channel('admin')->info("sendFunds: Downline retrieved", [
            'downline_id' => $downline->id,
        ]);
    
        // Verify that the recipient is actually the sender's downline
        if ($downline->referral !== $user->id) {
            Log::channel('admin')->info("sendFunds: Invalid downline relationship", [
                'sender_id' => $user->id,
                'downline_id' => $downline->id,
                'expected_referral' => $user->id,
                'actual_referral' => $downline->referral,
            ]);
            return redirect()->back()->withErrors(['The specified user is not your downline.']);
        }
        Log::channel('admin')->info("sendFunds: Downline relationship verified", [
            'sender_id' => $user->id,
            'downline_id' => $downline->id,
        ]);
    
        // Deduct the amount from the sender's cash_wallet
        $user->wallet->cash_wallet -= $amount;
        $user->wallet->save();
        Log::channel('admin')->info("sendFunds: Sender wallet debited", [
            'user_id' => $user->id,
            'new_cash_wallet' => $user->wallet->cash_wallet,
        ]);
    
        // Add the amount to the downline's cash_wallet
        $downline->wallet->cash_wallet += $amount;
        $downline->wallet->save();
        Log::channel('admin')->info("sendFunds: Downline wallet credited", [
            'downline_id' => $downline->id,
            'new_cash_wallet' => $downline->wallet->cash_wallet,
        ]);
    
        // Generate a unique sender transaction ID with 8 digits
        do {
            $senderTxid = 's_' . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Transfer::where('txid', $senderTxid)->exists());
        Log::channel('admin')->info("sendFunds: Sender transaction ID generated", [
            'senderTxid' => $senderTxid,
        ]);
    
        // Generate a unique receiver transaction ID with 8 digits
        do {
            $receiverTxid = 's_' . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Transfer::where('txid', $receiverTxid)->exists());
        Log::channel('admin')->info("sendFunds: Receiver transaction ID generated", [
            'receiverTxid' => $receiverTxid,
        ]);
    
        // Record the transfer for the sender (amount is negative)
        Transfer::create([
            'user_id'     => $user->id,
            'txid'        => $senderTxid,
            'from_wallet' => 'cash_wallet',
            'to_wallet'   => 'cash_wallet',
            'amount'      => -abs($amount),
            'status'      => 'Completed',
            'remark'      => 'downline'
        ]);
        Log::channel('admin')->info("sendFunds: Sender transfer record created", [
            'user_id' => $user->id,
            'txid' => $senderTxid,
        ]);
    
        // Record the transfer for the downline (amount is positive)
        Transfer::create([
            'user_id'     => $downline->id,
            'txid'        => $receiverTxid,
            'from_wallet' => 'cash_wallet',
            'to_wallet'   => 'cash_wallet',
            'amount'      => abs($amount),
            'status'      => 'Completed',
            'remark'      => 'downline'
        ]);
        Log::channel('admin')->info("sendFunds: Downline transfer record created", [
            'user_id' => $downline->id,
            'txid' => $receiverTxid,
        ]);
    
        return redirect()->back()->with('success', 'Funds sent successfully.');
    }

}