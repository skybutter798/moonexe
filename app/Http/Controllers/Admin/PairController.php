<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pair;
use App\Models\Currency;
use Carbon\Carbon;

class PairController extends Controller
{
    
    public function index()
    {
        // Eager load the related currencies for display.
        $pairs = Pair::with(['currency', 'pairCurrency'])->orderBy('created_at', 'desc')->get();
        return view('admin.pairs.index', compact('pairs'));
    }

    public function create()
    {
        // Get all currencies for dropdowns.
        $currencies = Currency::orderBy('c_name')->get();
        
        // Get the total cash wallet balance from all users.
        $totalBalance = \DB::table('wallets')->sum('trading_wallet');
        $totalCash = \DB::table('wallets')->sum('cash_wallet');
    
        return view('admin.pairs.create', compact('currencies', 'totalBalance', 'totalCash'));
    }
    
    public function store(Request $request)
    {
        // Validate inputs including end_time which must be one of 1,2,3,4,5,6.
        $request->validate([
            'currency_id' => 'required|exists:currencies,id|not_in:1',
            'pair_id'     => 'required|exists:currencies,id|in:1',
            'min_rate'    => 'required|numeric',
            'earning_gap' => 'required|numeric', // Use earning_gap instead of rate
            'volume'      => 'required|numeric',
            'gate_time'   => 'required|integer',
            'end_time'    => 'required|in:6,12,18,24,30,36', // Match form values
        ]);
    
        $max_rate = $request->min_rate + $request->earning_gap;

        Pair::create([
            'currency_id' => $request->currency_id,
            'pair_id'     => $request->pair_id,
            'min_rate'    => $request->min_rate,
            'rate'        => $request->earning_gap, // Use earning_gap instead of rate
            'max_rate'    => $max_rate,
            'volume'      => $request->volume,
            'gate_time'   => $request->gate_time,
            'end_time'    => $request->end_time,
        ]);
    
        return redirect()->route('admin.pairs.index')
                         ->with('success', 'Currency pair created successfully.');
    }
}
