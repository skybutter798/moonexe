<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Models\User;


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
}