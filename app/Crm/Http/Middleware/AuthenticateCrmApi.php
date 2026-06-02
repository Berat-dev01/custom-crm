<?php

namespace App\Crm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Crm\Models\CrmApiToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCrmApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->resolveBearerUser($request);

        if (! $user) {
            return response()->json([
                'message' => trans('crm::messages.api.unauthenticated'),
            ], 401);
        }

        Auth::shouldUse('web');
        Auth::guard('web')->setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function resolveBearerUser(Request $request): mixed
    {
        $plainTextToken = $request->bearerToken();

        if (! $plainTextToken) {
            return null;
        }

        /** @var CrmApiToken|null $token */
        $token = CrmApiToken::query()
            ->with('user')
            ->where('token_hash', CrmApiToken::hashToken($plainTextToken))
            ->first();

        if (! $token || $token->isExpired()) {
            return null;
        }

        if (! $token->last_used_at || $token->last_used_at->diffInSeconds(now()) > 60) {
            $token->forceFill(['last_used_at' => now()])->save();
        }

        return $token->user;
    }
}
