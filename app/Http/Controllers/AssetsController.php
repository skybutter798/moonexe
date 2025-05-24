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
use App\Models\Setting;

use App\Services\UserRangeCalculator;
use App\Services\CoinDepositService;
use App\Services\TelegramService;
use App\Events\CampaignBalanceUpdated;

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
            ->where('wallet', 'affiliates')
            ->where('type', 'payout')  // <-- Added condition here
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'payout_page');
        
        // New query for direct affiliates payouts (Type = "direct")
        $directPayoutRecords = \App\Models\Payout::where('user_id', $userId)
            ->where('wallet', 'affiliates')
            ->where('type', 'direct')
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'direct_payout_page');

    
        // Run the UserRangeCalculator to get the percentages
        $userRangeCalc = new \App\Services\UserRangeCalculator();
        $rangeCalculation = $userRangeCalc->calculate($user);
        $directPercentage = $rangeCalculation['direct_percentage'];
        $matchingPercentage = $rangeCalculation['matching_percentage'];
        
        // Enrich each payout record with additional data based on type.
        foreach ($payoutRecords as $payout) {
            // This branch will always get the non-direct records,
            // so you can continue with the regular logic (order lookup, etc).
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
            // Assign the matching percentage for affiliate (non-direct) payouts.
            $payout->profit_sharing = $matchingPercentage;
        }
        
        // For direct payouts, similar logic can be used (if necessary).
        foreach ($directPayoutRecords as $payout) {
            // For direct payouts you want to fetch the transfer details.
            $transfer = \App\Models\Transfer::where('txid', $payout->txid)->first();
            if ($transfer) {
                $payout->deposit_txid = $transfer->txid;
                $payout->deposit_amount = number_format($transfer->amount, 4);
            } else {
                $payout->deposit_txid = 'N/A';
                $payout->deposit_amount = '0.0000';
            }
            // Attach the direct percentage.
            $payout->direct_percentage = $directPercentage;
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
            'directPayoutRecords' => $directPayoutRecords,
            'roiRecords'          => $roiRecords,
        ]);
    }
    
    public function transferTrading(Request $request)
    {
        $userId = auth()->id();
        $user = User::find($userId);
        $wallet = Wallet::where('user_id', $userId)->first();
    
        if (!$user || !$wallet) {
            Log::channel('admin')->info("User or Wallet not found", ['user_id' => $userId]);
            return redirect()->back()->withErrors('Account or wallet not found.');
        }
    
        if ($userId <= 202) {
            Log::channel('admin')->warning("Transfer attempt by ineligible user", ['user_id' => $userId]);
            return redirect()->back()->withErrors('Your account is not eligible to transfer any bonus. Please contact support for more information.');
        }
    
        if ($user->status == 0) {
            Log::channel('admin')->warning("Transfer attempt by deactivated user", ['user_id' => $userId]);
            return redirect()->back()->withErrors('Your account has been deactivated and cannot perform this action.');
        }
    
        Log::channel('admin')->info("User and Wallet found", ['user_id' => $userId]);
    
        // Calculate campaign bonus
        $campaignBonus = Transfer::where('user_id', $userId)
            ->where('from_wallet', 'cash_wallet')
            ->where('to_wallet', 'trading_wallet')
            ->where('status', 'Completed')
            ->where('remark', 'campaign')
            ->sum('amount');
    
        // Real balance = trading_wallet - campaign bonus
        $realBalance = $wallet->trading_wallet - $campaignBonus;
    
        if ($realBalance <= 0) {
            Log::channel('admin')->warning("Only campaign bonus available, transfer blocked", [
                'user_id' => $userId,
                'trading_wallet' => $wallet->trading_wallet,
                'campaign_bonus' => $campaignBonus
            ]);
            return redirect()->back()->withErrors('You cannot transfer campaign bonus. No real balance available.');
        }
    
        // Use only real balance
        $amount = (float) $realBalance;
        $feeRate = 0.20;
        $fee = $amount * $feeRate;
        $netAmount = $amount - $fee;
    
        Log::channel('admin')->info("Fee calculated", [
            'user_id' => $userId,
            'real_balance' => $amount,
            'fee' => $fee,
            'net_amount' => $netAmount
        ]);
    
        // Update wallet balances
        $wallet->trading_wallet = 0; // Reset whole trading wallet (real + campaign)
        $wallet->cash_wallet += $netAmount;
        $wallet->save();
    
        Log::channel('admin')->info("Wallet balances updated", [
            'user_id' => $userId,
            'new_trading_wallet' => $wallet->trading_wallet,
            'new_cash_wallet' => $wallet->cash_wallet
        ]);
    
        // Deactivate user
        $user->status = 0;
        $user->save();
    
        Log::channel('admin')->info("User account deactivated after transfer", ['user_id' => $userId]);
    
        // Generate unique txid
        do {
            $txid = 't_' . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Transfer::where('txid', $txid)->exists());
    
        Log::channel('admin')->info("Transaction ID generated", ['txid' => $txid]);
    
        // Record transfer
        Transfer::create([
            'user_id'     => $userId,
            'txid'        => $txid,
            'from_wallet' => 'trading_wallet',
            'to_wallet'   => 'cash_wallet',
            'amount'      => $netAmount,
            'status'      => 'Completed',
            'remark'      => number_format($fee, 2), // Optional: store fee as remark
        ]);
    
        Log::channel('admin')->info("Transfer record created", ['txid' => $txid]);
    
        return redirect()->back()->with('success', 'Trading wallet transfer completed successfully. Your account has been deactivated. Fee deducted: ' . number_format($fee, 2) . ' USDT');
    }
    
    public function transfer(Request $request)
    {
        // 1) Validate input
        $request->validate([
            'amount'        => 'required|numeric|min:0.01',
            'transfer_type' => 'required|string|in:earning_to_cash,affiliates_to_cash',
        ]);
        Log::debug('Requested transfer type', ['type' => $request->transfer_type]);

        $userId = auth()->id();
        $wallet = Wallet::firstOrCreate(['user_id' => $userId]);

        // 2) Special reserved‑sum check for early users moving affiliates → cash
        if ($request->transfer_type === 'affiliates_to_cash' && $userId <= 202) {
            $reserved = DB::table('payouts as p')
                ->selectRaw("
                    SUM(CASE 
                          WHEN p.type = 'payout' 
                               AND o.user_id <= 202 THEN p.actual ELSE 0 END)
                    + SUM(CASE 
                          WHEN p.type = 'direct' 
                               AND t.user_id <= 202 THEN p.actual ELSE 0 END)
                    AS total_reserved
                ")
                ->leftJoin('orders as o', function($join) {
                    $join->on('p.order_id', '=', 'o.id')
                         ->where('p.type', 'payout');
                })
                ->leftJoin('transfers as t', function($join) {
                    $join->on('p.txid', '=', 't.txid')
                         ->where('p.type', 'direct');
                })
                ->where('p.user_id', $userId)
                ->where('p.wallet', 'affiliates')
                ->whereIn('p.type', ['payout','direct'])
                ->value('total_reserved') ?: 0;

            if ($wallet->affiliates_wallet <= $reserved) {
                Log::channel('admin')->warning("transfer: Affiliates below reserved", [
                    'user_id'        => $userId,
                    'balance'        => $wallet->affiliates_wallet,
                    'reserved_sum'   => $reserved,
                ]);
                return redirect()->back()
                    ->withErrors('Your affiliates balance must exceed your reserved amount before transferring.');
            }
        }

        // 3) Deduct from the chosen wallet
        $amount     = (float) $request->amount;
        $fromWallet = $request->transfer_type === 'earning_to_cash'
                    ? 'earning_wallet'
                    : 'affiliates_wallet';

        if ($wallet->{$fromWallet} < $amount) {
            Log::channel('admin')->info("Insufficient funds in {$fromWallet}", [
                'user_id'  => $userId,
                'required' => $amount,
                'available'=> $wallet->{$fromWallet},
            ]);
            return redirect()->back()
                   ->withErrors("Insufficient funds in your {$fromWallet}.");
        }

        $wallet->{$fromWallet}   -= $amount;
        $wallet->cash_wallet    += $amount;
        $wallet->save();

        Log::channel('admin')->info("transfer: Wallets updated", [
            'user_id'      => $userId,
            'from_wallet'  => $fromWallet,
            'amount'       => $amount,
            'new_cash_bal' => $wallet->cash_wallet,
        ]);

        // 4) Generate a unique txid
        do {
            $txid = 't_' . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Transfer::where('txid', $txid)->exists());

        Log::channel('admin')->info("transfer: Generated txid", ['txid' => $txid]);

        // 5) Record the transfer
        Transfer::create([
            'user_id'     => $userId,
            'txid'        => $txid,
            'from_wallet' => $fromWallet,
            'to_wallet'   => 'cash_wallet',
            'amount'      => $amount,
            'status'      => 'Completed',
            'remark'      => "Transfer from {$fromWallet}",
        ]);

        Log::channel('admin')->info("transfer: Record created", ['txid' => $txid]);

        // 6) Success response
        return redirect()->back()
               ->with('success', 'Profit transfer completed successfully.');
    }
    
    public function buyPackage(Request $request)
    {
        $user = auth()->user();
        
        // Prevent deactivated or inactive users
        if ($user->status === 0) {
            Log::channel('payout')->warning("Blocked package purchase: user status = 0", ['user_id' => $user->id]);
            return redirect()->back()->withErrors('Your account has been deactivated and is no longer eligible for package activation or top-up. Any balance from unclaimed orders will be returned to your cash wallet once the order pair completes within 24 hours.');
        }

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
                'activation_amount' => 'required|numeric|min:10',
            ]);
            $activationAmount = (float) $request->activation_amount;
            
            if ($activationAmount % 10 !== 0) {
                return redirect()->back()->withErrors('Activation amount must be in multiples of 10.');
            }
            
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
                'topup_amount' => 'required|numeric|min:10',
            ]);
            $topupAmount = (float) $request->topup_amount;
            
            if ($topupAmount % 10 !== 0) {
                return redirect()->back()->withErrors('Top-up amount must be in multiples of 10.');
            }
            
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
        
        // ↓↓↓ CAMPAIGN BALANCE DEDUCTION ↓↓↓
        $balanceSetting = \App\Models\Setting::where('name', 'cam_balance')->first();
        if ($balanceSetting) {
            $balanceSetting->value = max(0, $balanceSetting->value - $amount); // prevent negative
            $balanceSetting->save();
        
            Log::channel('payout')->info("✅ Campaign balance updated", [
                'user_id' => $userId,
                'new_cam_balance' => $balanceSetting->value
            ]);
        
            // Broadcast the updated balance
            event(new \App\Events\CampaignBalanceUpdated($balanceSetting->value));
            Log::channel('payout')->info("📡 CampaignBalanceUpdated event broadcasted", [
                'value' => $balanceSetting->value
            ]);
        }

    
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
        
        return redirect()->back()->with('success', 'Trade Margin top-up successfully!');
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
            'status'        => 'Pending', // Set directly to Completed
        ]);
        Log::channel('admin')->info("Deposit record created", ['txid' => $txid]);
    
        // Find the user's wallet; create one if it doesn't exist.
        /*$wallet = Wallet::firstOrNew(['user_id' => $userId]);
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
        ]);*/
    
        return redirect()->back()->with('success', 'Deposit submitted successfully.');
    }
    
    public function coindeposit(Request $request, CoinDepositService $coinService)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);
    
        $userId = auth()->id();
        $coinService->depositToUser($userId, $data['amount'], auth()->user()->wallet_address);
    
        return redirect()->back()->with('success','Deposit submitted successfully.');
    }
    
    public function withdrawal(Request $request)
    {
        $request->validate([
            'amount'        => 'required|numeric|min:10',
            'trc20_address' => 'required|string',
        ]);
    
        $userId = auth()->id();
    
        $wallet = Wallet::where('user_id', $userId)->first();
        if (!$wallet) {
            return redirect()->back()->withErrors('Wallet not found.');
        }
    
        if ($wallet->cash_wallet < $request->amount) {
            return redirect()->back()->withErrors('Insufficient balance in Cash Wallet.');
        }
    
        $feePercentage = 0.03;
        $percentageFee = round($request->amount * $feePercentage, 2);
        $fee = max($percentageFee, 7.00);
        $netAmount = $request->amount - $fee;
    
        do {
            $randomNumber = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            $txid = 'w_' . $randomNumber;
        } while (Withdrawal::where('txid', $txid)->exists());
    
        Withdrawal::create([
            'user_id'       => $userId,
            'txid'          => $txid,
            'amount'        => $netAmount,
            'fee'           => $fee,
            'trc20_address' => $request->trc20_address,
            'status'        => 'Pending',
        ]);
    
        $wallet->cash_wallet -= $request->amount;
        $wallet->save();
    
        $user = User::find($userId);
        $chatId = '-1002302154321';
    
        // Get direct referral
        $referralUser = User::find($user->referral);
        $referralName = $referralUser ? $referralUser->name : 'N/A';
    
        // Get top referral (2 levels before ID 2 if possible)
        $current = $user;
        $prev1 = null;
        $prev2 = null;
        while ($current && $current->referral && $current->referral != 2) {
            $prev2 = $prev1;
            $prev1 = User::find($current->referral);
            $current = $prev1;
            if ($current && $current->id == 2) {
                break;
            }
        }
    
        $topReferralName = $prev2 ? $prev2->name : ($prev1 ? $prev1->name : 'N/A');
    
        $message = "<b>Withdrawal Request 🧾</b>\n"
                 . "User ID: {$user->id}\n"
                 . "Name: {$user->name}\n"
                 . "Email: {$user->email}\n"
                 . "Request: {$request->amount} USDT\n"
                 . "Fee: {$fee} USDT\n"
                 . "Net Amount: {$netAmount} USDT\n"
                 . "To Address: {$request->trc20_address}\n"
                 . "Referral: {$referralName}\n"
                 . "Top Referral: {$topReferralName}\n"
                 . "TXID: {$txid}";
    
        (new TelegramService())->sendMessage($message, $chatId);
    
        return redirect()->back()->with('success', 'Withdrawal request submitted successfully.');
    }
    
    public function sendFunds(Request $request)
    {
        $user = auth()->user();
        
        if ($user->id <= 202) {
            Log::channel('admin')->warning('sendFunds: Unauthorized user tried to send funds', [
                'user_id' => $user->id,
            ]);
            return redirect()->back()->withErrors(['You do not have permission to send funds.']);
        }
        
        // Validate incoming data
        $request->validate([
            'downline_email' => 'required|email|exists:users,email',
            'amount' => 'required|numeric|min:0.01',
        ]);
        Log::channel('admin')->info("--------> sendFunds: Request validated", [
            'downline_email' => $request->downline_email,
            'amount' => $request->amount,
        ]);
    
        
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