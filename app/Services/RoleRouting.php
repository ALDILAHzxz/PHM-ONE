<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Where a user lands after login (master specification section 6.5).
 * There is no intermediate "choose your dashboard" screen.
 */
class RoleRouting
{
    public function homeRouteFor(User $user): string
    {
        $route = $user->activeRole()?->default_route;

        if ($route !== null && Route::has($route)) {
            return $route;
        }

        // A user with no usable role still gets a real page rather than a loop.
        return 'profile';
    }

    public function homeUrlFor(User $user): string
    {
        return route($this->homeRouteFor($user));
    }
}
