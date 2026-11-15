<?php

namespace App\Http\Controllers;

use App\Services\RoleRouting;
use Illuminate\Http\RedirectResponse;

/**
 * Sends the signed-in user straight to their workspace.
 * There is no intermediate dashboard-selection screen.
 */
class HomeController extends Controller
{
    public function __invoke(RoleRouting $routing): RedirectResponse
    {
        return redirect()->to($routing->homeUrlFor(request()->user()));
    }
}
