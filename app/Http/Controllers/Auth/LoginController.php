<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    use AuthenticatesUsers;
    
    protected $redirectTo = '/dashboard';
    
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }
    
    protected function authenticated(Request $request, $user)
    {
        $user->last_login = now();
        $user->save();
    
        if ($user->role === 'admin') {
            return redirect('/admin/dashboard');
        }
    
        return redirect('/user-dashboard/dashboard');
    }
    
    // Override the username method to determine the field dynamically
    protected function username()
    {
        $login = request()->input('login');
        return filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
    }
    
    // Override the credentials method to build the correct credentials array
    protected function credentials(Request $request)
    {
        $login = $request->input('login');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
        return [
            $field => $login,
            'password' => $request->input('password'),
        ];
    }

    // Override the validateLogin method to validate the "login" field
    protected function validateLogin(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);
    }
}
