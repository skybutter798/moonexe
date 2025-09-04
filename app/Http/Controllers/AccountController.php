<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Storage;
use PragmaRX\Google2FAQRCode\Google2FA;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function index()
    {
        $user = Auth::user();
    
        $QR_Image = null;
        $secret = null;
    
        if (!$user->google2fa_secret) {
            $google2fa = new Google2FA();
            $secret = $google2fa->generateSecretKey();
            $user->google2fa_secret = $secret;
            $user->save();
        } else {
            $secret = $user->google2fa_secret;
        }
    
        // Generate QR image from secret
        $QR_Image = \Google2FA::getQRCodeInline(
            config('app.name'),
            $user->email,
            $secret
        );
    
        return view('user.account_v2', compact('user', 'QR_Image', 'secret'));
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
    
    public function changeSecurityPassword(Request $request)
    {
        $request->validate([
            'current_account_password'      => 'required',              // NEW: must enter current login password
            'security_password'             => 'required|min:6|confirmed',
        ]);
    
        $user = Auth::user();
    
        // Verify the current account login password
        if (!\Illuminate\Support\Facades\Hash::check($request->current_account_password, $user->password)) {
            return back()
                ->withErrors(['current_account_password' => 'Current account password is incorrect.'])
                ->withInput();
        }
    
        // Prevent using the same string as account password
        if (\Illuminate\Support\Facades\Hash::check($request->security_password, $user->password)) {
            return back()
            ->withErrors(['security_password' => 'Security password cannot be the same as your account login password.'])
            ->withInput();
        }
    
        $user->security_pass = $request->security_password;
        $user->save();
    
        return back()->with('success', 'Security password updated successfully.');
    }

}
