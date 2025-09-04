<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Deposit;
use App\Models\Withdrawal;
use App\Models\Transfer;

class AdminToolController extends Controller
{

    public function index()
    {
        return view('admin.tools.index');
    }
    
    public function walletReport(Request $request)
    {
        $request->validate([
            'user_range' => 'required|string',
        ]);
    
        $input = $request->input('user_range');
        $parts = collect(explode(',', $input))->map(fn($v) => trim($v))->filter();
    
        $ids = collect();
    
        foreach ($parts as $item) {
            if (is_numeric($item)) {
                $ids->push((int) $item);
            } else {
                $user = User::where('name', $item)->first();
                if ($user) {
                    $ids->push($user->id);
                }
            }
        }
    
        if ($ids->isEmpty()) {
            return redirect()->back()->with('error', 'No valid users found.');
        }
    
        // Call artisan with processed ID list
        Artisan::call('wallets:recalculate', [
            'userRange' => $ids->implode(','),
        ]);
    
        $output = Artisan::output();
    
        Log::channel('admin')->info('[WalletTool] Recalculate executed', [
            'input' => $input,
            'resolved_ids' => $ids->toArray(),
            'admin' => auth()->user()->id,
        ]);
    
        return view('admin.tools.index', [
            'output' => $output,
        ]);
    }
    
    public function realWalletBreakdown(Request $request)
    {
        $userKey = trim($request->input('user_key'));
    
        Artisan::call('check:real-wallet', [
            'user_key' => $userKey,
            '--no-telegram' => true, // don't send to Telegram on UI request
        ]);
    
        $output = Artisan::output();
    
        Log::channel('admin')->info('[WalletTool] RealWalletBreakdown executed', [
            'user_key' => $userKey,
            'admin' => auth()->user()->id,
        ]);
    
        return view('admin.tools.index', [
            'output' => $output,
        ]);
    }
    
    public function walletFlowReport(Request $request)
    {
        $request->validate([
            'flow_user' => 'required|string',
        ]);
    
        $input = trim($request->input('flow_user'));
    
        // Allow using name or ID
        $user = is_numeric($input)
            ? User::find($input)
            : User::where('name', $input)->first();
    
        if (!$user) {
            return redirect()->back()->with('error', "User not found: $input");
        }
    
        Artisan::call('wallet:flow', [
            'user_id' => $user->id,
        ]);
    
        $output = Artisan::output();
    
        Log::channel('admin')->info('[WalletTool] FlowReport executed', [
            'input' => $input,
            'resolved_id' => $user->id,
            'admin' => auth()->user()->id,
        ]);
    
        return view('admin.tools.index', [
            'output' => $output,
        ]);
    }
    
    public function walletHistory(Request $request)
    {
        $request->validate([
            'user_key' => 'required|string',
            'wallet_type' => 'required|in:cash_wallet',
        ]);
        
        $groupByDate = $request->has('group_by_date');
    
        $key = trim($request->input('user_key'));
        $walletType = $request->input('wallet_type');
    
        $user = User::where('id', $key)
                    ->orWhere('name', $key)
                    ->orWhere('email', $key)
                    ->first();
    
        if (!$user) {
            return redirect()->back()->with('error', "User not found: $key");
        }
    
        $history = collect();
    
        // ✅ Deposits (+)
        $deposits = Deposit::where('user_id', $user->id)->where('status', 'Completed')->get();
        foreach ($deposits as $d) {
            $history->push([
                'date' => $d->updated_at->toDateTimeString(),
                'txid' => $d->txid,
                'direction' => 'In',
                'amount' => number_format($d->amount, 2),
                'fee' => number_format($d->fee ?? 0, 2),
                'method' => 'Deposit',
                'remark' => '-',
                'balance_change' => $d->amount,
            ]);
        }
    
        // ✅ Withdrawals (–) — exclude Rejected
        $withdrawals = Withdrawal::where('user_id', $user->id)->where('status', '!=', 'Rejected')->get();
        foreach ($withdrawals as $w) {
            $total = $w->amount + ($w->fee ?? 0);
            $history->push([
                'date' => $w->updated_at->toDateTimeString(),
                'txid' => $w->txid,
                'direction' => 'Out',
                'amount' => number_format($w->amount, 2),
                'fee' => number_format($w->fee ?? 0, 2),
                'method' => 'Withdrawal',
                'remark' => '-',
                'balance_change' => -$total,
            ]);
        }
    
        // ✅ Transfers
        $transfers = Transfer::where('user_id', $user->id)->where('status', 'Completed')->get();
        foreach ($transfers as $t) {
            $direction = null;
            $change = 0;
            $remark = $t->remark ?? '-';
    
            // Aff/Earn → Cash (+)
            if (in_array($t->from_wallet, ['affiliates_wallet', 'earning_wallet']) && $t->to_wallet === 'cash_wallet') {
                $direction = 'In';
                $change = $t->amount;
            }
    
            // Cash → Trading (package only) (–)
            elseif ($t->from_wallet === 'cash_wallet' && $t->to_wallet === 'trading_wallet' && $t->remark === 'package') {
                $direction = 'Out';
                $change = -$t->amount;
            }
    
            // Cash ↔ Cash (downline) – signed
            elseif ($t->from_wallet === 'cash_wallet' && $t->to_wallet === 'cash_wallet' && $t->remark === 'downline') {
                if ($t->amount < 0) {
                    $direction = 'Out';
                } elseif ($t->amount > 0) {
                    $direction = 'In';
                }
                $change = $t->amount;
            }
    
            if ($direction) {
                $history->push([
                    'date' => $t->updated_at->toDateTimeString(),
                    'txid' => $t->txid ?? $t->id,
                    'direction' => $direction,
                    'amount' => number_format(abs($t->amount), 2),
                    'fee' => '0.00',
                    'method' => 'Transfer',
                    'remark' => $remark,
                    'balance_change' => $change,
                ]);
            }
        }
    
        // Sort and apply running balance
        $sorted = $history->sortBy('date')->values();
    
        $balance = 0;
        $final = [];
        
        if ($groupByDate) {
            $grouped = $sorted->groupBy(fn($row) => substr($row['date'], 0, 10)); // group by YYYY-MM-DD
        
            foreach ($grouped as $date => $items) {
                $dailyIn = $items->where('direction', 'In')->sum(fn($i) => $i['balance_change']);
                $dailyOut = $items->where('direction', 'Out')->sum(fn($i) => abs($i['balance_change']));
                $dailyFee = $items->sum(fn($i) => floatval($i['fee']));
                $balance += $dailyIn - $dailyOut;
        
                $final[] = [
                    'date' => $date,
                    'txid' => '—', // no individual txid
                    'direction' => 'Grouped',
                    'amount' => number_format($dailyIn, 2) . ' / -' . number_format($dailyOut, 2),
                    'fee' => number_format($dailyFee, 2),
                    'method' => 'Multiple',
                    'remark' => 'Grouped total',
                    'balance' => number_format($balance, 2),
                ];
            }
        } else {
            foreach ($sorted as $row) {
                $balance += $row['balance_change'];
                $final[] = [
                    'date' => $row['date'],
                    'txid' => $row['txid'],
                    'direction' => $row['direction'],
                    'amount' => $row['amount'],
                    'fee' => $row['fee'],
                    'method' => $row['method'],
                    'remark' => $row['remark'],
                    'balance' => number_format($balance, 2),
                ];
            }
        }
    
        return view('admin.tools.wallet_history', [
            'user' => $user,
            'walletType' => $walletType,
            'history' => $final,
        ]);
    }
}