<?php

namespace App\Http\Controllers;

use App\Models\Annoucement;
use Illuminate\Http\Request;

class AnnoucementController extends Controller
{
    public function index()
    {
        $annoucements = Annoucement::orderBy('created_at', 'desc')->get();

        return view('admin.annoucements.index', compact('annoucements'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'content' => 'required|string',
            'status'  => 'required|in:0,1',
            'banner_image' => 'nullable|image|max:2048',
        ]);
    
        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = $request->file('banner_image')->store('annoucements', 'public');
        }
    
        Annoucement::create($data);
        return redirect()->back()->with('success', 'Announcement saved.');
    }
    
    public function update(Request $request, Annoucement $annoucement)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'content' => 'required|string',
            'status'  => 'required|in:0,1',
            'banner_image' => 'nullable|image|max:2048',
        ]);
    
        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = $request->file('banner_image')->store('annoucements', 'public');
        }
    
        $annoucement->update($data);
        return redirect()->route('admin.annoucement.index')->with('success', 'Announcement updated.');
    }

    
    public function edit(Annoucement $annoucement)
    {
        // If you want to edit in a separate page:
        return view('admin.annoucements.edit', compact('annoucement'));
    }
}
