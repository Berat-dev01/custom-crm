<?php

namespace Sanalkopru\Crm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class EnsureCrmAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = (string) config('admin-panel.guard', 'admin');
        $user = $request->user($guard) ?: $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                abort(403);
            }

            $loginRoute = (string) config('admin-panel.login_route', 'admin.login');

            abort_unless(Route::has($loginRoute), 403);

            return redirect()->guest(route($loginRoute));
        }

        if ($request->user($guard)) {
            Auth::shouldUse($guard);
        }

        return $next($request);
    }
}
