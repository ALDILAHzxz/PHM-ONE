<div>
    {{-- =====================================================================
         STEP 1 — Nomor Induk Karyawan only. One field, one button.
    ====================================================================== --}}
    @if ($step === 'identify')
        <div class="phm-card">
            <h1 class="phm-page-title">Selamat Datang di PHM One</h1>
            <p class="mt-2 text-base text-muted">Masuk untuk melihat dan mencatat pekerjaan Anda.</p>

            <form wire:submit="identify" class="mt-6 space-y-5">
                <div>
                    <label for="employee_number" class="phm-label">Nomor Induk Karyawan</label>
                    <input id="employee_number"
                           type="text"
                           wire:model="employee_number"
                           class="phm-input"
                           placeholder="PHM-EMP-001"
                           autocomplete="username"
                           autocapitalize="characters"
                           spellcheck="false"
                           required
                           @error('employee_number') aria-invalid="true" aria-describedby="employee_number-error" @enderror
                           autofocus>

                    @error('employee_number')
                        <p id="employee_number-error" role="alert" class="mt-2 flex items-start gap-1.5 text-sm text-rose-700 dark:text-rose-300">
                            <x-lucide-circle-alert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                            <span>{{ $message }}</span>
                        </p>
                    @enderror

                    <p class="phm-helper mt-2">
                        Gunakan nomor karyawan dari perusahaan, bukan NIK kartu identitas.
                    </p>
                </div>

                <button type="submit" class="phm-btn phm-btn-primary w-full" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="identify">Lanjutkan</span>
                    <span wire:loading wire:target="identify" class="flex items-center gap-2">
                        <x-lucide-loader-circle class="size-5 animate-spin" aria-hidden="true" />
                        Memeriksa…
                    </span>
                </button>
            </form>

            <div class="mt-5 border-t border-line pt-4">
                <a href="{{ route('login.help') }}" class="phm-btn phm-btn-ghost w-full">
                    <x-lucide-circle-help class="size-5" aria-hidden="true" />
                    Tidak bisa masuk?
                </a>
            </div>
        </div>

        {{-- ----------------------------------------------------------------
             DEMO LOGIN — clearly labelled, isolated from real authentication.
        ----------------------------------------------------------------- --}}
        @if (config('phm.demo_mode') && count($demoAccounts) > 0)
            <div class="phm-card mt-5">
                <div class="flex items-start gap-3">
                    <span class="grid size-9 shrink-0 place-items-center rounded-[0.625rem] bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300">
                        <x-lucide-flask-conical class="size-5" aria-hidden="true" />
                    </span>
                    <div>
                        <p class="phm-card-heading">Masuk Cepat — Akun Demo</p>
                        <p class="phm-helper mt-1">
                            Akun fiktif untuk demonstrasi. Ini <strong>bukan</strong> SSO perusahaan
                            dan tidak terhubung ke sistem PHM.
                        </p>
                    </div>
                </div>

                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                    @foreach ($demoAccounts as $account)
                        <button type="button"
                                wire:click="loginAsDemo('{{ $account['employee_number'] }}')"
                                wire:loading.attr="disabled"
                                class="flex min-h-[3.25rem] items-center gap-3 rounded-[var(--radius-control)] border border-line bg-surface px-3 py-2 text-left transition-colors duration-150 hover:border-brand hover:bg-brand/5">
                            <x-dynamic-component :component="'lucide-' . $account['icon']"
                                                 class="size-5 shrink-0 text-icon-accent"
                                                 aria-hidden="true" />
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold">{{ $account['role'] }}</span>
                                <span class="block truncate text-[0.8125rem] text-muted">{{ $account['name'] }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    {{-- =====================================================================
         STEP 2 — company authentication step for the identified employee.
    ====================================================================== --}}
    @if ($step === 'authenticate')
        <div class="phm-card">
            <button type="button"
                    wire:click="backToIdentify"
                    class="phm-btn phm-btn-ghost -ml-3 mb-4 min-h-11 px-3 text-base">
                <x-lucide-arrow-left class="size-5" aria-hidden="true" />
                Ganti nomor karyawan
            </button>

            <div class="flex items-center gap-3 rounded-[var(--radius-control)] bg-surface-muted p-3 ring-1 ring-line">
                <span class="grid size-12 shrink-0 place-items-center rounded-full bg-brand text-[1.0625rem] font-semibold text-white">
                    {{ $identifiedInitials }}
                </span>
                <div class="min-w-0">
                    <p class="truncate text-base font-semibold">{{ $identifiedName }}</p>
                    <p class="truncate text-sm text-muted">
                        {{ $identifiedPosition ?? 'Karyawan PHM' }} · {{ $employee_number }}
                    </p>
                </div>
            </div>

            <form wire:submit="authenticate" class="mt-5 space-y-5">
                <div x-data="{ show: false }">
                    <label for="password" class="phm-label">Kata Sandi</label>
                    <div class="relative">
                        <input id="password"
                               :type="show ? 'text' : 'password'"
                               type="password"
                               wire:model="password"
                               class="phm-input pr-14"
                               autocomplete="current-password"
                               required
                               @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                               autofocus>

                        <button type="button"
                                @click="show = !show"
                                class="absolute inset-y-0 right-0 grid w-14 place-items-center rounded-r-[var(--radius-control)] text-muted transition-colors hover:text-interactive"
                                :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                            <x-lucide-eye x-show="!show" class="size-5" aria-hidden="true" />
                            <x-lucide-eye-off x-cloak x-show="show" class="size-5" aria-hidden="true" />
                        </button>
                    </div>

                    @error('password')
                        <p id="password-error" role="alert" class="mt-2 flex items-start gap-1.5 text-sm text-rose-700 dark:text-rose-300">
                            <x-lucide-circle-alert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                            <span>{{ $message }}</span>
                        </p>
                    @enderror

                    @error('employee_number')
                        <p role="alert" class="mt-2 flex items-start gap-1.5 text-sm text-rose-700 dark:text-rose-300">
                            <x-lucide-circle-alert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <label class="flex min-h-11 items-center gap-3">
                    <input type="checkbox"
                           wire:model="remember"
                           class="size-5 rounded border-line-strong text-brand focus:ring-2 focus:ring-brand/40">
                    <span class="text-base">Ingat perangkat ini</span>
                </label>

                <button type="submit" class="phm-btn phm-btn-primary w-full" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="authenticate">Masuk</span>
                    <span wire:loading wire:target="authenticate" class="flex items-center gap-2">
                        <x-lucide-loader-circle class="size-5 animate-spin" aria-hidden="true" />
                        Memverifikasi…
                    </span>
                </button>
            </form>

            <div class="mt-5 border-t border-line pt-4">
                <a href="{{ route('login.help') }}" class="phm-btn phm-btn-ghost w-full">
                    <x-lucide-key class="size-5" aria-hidden="true" />
                    Lupa kata sandi?
                </a>
            </div>
        </div>
    @endif
</div>
