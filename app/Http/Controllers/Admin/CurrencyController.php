<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Currency;

class CurrencyController extends Controller
{
    public function index()
    {
        $currencies = Currency::where('id','!=',1)
                          ->orderBy('created_at','asc')
                          ->get();
        return view('admin.currencies.index', compact('currencies'));
    }

    public function toggleStatus($id)
    {
        $currency = Currency::findOrFail($id);
        $currency->status = $currency->status ? 0 : 1;
        $currency->save();

        return redirect()
            ->route('admin.currencies.index')
            ->with('success', 'Currency status updated.');
    }

    /**
     * Show form to create a new currency.
     */
    public function create()
    {
        return view('admin.currencies.create');
    }

    /**
     * Store a newly created currency.
     */
    public function store(Request $request)
    {
        // Validate and ensure c_name is unique.
        $request->validate([
            'c_name' => 'required|unique:currencies,c_name',
            'status' => 'nullable|integer',
        ]);

        Currency::create([
            'c_name' => $request->c_name,
            'status' => $request->status ?? 1,
        ]);

        return redirect()->route('admin.currencies.index')
                         ->with('success', 'Currency created successfully.');
    }
}
