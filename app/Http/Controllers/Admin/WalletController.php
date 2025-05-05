<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $users = User::with('wallet')
            // skip the super-admin (user #1)
            ->where('id', '!=', 1)
            ->when($search, fn($q) =>
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
            )
            // order by user ID ascending
            ->orderBy('id', 'asc')
            ->paginate(20);
    
        return view('admin.wallets.index', compact('users', 'search'));
    }

    public function edit(User $user)
    {
        $user->load('wallet');
        return view('admin.wallets.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'cash_wallet'       => 'required|numeric|min:0',
            'trading_wallet'    => 'required|numeric|min:0',
            'earning_wallet'    => 'required|numeric|min:0',
            'affiliates_wallet' => 'required|numeric|min:0',
        ]);

        $old = $user->wallet->only(array_keys($data));
        $user->wallet->update($data);
        $new = $user->wallet->only(array_keys($data));

        Log::channel('admin')->info('Wallet updated', [
            'admin_id' => auth()->id(),
            'user_id'  => $user->id,
            'old'      => $old,
            'new'      => $new,
        ]);

        return redirect()
            ->route('admin.wallets.index')
            ->with('success', 'Wallet balances updated.');
    }
}
