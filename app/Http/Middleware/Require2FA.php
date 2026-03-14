<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Require2FA
{
    /**
     * Redirect to the security settings page when the authenticated user
     * has not yet enabled two-factor authentication.
     *
     * This middleware should be applied to any route that creates or
     * modifies sensitive platform resources (servers, projects).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasEnabledTwoFactorAuthentication()) {
            return redirect()->route('security.edit')
                ->with('status', '2fa-required');
        }

        return $next($request);
    }
}
