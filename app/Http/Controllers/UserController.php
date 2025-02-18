<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{

    public function index()
    {
        $users = User::with(['wallet', 'upline'])->get();
        return view('app.users.index', compact('users'));
    }


    public function disable($id)
    {
        $user = User::findOrFail($id);
        $user->status = 0; // Set status to disabled
        $user->save();

        return redirect()->route('users.index')->with('success', 'User disabled successfully.');
    }
    
    public function enable($id)
    {
        $user = User::findOrFail($id);
        $user->status = 1; // Set status to active
        $user->save();
    
        return redirect()->route('users.index')->with('success', 'User enabled successfully.');
    }
    
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'referral_code' => 'nullable|string|max:255',
            'status' => 'required|boolean',
            'referral' => 'nullable|exists:users,id', // Ensure the referral exists in the users table
        ]);
    
        $user = User::findOrFail($id);
        $user->update([
            'name' => $request->name,
            'referral_code' => $request->referral_code,
            'status' => $request->status,
            'referral' => $request->referral, // Update referral
        ]);
    
        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }


}
