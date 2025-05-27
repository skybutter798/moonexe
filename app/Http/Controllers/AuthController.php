<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    public function verify2FA(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $google2fa = new Google2FA();
        $isValid = $google2fa->verifyKey($user->google2fa_secret, $request->otp);

        if ($isValid) {
            $user->two_fa_enabled = true;
            $user->save();

            return redirect()->back()->with('success', '✅ Two-Factor Authentication has been enabled.');
        }

        return redirect()->back()->withErrors(['otp' => 'Invalid code. Please try again.']);
    }
    
    public function disable2FA(Request $request)
    {
        $user = auth()->user();
    
        $user->google2fa_secret = null;
        $user->two_fa_enabled = false;
        $user->save();
    
        return redirect()->back()->with('success', '2FA has been disabled.');
    }

}
