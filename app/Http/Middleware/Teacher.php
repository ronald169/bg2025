<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Teacher
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || !Auth::user()->isTeacher()) {
            abort(403, 'Unauthorized - Teacher access required.');
        }

        return $next($request);
    }
}
