<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard: `permission:production.report.create`.
 *
 * All listed permissions must be held (AND), which keeps route declarations
 * explicit rather than relying on role names.
 */
class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        foreach ($permissions as $permission) {
            if (! $user->hasPermission($permission)) {
                abort(403, 'Anda tidak memiliki izin untuk tindakan ini.');
            }
        }

        return $next($request);
    }
}
