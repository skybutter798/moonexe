<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Currency;

class CurrencyController extends Controller
{
    /**
     * List all currencies.
     */
    public function index()
    {
        $currencies = Currency::orderBy('created_at', 'desc')->get();
        return view('admin.currencies.index', compact('currencies'));
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
