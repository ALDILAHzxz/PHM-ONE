<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\RoleRouting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(RoleRouting $routing): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->to($routing->homeUrlFor(Auth::user()));
        }

        return view('auth.login');
    }

    public function help(): View
    {
        return view('auth.help');
    }

    public function logout(Request $request, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            $audit->log(
                $user,
                'auth.logout',
                'User',
                $user->id,
                sprintf('%s keluar dari PHM One.', $user->full_name),
                visibleInFeed: false,
            );
        }

        Auth::logout();

        // Session data is discarded; production records are untouched.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah keluar dari PHM One.');
    }
}
