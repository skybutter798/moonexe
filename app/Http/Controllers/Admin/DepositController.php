<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Deposit;
use App\Models\Wallet;

class DepositController extends Controller
{
    public function index(Request $request)
    {
        $query = Deposit::with('user')
            ->where('status', 'Completed')
            ->whereNotNull('external_txid')
            ->whereNotIn('id', [302, 245, 279])
            ->orderBy('created_at', 'desc');


    
        // username search
        if ($request->filled('username')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', '%'.$request->username.'%');
            });
        }
    
        // other filters
        if ($request->filled('txid')) {
            $query->where('txid', 'like', '%'.$request->txid.'%');
        }
        if ($request->filled('trc20_address')) {
            $query->where('trc20_address', 'like', '%'.$request->trc20_address.'%');
        }
        if ($request->filled('amount')) {
            // ensure we compare at 2 decimal places
            $val = number_format((float)$request->amount, 2, '.', '');
            $query->whereRaw('FORMAT(amount, 2) = ?', [$val]);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }
    
        // paginate, keep query string for filters
        $deposits = $query->paginate(15)->withQueryString();
    
        // Clone the query to calculate total amount separately
        $totalAmount = (clone $query)->sum('amount');
        
        // Paginate the original query
        $deposits = $query->paginate(15)->withQueryString();
        
        // Pass total amount to the view
        return view('admin.deposits.index', compact('deposits', 'totalAmount'));
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
