<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pair;
use App\Models\Currency;
use Carbon\Carbon;

class PairController extends Controller
{
    /**
     * List all currency pairs.
     */
    public function index()
    {
        // Eager load the related currencies for display.
        $pairs = Pair::with(['currency', 'pairCurrency'])->orderBy('created_at', 'desc')->get();
        return view('admin.pairs.index', compact('pairs'));
    }

    /**
     * Show form to create a new pair.
     */
    public function create()
    {
        // Get all currencies to populate our dropdown selects.
        $currencies = Currency::orderBy('c_name')->get();
        return view('admin.pairs.create', compact('currencies'));
    }

    /**
     * Store a newly created currency pair.
     */
    public function store(Request $request)
    {
        // Validate inputs. We use an HTML5 datetime-local input so we expect a format like 2025-02-10T14:30.
        $request->validate([
            'currency_id' => 'required|exists:currencies,id',
            'pair_id'     => 'required|exists:currencies,id',
            'rate'        => 'required|numeric',
            'volume'      => 'required|numeric',
            'gate_time'   => 'required|date_format:Y-m-d\TH:i',
        ]);

        // Convert the gate_time input into a Carbon instance.
        $gate_time = Carbon::createFromFormat('Y-m-d\TH:i', $request->gate_time);

        Pair::create([
            'currency_id' => $request->currency_id,
            'pair_id'     => $request->pair_id,
            'rate'        => $request->rate,
            'volume'      => $request->volume,
            'gate_time'   => $gate_time,
        ]);

        return redirect()->route('admin.pairs.index')
                         ->with('success', 'Currency pair created successfully.');
    }
}
