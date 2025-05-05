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
    public function index(Request $request)
    {
        $query = Withdrawal::with('user')->orderBy('created_at', 'desc');

        // Username
        if ($request->filled('username')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', '%'.$request->username.'%');
            });
        }

        // TXID
        if ($request->filled('txid')) {
            $query->where('txid', 'like', '%'.$request->txid.'%');
        }

        // TRC20 address
        if ($request->filled('trc20_address')) {
            $query->where('trc20_address', 'like', '%'.$request->trc20_address.'%');
        }

        // Amount (match 2-decimal format)
        if ($request->filled('amount')) {
            $val = number_format((float)$request->amount, 2, '.', '');
            $query->whereRaw('FORMAT(amount, 2) = ?', [$val]);
        }

        // Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Date
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $withdrawals = $query->paginate(15)->withQueryString();

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
