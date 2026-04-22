<?php

namespace Sanalkopru\Crm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCrmAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = (string) config('admin-panel.guard', 'admin');
        $user = $request->user($guard) ?: $request->user();

        abort_if(! $user, 403);

        if ($request->user($guard)) {
            Auth::shouldUse($guard);
        }

        return $next($request);
    }
}
