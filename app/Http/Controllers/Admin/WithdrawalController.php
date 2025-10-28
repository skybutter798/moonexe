<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Withdrawal;
use App\Models\Wallet;
use App\Services\TelegramService;
use App\Models\User;
use App\Exports\WithdrawalsExport;
use Maatwebsite\Excel\Facades\Excel;

class WithdrawalController extends Controller
{
    public function index(Request $request)
    {
        $query = Withdrawal::with('user')
            ->whereNotIn('id', [61, 63])
            ->whereHas('user', function ($q) {
                $q->where('id', '!=', 665);
            })
            ->orderBy('created_at', 'desc');

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

        $withdrawals = $query->paginate(15)->withQueryString();

        return view('admin.withdrawals.index', compact('withdrawals'));
    }

    // ✅ Excel export
    public function export(Request $request)
    {
        $filters = $request->all();
        return Excel::download(new WithdrawalsExport($filters), 'withdrawals_'.now()->format('Ymd_His').'.xlsx');
    }

    public function approve($id)
    {
        $withdrawal = Withdrawal::findOrFail($id);
        if ($withdrawal->status !== 'Pending') {
            return redirect()->back()->with('error', 'This withdrawal has already been processed.');
        }
    
        $withdrawal->status = 'Completed';
        $withdrawal->save();
    
        $user = User::find($withdrawal->user_id);
        $chatId = '-1002643026089';
    
        // Get direct referral
        $referralUser = User::find($user->referral);
        $referralName = $referralUser ? $referralUser->name : 'N/A';
    
        // Get top referral (2 levels up max before user ID 2)
        $current = $user;
        $prev1 = null;
        $prev2 = null;
        while ($current && $current->referral && $current->referral != 2) {
            $prev2 = $prev1;
            $prev1 = User::find($current->referral);
            $current = $prev1;
            if ($current && $current->id == 2) break;
        }
        $topReferralName = $prev2 ? $prev2->name : ($prev1 ? $prev1->name : 'N/A');
    
        $scanLink = "https://tronscan.org/#/address/{$withdrawal->trc20_address}";
    
        $message = "<b>Withdrawal Approved ✅</b>\n"
                 . "User ID: {$user->id}\n"
                 . "Name: {$user->name}\n"
                 . "Email: {$user->email}\n"
                 . "Amount: {$withdrawal->amount} USDT\n"
                 . "Fee: {$withdrawal->fee} USDT\n"
                 . "Referral: {$referralName}\n"
                 . "Top Referral: {$topReferralName}\n"
                 . "Address: <a href=\"{$scanLink}\">{$withdrawal->trc20_address}</a>\n"
                 . "TXID: {$withdrawal->txid}";
    
        (new TelegramService())->sendMessage($message, $chatId);
    
        return redirect()->back()->with('success', 'Withdrawal approved successfully.');
    }

    public function reject($id)
    {
        $withdrawal = Withdrawal::findOrFail($id);
        if ($withdrawal->status !== 'Pending') {
            return redirect()->back()->with('error', 'This withdrawal has already been processed.');
        }
    
        $wallet = Wallet::where('user_id', $withdrawal->user_id)->first();
        if ($wallet) {
            $wallet->cash_wallet += ($withdrawal->amount + $withdrawal->fee);
            $wallet->save();
        }
    
        $withdrawal->status = 'Rejected';
        $withdrawal->save();
    
        $user = User::find($withdrawal->user_id);
        $chatId = '-1002643026089';
    
        // Get direct referral
        $referralUser = User::find($user->referral);
        $referralName = $referralUser ? $referralUser->name : 'N/A';
    
        // Get top referral (up to 2 levels)
        $current = $user;
        $prev1 = null;
        $prev2 = null;
        while ($current && $current->referral && $current->referral != 2) {
            $prev2 = $prev1;
            $prev1 = User::find($current->referral);
            $current = $prev1;
            if ($current && $current->id == 2) break;
        }
        $topReferralName = $prev2 ? $prev2->name : ($prev1 ? $prev1->name : 'N/A');
    
        $message = "<b>Withdrawal Rejected ❌</b>\n"
                 . "User ID: {$user->id}\n"
                 . "Name: {$user->name}\n"
                 . "Email: {$user->email}\n"
                 . "Refunded: " . number_format($withdrawal->amount + $withdrawal->fee, 2) . " USDT\n"
                 . "Referral: {$referralName}\n"
                 . "Top Referral: {$topReferralName}\n"
                 . "Address: {$withdrawal->trc20_address}\n"
                 . "TXID: {$withdrawal->txid}";
    
        (new TelegramService())->sendMessage($message, $chatId);
    
        return redirect()->back()->with('success', 'Withdrawal rejected and refunded.');
    }

}
