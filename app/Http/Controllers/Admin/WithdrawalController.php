<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Withdrawal;
use App\Models\Wallet;
use App\Services\TelegramService;
use App\Models\User;

class WithdrawalController extends Controller
{

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

    public function approve($id)
    {
        $withdrawal = Withdrawal::findOrFail($id);
        if ($withdrawal->status !== 'Pending') {
            return redirect()->back()->with('error', 'This withdrawal has already been processed.');
        }

        // Update the withdrawal status.
        $withdrawal->status = 'Completed';
        $withdrawal->save();
        
        // Notify Telegram
        $user = User::find($withdrawal->user_id);
        $chatId = '-1002302154321'; // your group ID
        
        $scanLink = "https://tronscan.org/#/address/{$withdrawal->trc20_address}";

        $message = "<b>Withdrawal Approved ✅</b>\n"
                 . "User ID: {$user->id}\n"
                 . "Name: {$user->name}\n"
                 . "Email: {$user->email}\n"
                 . "Amount: {$withdrawal->amount} USDT\n"
                 . "Fee: {$withdrawal->fee} USDT\n"
                 . "Address: <a href=\"{$scanLink}\">{$withdrawal->trc20_address}</a>\n"
                 . "TXID: {$withdrawal->txid}";

        
        $telegram = new TelegramService();
        $telegram->sendMessage($message, $chatId);


        return redirect()->back()->with('success', 'Withdrawal approved successfully.');
    }

    public function reject($id)
    {
        $withdrawal = Withdrawal::findOrFail($id);
    
        if ($withdrawal->status !== 'Pending') {
            return redirect()->back()->with('error', 'This withdrawal has already been processed.');
        }
    
        // Refund: add full requested amount (net + fee) back to wallet
        $wallet = \App\Models\Wallet::where('user_id', $withdrawal->user_id)->first();
        if ($wallet) {
            $wallet->cash_wallet += ($withdrawal->amount + $withdrawal->fee);
            $wallet->save();
        }
    
        // Update status to rejected
        $withdrawal->status = 'Rejected';
        $withdrawal->save();
    
        // Notify Telegram
        $user = \App\Models\User::find($withdrawal->user_id);
        $chatId = '-1002302154321';
    
        $message = "<b>Withdrawal Rejected ❌</b>\n"
                 . "User ID: {$user->id}\n"
                 . "Name: {$user->name}\n"
                 . "Email: {$user->email}\n"
                 . "Refunded: " . number_format($withdrawal->amount + $withdrawal->fee, 2) . " USDT\n"
                 . "Address: {$withdrawal->trc20_address}\n"
                 . "TXID: {$withdrawal->txid}";
    
        $telegram = new \App\Services\TelegramService();
        $telegram->sendMessage($message, $chatId);
    
        return redirect()->back()->with('success', 'Withdrawal rejected and refunded.');
    }

}
