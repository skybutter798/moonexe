<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Withdrawal;
use App\Models\Wallet;

class WithdrawalController extends Controller
{
    /**
     * Display a list of withdrawal requests.
     */
    public function index()
    {
        $withdrawals = Withdrawal::orderBy('created_at', 'desc')->get();
        return view('admin.withdrawals.index', compact('withdrawals'));
    }

    /**
     * Approve a withdrawal request.
     */
    public function approve($id)
    {
        $withdrawal = Withdrawal::findOrFail($id);
        if ($withdrawal->status !== 'Pending') {
            return redirect()->back()->with('error', 'This withdrawal has already been processed.');
        }

        // Find the user's wallet.
        $wallet = Wallet::where('user_id', $withdrawal->user_id)->first();
        if (!$wallet) {
            return redirect()->back()->with('error', 'User wallet not found.');
        }

        // Check if the user has sufficient funds in the Cash Wallet.
        if ($wallet->cash_wallet < $withdrawal->amount) {
            return redirect()->back()->with('error', 'Insufficient funds in the user wallet.');
        }

        // Deduct the amount from the user's Cash Wallet.
        $wallet->cash_wallet -= $withdrawal->amount;
        $wallet->save();

        // Update the withdrawal status.
        $withdrawal->status = 'Completed';
        $withdrawal->save();

        return redirect()->back()->with('success', 'Withdrawal approved successfully.');
    }

    /**
     * Reject a withdrawal request.
     */
    public function reject($id)
    {
        $withdrawal = Withdrawal::findOrFail($id);
        if ($withdrawal->status !== 'Pending') {
            return redirect()->back()->with('error', 'This withdrawal has already been processed.');
        }

        $withdrawal->status = 'Rejected';
        $withdrawal->save();

        return redirect()->back()->with('success', 'Withdrawal rejected.');
    }
}
