<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{

    public function index()
    {
        $user = auth()->user();
        $title = 'Dashboard';
        if ($user->role == 'admin') {
            return view('admin.dashboard', compact('title'));
        }
        
        return view('user.dashboard', compact('title'));
    }
}