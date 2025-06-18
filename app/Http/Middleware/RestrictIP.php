<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestrictIP
{
    public function handle(Request $request, Closure $next)
    {
        $allowedIp = '139.180.157.192';

        if ($request->ip() !== $allowedIp) {
            return response()->json(['error' => 'Unauthorized IP'], 403);
        }

        return $next($request);
    }
}

