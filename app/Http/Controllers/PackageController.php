<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    
    public function index()
    {
        $packages = Package::all();

        return view('app.package.index', compact('packages'));
    }
    
    public function disable($id)
    {
        $package = Package::findOrFail($id);
        $package->status = 0;
        $package->save();

        return redirect()->route('packages.index')->with('success', 'Package disabled successfully.');
    }
    
    public function enable($id)
    {
        $package = Package::findOrFail($id);
        $package->status = 1;
        $package->save();

        return redirect()->route('packages.index')->with('success', 'Package enabled successfully.');
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'eshare' => 'required|numeric',
            'max_payout' => 'required|numeric',
            'profit' => 'required|numeric',
        ]);
    
        Package::create([
            'name' => $request->name,
            'eshare' => $request->eshare,
            'max_payout' => $request->max_payout,
            'profit' => $request->profit,
            'status' => 1, // Default status as enabled
        ]);
    
        return redirect()->route('packages.index')->with('success', 'Package created successfully.');
    }
    
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'eshare' => 'required|numeric',
            'max_payout' => 'required|numeric',
            'profit' => 'required|numeric',
        ]);
    
        $package = Package::findOrFail($id);
        $package->update($request->only('name', 'eshare', 'max_payout', 'profit'));
    
        return redirect()->route('packages.index')->with('success', 'Package updated successfully.');
    }

}
