<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pair;
use App\Models\Currency;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PairController extends Controller
{
    
    public function index(Request $request)
    {
        // Build base query with eager‐loads
        $query = Pair::with(['currency', 'pairCurrency', 'orders'])
                 ->orderBy('created_at', 'desc');
    
        // Apply filters if present
        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }
    
        if ($request->filled('currency')) {
            $query->whereHas('currency', function($q) use ($request) {
                $q->where('c_name', 'like', '%'.$request->currency.'%');
            });
        }
    
        if ($request->filled('volume')) {
            $query->where('volume', $request->volume);
        }
    
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
    
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
    
        // Paginate and append query string so filters persist
        $pairs = $query->paginate(15)->appends($request->all());
    
        return view('admin.pairs.index', compact('pairs'));
    }

    public function create()
    {
        // 2. only currencies with status = 1
        $currencies = Currency::where('status', 1)
                              ->orderBy('c_name')
                              ->get();

        // 5a. Total trading + cash margin split by user_id
        $totalBalanceSmall = DB::table('wallets')
            ->whereBetween('user_id', [2, 201])
            ->sum(DB::raw('trading_wallet + cash_wallet'));
        
        $totalBalanceLarge = DB::table('wallets')
            ->where('user_id', '>', 202)
            ->sum(DB::raw('trading_wallet + cash_wallet'));


        // New: Total unclaimed trading margin split by user_id
        $unclaimedSmall = DB::table('orders')
            ->where('status', 'pending')
            ->whereBetween('user_id', [2, 201])
            ->sum('buy');
        $unclaimedLarge = DB::table('orders')
            ->where('status', 'pending')
            ->where('user_id', '>', 202)
            ->sum('buy');

        return view('admin.pairs.create', compact(
            'currencies',
            'totalBalanceSmall',
            'totalBalanceLarge',
            'unclaimedSmall',
            'unclaimedLarge'
        ));
    }
    
    public function edit(Pair $pair)
    {
        $currencies = Currency::where('status',1)
                              ->orderBy('c_name')
                              ->get();

        return view('admin.pairs.edit', compact('pair','currencies'));
    }
    
    public function update(Request $request, Pair $pair)
    {
        $data = $request->validate([
            'currency_id' => 'required|exists:currencies,id|not_in:1',
            'pair_id'     => 'required|in:1',
            'rate'        => 'required|numeric',
            'volume'      => 'required|numeric',
            'gate_time'   => 'required|integer',
            'end_time'    => 'required|in:6,12,18,24,30,36',
            // no status here—disable is separate
        ]);

        // keep min/max at 0
        $data['min_rate'] = 0;
        $data['max_rate'] = 0;

        $pair->update($data);

        return redirect()
            ->route('admin.pairs.index')
            ->with('success', 'Pair updated successfully.');
    }
    
    public function disable(Pair $pair)
    {
        $pair->status = 0;
        $pair->save();

        return redirect()
            ->route('admin.pairs.index')
            ->with('success', 'Pair disabled.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'currency_id' => 'required|exists:currencies,id|not_in:1',
            'pair_id'     => 'required|exists:currencies,id|in:1',
            // min_rate/max_rate always 0
            'min_rate'    => 'required|numeric',
            'rate'        => 'required|numeric',
            'volume'      => 'required|numeric',
            'gate_time'   => 'required|integer',
            'end_time'    => 'required|in:6,12,18,24,30,36',
        ]);

        Pair::create([
            'currency_id' => $request->currency_id,
            'pair_id'     => $request->pair_id,
            'min_rate'    => 0,
            'rate'        => $request->rate,
            'max_rate'    => 0,
            'volume'      => $request->volume,
            'gate_time'   => $request->gate_time,
            'end_time'    => $request->end_time,
        ]);

        return redirect()
            ->route('admin.pairs.index')
            ->with('success', 'Currency pair created successfully.');
    }
}
