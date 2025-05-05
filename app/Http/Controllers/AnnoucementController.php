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
        // Validate and force status to be present (0 or 1)
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'content' => 'required|string',
            'status'  => 'required|in:0,1',
        ]);

        Annoucement::create($data);

        return redirect()
            ->back()
            ->with('success', 'Announcement saved.');
    }
    
    public function edit(Annoucement $annoucement)
    {
        // If you want to edit in a separate page:
        return view('admin.annoucements.edit', compact('annoucement'));
    }

    public function update(Request $request, Annoucement $annoucement)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'content' => 'required|string',
            'status'  => 'required|in:0,1',
        ]);

        $annoucement->update($data);

        return redirect()
            ->route('admin.annoucement.index')
            ->with('success', 'Announcement updated.');
    }
}
