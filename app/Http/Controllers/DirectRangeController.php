<?php

namespace App\Http\Controllers;

use App\Models\DirectRange;
use App\Models\MatchingRange;
use Illuminate\Http\Request;

class DirectRangeController extends Controller
{
    /**
     * Display a listing of the direct ranges.
     */
    public function index()
    {
        $directranges = DirectRange::all();
        $matchingranges = MatchingRange::all();
        
        return view('app.directrange.index', compact('directranges','matchingranges'));
    }

    /**
     * Store a newly created direct range in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'min'        => 'required|numeric',
            'max'        => 'required|numeric',
            'percentage' => 'required|numeric',
        ]);

        DirectRange::create($request->only(['name', 'min', 'max', 'percentage']));

        return redirect()
            ->route('admin.directranges.index')
            ->with('success', 'Direct range created successfully.');
    }

    /**
     * Update the specified direct range in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'min'        => 'required|numeric',
            'max'        => 'required|numeric',
            'percentage' => 'required|numeric',
        ]);

        $range = DirectRange::findOrFail($id);
        $range->update($request->only(['name', 'min', 'max', 'percentage']));

        return redirect()
            ->route('admin.directranges.index')
            ->with('success', 'Direct range updated successfully.');
    }

    /**
     * Remove the specified direct range from storage.
     */
    public function destroy($id)
    {
        DirectRange::destroy($id);

        return redirect()
            ->route('admin.directranges.index')
            ->with('success', 'Direct range deleted successfully.');
    }
}
