<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class VerificationController extends Controller
{
    public function sendEmail(Request $request)
    {
        $user = Auth::user();
    
        $token = Str::uuid();
        DB::table('account_verifications')->updateOrInsert(
            ['user_id' => $user->id],
            ['token' => $token, 'created_at' => now()]
        );
    
        $url = route('user.verifyAccount', ['token' => $token]);
    
        Mail::send('emails.verify_account', [
            'user' => $user,
            'verificationUrl' => $url,
        ], function ($msg) use ($user) {
            $msg->to($user->email)
                ->subject('Verify Your MoonExe Account');
        });
    
        return response()->json(['message' => 'Verification email sent to ' . $user->email]);
    }


    public function verify($token)
    {
        $record = DB::table('account_verifications')->where('token', $token)->first();

        if (!$record) {
            return redirect('/')->with('error', 'Invalid or expired verification link.');
        }

        $user = User::find($record->user_id);
        if ($user && is_null($user->package)) {
            $user->package = 1;
            $user->save();
        }

        DB::table('account_verifications')->where('token', $token)->delete();

        return redirect('/')->with('success', 'Account verified. You can now trade.');
    }
}
