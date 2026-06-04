<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = config('admin-panel.guard', 'admin');

        if (! Auth::guard($guard)->check()) {
            return redirect()->route(config('admin-panel.login_route', 'admin.login'));
        }

        Auth::shouldUse($guard);

        return $next($request);
    }
}
