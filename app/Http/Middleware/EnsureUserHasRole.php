<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard: `role:operator,supervisor`.
 *
 * Role checks live here (server side) in addition to the per-permission gates,
 * so a workspace route cannot be reached by typing its URL.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if (! $user->hasAnyRole($roles)) {
            abort(403, 'Anda tidak memiliki akses ke bagian ini.');
        }

        return $next($request);
    }
}
