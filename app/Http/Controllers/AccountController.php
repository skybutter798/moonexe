<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\NewsletterSubscriber; // Make sure this model exists or adjust accordingly

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
