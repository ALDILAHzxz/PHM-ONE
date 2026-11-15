<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\AuditLogger;
use App\Services\RoleRouting;
use App\Support\Roles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Two-step login (master specification section 06).
 *
 * Step 1 "identify"     — Nomor Induk Karyawan only, one field, one button.
 * Step 2 "authenticate" — the company authentication step for that identity.
 *
 * In demo mode step 2 verifies a seeded password. This is NOT enterprise SSO
 * and is never presented as such; see docs/AUTHENTICATION_DESIGN.md for the
 * OIDC boundary that replaces it once an identity provider is approved.
 */
class LoginForm extends Component
{
    public string $step = 'identify';

    #[Validate]
    public string $employee_number = '';

    #[Validate]
    public string $password = '';

    public bool $remember = false;

    /** Shown in step 2 so the worker can confirm it is their own account. */
    public ?string $identifiedName = null;

    public ?string $identifiedPosition = null;

    public ?string $identifiedInitials = null;

    protected function rules(): array
    {
        return [
            'employee_number' => ['required', 'string', 'max:32'],
            'password' => [$this->step === 'authenticate' ? 'required' : 'nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'employee_number.required' => 'Masukkan Nomor Induk Karyawan Anda.',
            'employee_number.max' => 'Nomor Induk Karyawan terlalu panjang.',
            'password.required' => 'Masukkan kata sandi Anda.',
        ];
    }

    // ------------------------------------------------------------------ step 1

    public function identify(): void
    {
        $this->validateOnly('employee_number');

        $this->ensureIsNotRateLimited('identify', 12);

        $user = User::where('employee_number', trim($this->employee_number))->first();

        if ($user === null || ! $user->isActive()) {
            RateLimiter::hit($this->throttleKey('identify'), 60);

            throw ValidationException::withMessages([
                'employee_number' => 'Nomor Induk Karyawan tidak ditemukan atau akun tidak aktif.',
            ]);
        }

        $this->identifiedName = $user->full_name;
        $this->identifiedPosition = $user->position;
        $this->identifiedInitials = $user->initials();
        $this->step = 'authenticate';
        $this->password = '';
    }

    public function backToIdentify(): void
    {
        $this->step = 'identify';
        $this->password = '';
        $this->identifiedName = null;
        $this->identifiedPosition = null;
        $this->identifiedInitials = null;
        $this->resetErrorBag();
    }

    // ------------------------------------------------------------------ step 2

    public function authenticate(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited('login', 5);

        $credentials = [
            'employee_number' => trim($this->employee_number),
            'password' => $this->password,
            'status' => 'active',
        ];

        if (! Auth::attempt($credentials, $this->remember)) {
            RateLimiter::hit($this->throttleKey('login'), 60);

            throw ValidationException::withMessages([
                'password' => 'Kata sandi salah. Silakan coba lagi.',
            ]);
        }

        RateLimiter::clear($this->throttleKey('login'));
        RateLimiter::clear($this->throttleKey('identify'));

        $this->completeLogin(Auth::user(), 'password');
    }

    // --------------------------------------------------------------- demo login

    /**
     * One-click sign-in for a seeded demo identity.
     *
     * Only works while PHM_DEMO_MODE=true and only ever targets accounts with
     * is_demo = true, so it can never reach a real identity.
     */
    public function loginAsDemo(string $employeeNumber): void
    {
        abort_unless(config('phm.demo_mode'), 403, 'Mode demo tidak aktif.');

        $user = User::where('employee_number', $employeeNumber)
            ->where('is_demo', true)
            ->where('status', 'active')
            ->first();

        if ($user === null) {
            $this->addError('employee_number', 'Akun demo tidak tersedia. Jalankan ulang seeder database.');

            return;
        }

        Auth::login($user);

        $this->completeLogin($user, 'demo');
    }

    // ----------------------------------------------------------------- helpers

    private function completeLogin(User $user, string $method): void
    {
        session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();

        // Multi-role users start in their primary workspace.
        session(['phm.active_role' => $user->primaryRole()?->code]);

        app(AuditLogger::class)->log(
            $user,
            'auth.login',
            'User',
            $user->id,
            sprintf('%s masuk ke PHM One (%s).', $user->full_name, $method === 'demo' ? 'login demo' : 'kata sandi'),
            ['method' => $method],
            visibleInFeed: false,
        );

        // Livewire's own redirectIntended() honours an intended URL captured by
        // the auth middleware, falling back to the role's home route.
        $this->redirectIntended(
            default: app(RoleRouting::class)->homeUrlFor($user),
            navigate: false,
        );
    }

    private function throttleKey(string $action): string
    {
        return $action.'|'.Str::lower(trim($this->employee_number)).'|'.request()->ip();
    }

    private function ensureIsNotRateLimited(string $action, int $maxAttempts): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($action), $maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($action));

        throw ValidationException::withMessages([
            'employee_number' => sprintf(
                'Terlalu banyak percobaan masuk. Silakan coba lagi dalam %d detik.',
                $seconds
            ),
        ]);
    }

    public function render()
    {
        return view('livewire.auth.login-form', [
            'demoAccounts' => $this->demoAccounts(),
        ]);
    }

    /**
     * @return array<int, array{employee_number: string, name: string, role: string, icon: string}>
     */
    private function demoAccounts(): array
    {
        if (! config('phm.demo_mode')) {
            return [];
        }

        $icons = [
            Roles::OPERATOR => 'hard-hat',
            Roles::SUPERVISOR => 'clipboard-check',
            Roles::ADMIN_ORDER => 'package',
            Roles::PPIC => 'calendar-days',
            Roles::WAREHOUSE => 'warehouse',
            Roles::DRIVER => 'truck',
            Roles::MANAGEMENT => 'chart-column',
            Roles::SYSADMIN => 'shield-check',
        ];

        return User::where('is_demo', true)
            ->where('status', 'active')
            ->with('roles')
            ->orderBy('employee_number')
            ->get()
            ->filter(fn (User $u) => $u->roles->count() === 1)
            ->map(fn (User $u) => [
                'employee_number' => $u->employee_number,
                'name' => $u->full_name,
                'role' => $u->roles->first()->name,
                'icon' => $icons[$u->roles->first()->code] ?? 'circle-user',
            ])
            ->values()
            ->all();
    }
}
