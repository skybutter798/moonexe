<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Deposit;
use App\Models\Wallet;
use App\Exports\DepositsExport;
use Maatwebsite\Excel\Facades\Excel;


class DepositController extends Controller
{
    public function index(Request $request)
    {
        $query = Deposit::with(['user:id,name,email,trx_address'])
            ->where('status', 'Completed')
            ->whereNotNull('external_txid')
            ->whereNotIn('id', [279, 299, 281, 272, 262, 256, 252, 234])
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
            $val = number_format((float)$request->amount, 2, '.', '');
            $query->whereRaw('FORMAT(amount, 2) = ?', [$val]);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ✅ Date range filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59',
            ]);
        } elseif ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // paginate, keep query string for filters
        $deposits = $query->paginate(15)->withQueryString();

        // Clone for total
        $totalAmount = (clone $query)->sum('amount');

        return view('admin.deposits.index', compact('deposits', 'totalAmount'));
    }

    public function approve($id)
    {
        $deposit = Deposit::findOrFail($id);
        if ($deposit->status !== 'Pending') {
            return redirect()->back()->with('error', 'This deposit has already been processed.');
        }

        $deposit->status = 'Completed';
        $deposit->save();

        $wallet = Wallet::firstOrNew(['user_id' => $deposit->user_id]);
        if (!$wallet->exists) {
            $wallet->cash_wallet = 0;
            $wallet->trading_wallet = 0;
            $wallet->earning_wallet = 0;
            $wallet->affiliates_wallet = 0;
        }

        $wallet->cash_wallet += $deposit->amount;
        $wallet->save();

        return redirect()->back()->with('success', 'Deposit approved successfully.');
    }

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
    
    public function export(Request $request)
    {
        $filters = $request->all();
    
        return Excel::download(new DepositsExport($filters), 'deposits_export_'.now()->format('Ymd_His').'.xlsx');
    }

}

