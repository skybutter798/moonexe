<?php

namespace App\Http\Controllers;

use App\Models\MatchingRange;
use Illuminate\Http\Request;

class MatchingRangeController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'min'        => 'required|numeric',
            'max'        => 'required|numeric',
            'percentage' => 'required|numeric',
        ]);

        MatchingRange::create($request->only(['name','min','max','percentage']));

        return redirect()
            ->route('admin.directranges.index')
            ->with('success','Matching range created successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'min'        => 'required|numeric',
            'max'        => 'required|numeric',
            'percentage' => 'required|numeric',
        ]);

        MatchingRange::findOrFail($id)
            ->update($request->only(['name','min','max','percentage']));

        return redirect()
            ->route('admin.directranges.index')
            ->with('success','Matching range updated successfully.');
    }

    public function destroy($id)
    {
        MatchingRange::destroy($id);

        return redirect()
            ->route('admin.directranges.index')
            ->with('success','Matching range deleted successfully.');
    }
}
