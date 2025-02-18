<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Deposit;
use App\Models\Wallet;

class DepositController extends Controller
{
    /**
     * Display a list of deposit requests.
     */
    public function index()
    {
        // You may decide to only show pending requests or all requests.
        $deposits = Deposit::orderBy('created_at', 'desc')->get();
        return view('admin.deposits.index', compact('deposits'));
    }

    /**
     * Approve a deposit request.
     */
    public function approve($id)
    {
        $deposit = Deposit::findOrFail($id);
        if ($deposit->status !== 'Pending') {
            return redirect()->back()->with('error', 'This deposit has already been processed.');
        }

        // Update the deposit status.
        $deposit->status = 'Completed';
        $deposit->save();

        // Find the user's wallet; create one if it doesn't exist.
        $wallet = Wallet::firstOrNew(['user_id' => $deposit->user_id]);
        if (!$wallet->exists) {
            $wallet->cash_wallet = 0;
            $wallet->trading_wallet = 0;
            $wallet->earning_wallet = 0;
            $wallet->affiliates_wallet = 0;
        }

        // Add the deposit amount to the user's Cash Wallet.
        $wallet->cash_wallet += $deposit->amount;
        $wallet->save();

        return redirect()->back()->with('success', 'Deposit approved successfully.');
    }

    /**
     * Reject a deposit request.
     */
    public function reject($id)
    {
        $deposit = Deposit::findOrFail($id);
        if ($deposit->status !== 'Pending') {
            return redirect()->back()->with('error', 'This deposit has already been processed.');
        }

        $deposit->status = 'Rejected';
        $deposit->save();

        return redirect()->back()->with('success', 'Deposit rejected.');
    }
}
