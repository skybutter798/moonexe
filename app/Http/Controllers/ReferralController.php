<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function index()
    {
        // Here, you'd fetch data such as referral ID, link, existing referrals, etc.
        // For now we return a static example.

        return view('user.referral', [
            'title' => 'My Referral Program',
        ]);
    }
}
