<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsNotAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->role === 'admin') {
            // Option 1: Redirect to admin dashboard
            return redirect()->route('admin.dashboard');

            // Option 2: Abort with a 403 forbidden error
            // abort(403, 'Admins are not allowed to access user pages.');
        }
        return $next($request);
    }
}
