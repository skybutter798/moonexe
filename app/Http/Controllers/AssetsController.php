<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\Deposit;
use App\Models\Withdrawal;
use App\Models\Transfer;
use App\Models\Asset;
use Illuminate\Support\Str;

class AssetsController extends Controller
{
    public function __construct()
    {
        // Ensure the user is authenticated for every method in this controller.
        $this->middleware('auth');
    }

    public function index()
    {
        $userId = auth()->id();
        $wallets = Wallet::where('user_id', $userId)->first();

        // Ensure wallet object keys match what your Blade expects.
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

        // Fetch deposit, withdrawal, and transfer records.
        $depositRequests    = Deposit::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
        $withdrawalRequests = Withdrawal::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
        $transferRecords    = Transfer::where('user_id', $userId)->orderBy('created_at', 'desc')->get();

        // Merge only completed transactions into a single collection.
        $transactions = collect();

        // Process deposits.
        foreach ($depositRequests as $deposit) {
            if ($deposit->status === 'Completed') {
                $deposit->type = 'Deposit';
                $deposit->transaction_description = 'Deposit USDT';
                $deposit->transaction_amount = '+' . number_format($deposit->amount, 2);
                $transactions->push($deposit);
            }
        }

        // Process withdrawals.
        foreach ($withdrawalRequests as $withdrawal) {
            if ($withdrawal->status === 'Completed') {
                $withdrawal->type = 'Withdrawal';
                $withdrawal->transaction_description = 'Withdrawal USDT';
                $withdrawal->transaction_amount = '-' . number_format($withdrawal->amount, 2);
                $transactions->push($withdrawal);
            }
        }

        // Process transfers.
        foreach ($transferRecords as $transfer) {
            if ($transfer->status === 'Completed') {
                $transfer->type = 'Transfer';
                // Map wallet keys to user-friendly names.
                $walletMapping = [
                    'earning_wallet'    => 'Earning',
                    'affiliates_wallet' => 'Affiliates',
                    'cash_wallet'       => 'Cash',
                    'trading_wallet'    => 'Trading',
                ];
                $fromReadable = $walletMapping[$transfer->from_wallet] ?? ucfirst($transfer->from_wallet);
                $toReadable   = $walletMapping[$transfer->to_wallet] ?? ucfirst($transfer->to_wallet);
                $transfer->transaction_description = "Transfer [{$fromReadable} -> {$toReadable}]";
                $transfer->transaction_amount = number_format($transfer->amount, 2);
                $transactions->push($transfer);
            }
        }

        // Sort transactions descending by created_at.
        $transactions = $transactions->sortByDesc('created_at');

        // Fetch dynamic assets for the current user.
        $assets = Asset::where('user_id', $userId)->orderBy('currency')->get();

        return view('user.assets', [
            'title'              => 'My Assets',
            'wallets'            => $wallets,
            'total_balance'      => $total_balance,
            'depositRequests'    => $depositRequests,
            'withdrawalRequests' => $withdrawalRequests,
            'transactions'       => $transactions,
            'assets'             => $assets, // Pass the dynamic assets
        ]);
    }

    /**
     * Process deposit requests.
     */
    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $userId = auth()->id();

        // Use a fixed (sample) TRC20 address for deposits (not editable in the form)
        $sampleTRC20 = 'TR9wHy8rF89a59gD3dmMPhrtPhtu6n5U5H';

        // Generate a random transaction id (txid)
        $txid = 'd_' . uniqid();

        Deposit::create([
            'user_id'       => $userId,
            'txid'          => $txid,
            'amount'        => $request->amount,
            'trc20_address' => $sampleTRC20,
            'status'        => 'Pending',
        ]);

        // Optionally: Do not update the wallet balance until deposit confirmation

        return redirect()->back()->with('success', 'Deposit request submitted successfully.');
    }

    /**
     * Process withdrawal requests.
     */
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
            return redirect()->back()->withErrors('Insufficient balance in cash wallet.');
        }

        // Generate a random txid for withdrawal.
        $txid = 'w_' . uniqid();

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
    
    public function transfer(Request $request)
    {
        $request->validate([
            'transfer_type' => 'required|in:earning_to_cash,affiliates_to_cash,cash_to_trading',
            'amount'        => 'required|numeric|min:0.01',
        ]);

        $userId = auth()->id();
        $wallet = Wallet::where('user_id', $userId)->first();

        if (!$wallet) {
            return redirect()->back()->withErrors('Wallet not found.');
        }

        $amount = (float)$request->amount;
        $from_wallet = '';
        $to_wallet   = '';

        if ($request->transfer_type == 'earning_to_cash') {
            if ($wallet->earning_wallet < $amount) {
                return redirect()->back()->withErrors('Insufficient balance in Earning Wallet.');
            }
            // Subtract from earning and add to cash.
            $wallet->earning_wallet -= $amount;
            $wallet->cash_wallet += $amount;
            $from_wallet = 'earning_wallet';
            $to_wallet   = 'cash_wallet';
        } elseif ($request->transfer_type == 'affiliates_to_cash') {
            if ($wallet->affiliates_wallet < $amount) {
                return redirect()->back()->withErrors('Insufficient balance in Affiliates Wallet.');
            }
            // Subtract from affiliates and add to cash.
            $wallet->affiliates_wallet -= $amount;
            $wallet->cash_wallet += $amount;
            $from_wallet = 'affiliates_wallet';
            $to_wallet   = 'cash_wallet';
        } elseif ($request->transfer_type == 'cash_to_trading') {
            if ($wallet->cash_wallet < $amount) {
                return redirect()->back()->withErrors('Insufficient balance in Cash Wallet.');
            }
            // Subtract from cash and add to trading.
            $wallet->cash_wallet -= $amount;
            $wallet->trading_wallet += $amount;
            $from_wallet = 'cash_wallet';
            $to_wallet   = 'trading_wallet';
        }

        $wallet->save();

        // Create a transfer record.
        $txid = 't_' . uniqid();
        Transfer::create([
            'user_id'     => $userId,
            'txid'        => $txid,
            'from_wallet' => $from_wallet,
            'to_wallet'   => $to_wallet,
            'amount'      => $amount,
            'status'      => 'Completed',
        ]);

        return redirect()->back()->with('success', 'Transfer completed successfully.');
    }
}
