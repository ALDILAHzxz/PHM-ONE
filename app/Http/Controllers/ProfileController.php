<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\RoleRouting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        $user = request()->user()->load(['department', 'workShift', 'roles']);

        return view('profile', [
            'user' => $user,
            'activeRole' => $user->activeRole(),
        ]);
    }

    /**
     * Switch workspace for a multi-role employee.
     * A role the user does not hold is rejected server side.
     */
    public function switchRole(Request $request, RoleRouting $routing, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string'],
        ]);

        $user = $request->user();
        $role = $user->roles->firstWhere('code', $validated['role']);

        if ($role === null) {
            abort(403, 'Anda tidak memiliki peran tersebut.');
        }

        session(['phm.active_role' => $role->code]);

        $audit->log(
            $user,
            'auth.role_switched',
            'User',
            $user->id,
            sprintf('%s berpindah ke ruang kerja %s.', $user->full_name, $role->name),
            ['role' => $role->code],
            visibleInFeed: false,
        );

        return redirect()->to($routing->homeUrlFor($user->refresh()))
            ->with('status', 'Anda sekarang bekerja sebagai '.$role->name.'.');
    }

    public function updatePassword(Request $request, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate(
            [
                'current_password' => ['required', 'string'],
                'password' => ['required', 'confirmed', Password::min(8)],
            ],
            [
                'current_password.required' => 'Masukkan kata sandi Anda saat ini.',
                'password.required' => 'Masukkan kata sandi baru.',
                'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
                'password.min' => 'Kata sandi baru minimal 8 karakter.',
            ],
        );

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Kata sandi saat ini salah.'])
                ->with('error', 'Kata sandi tidak diubah.');
        }

        $user->forceFill(['password' => $validated['password']])->save();

        $audit->log(
            $user,
            'auth.password_changed',
            'User',
            $user->id,
            sprintf('%s mengubah kata sandi akunnya.', $user->full_name),
            visibleInFeed: false,
        );

        return back()->with('status', 'Kata sandi berhasil diubah.');
    }
}
