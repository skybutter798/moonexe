<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Storage;


class AccountController extends Controller
{
    /**
     * Display the user's account page.
     */
    public function index()
    {
        // Retrieve the authenticated user.
        $user = Auth::user();

        // Pass the user object to the account view.
        return view('user.account_v2', compact('user'));
    }
    
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validatedData = $request->validate([
            //'name'   => 'required|string|max:255',
            //'email'  => 'required|email|max:255|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // 2MB Max
        ]);

        if ($request->hasFile('avatar')) {
            // Optionally delete the old avatar if exists
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            // Store the new avatar in a directory called 'avatars'
            $path = $request->file('avatar')->store('avatars', 'public');
            $validatedData['avatar'] = $path;
            $user->update($validatedData);
        } else {
            // Update only non-avatar fields if necessary, or do nothing if no updates are made.
        }

        $user->update($validatedData);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Handle the newsletter subscription form submission.
     */
    public function subscribeNewsletter(Request $request)
    {
        // Validate the incoming email field.
        $request->validate([
            'email' => 'required|email',
        ]);

        // Process the subscription.
        // Here we're using a NewsletterSubscriber model as an example.
        NewsletterSubscriber::create([
            'email' => $request->email,
        ]);

        // Redirect back with a success message.
        return redirect()->back()->with('success', 'Subscribed successfully to our newsletter.');
    }
    
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);
    
        $user = Auth::user();
    
        // Verify that the current password matches
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }
    
        // Update the password
        $user->password = Hash::make($request->new_password);
        $user->save();
    
        return back()->with('success', 'Password changed successfully.');
    }
}
