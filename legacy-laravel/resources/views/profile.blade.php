<x-app-layout title="Profil" page-title="Profil">
    <div class="mx-auto w-full max-w-[45rem] space-y-5">

        <x-page-header title="Profil Saya"
                       subtitle="Informasi akun dan preferensi tampilan Anda." />

        {{-- ------------------------------------------------------- identitas --}}
        <section class="phm-card">
            <div class="flex items-center gap-4">
                <span class="grid size-16 shrink-0 place-items-center rounded-full bg-brand text-[1.375rem] font-semibold text-white">
                    {{ $user->initials() }}
                </span>
                <div class="min-w-0">
                    <h2 class="phm-section-heading truncate">{{ $user->full_name }}</h2>
                    <p class="truncate text-base text-muted">{{ $user->position ?? 'Karyawan PHM' }}</p>
                </div>
            </div>

            <h3 class="phm-card-heading mt-6">Identitas</h3>
            <dl class="mt-3">
                <x-info-row label="Nama lengkap" :value="$user->full_name" />
                <x-info-row label="Nomor Induk Karyawan" :value="$user->employee_number" strong />
                <x-info-row label="Departemen" :value="$user->department?->name ?? '—'" />
                <x-info-row label="Jabatan" :value="$user->position ?? '—'" />
                @if ($user->workShift)
                    <x-info-row label="Shift" :value="$user->workShift->describe()" />
                @endif
                <x-info-row label="Status akun">
                    <span class="phm-badge {{ $user->isActive()
                        ? 'bg-emerald-100 text-emerald-900 ring-emerald-300 dark:bg-emerald-400/15 dark:text-emerald-200 dark:ring-emerald-400/40'
                        : 'bg-rose-100 text-rose-900 ring-rose-300 dark:bg-rose-400/15 dark:text-rose-200 dark:ring-rose-400/40' }}">
                        {{ $user->isActive() ? 'Aktif' : 'Tidak aktif' }}
                    </span>
                </x-info-row>
            </dl>
        </section>

        {{-- -------------------------------------------------- informasi akun --}}
        <section class="phm-card">
            <h3 class="phm-card-heading">Informasi Akun</h3>
            <dl class="mt-3">
                <x-info-row label="Email kerja" :value="$user->email ?? 'Belum tercatat'" />
                <x-info-row label="Nomor kontak" :value="$user->phone ?? 'Belum tercatat'" />
                <x-info-row label="Lokasi kerja" :value="$user->work_location ?? '—'" />
                <x-info-row label="Akun terakhir digunakan"
                            :value="$user->last_login_at?->translatedFormat('l, d F Y · H:i') ?? 'Baru pertama kali'" />
            </dl>
        </section>

        {{-- ------------------------------------------------------ ruang kerja --}}
        <section class="phm-card">
            <h3 class="phm-card-heading">Ruang Kerja</h3>
            <p class="phm-helper mt-1">
                @if ($user->roles->count() > 1)
                    Anda memiliki {{ $user->roles->count() }} peran. Pilih ruang kerja yang ingin Anda gunakan.
                @else
                    Peran Anda menentukan menu dan data yang dapat Anda akses.
                @endif
            </p>

            <div class="mt-4 space-y-2">
                @foreach ($user->roles as $role)
                    @if ($role->code === $activeRole?->code)
                        <div class="flex items-center gap-3 rounded-[var(--radius-control)] border border-brand bg-brand/8 p-4">
                            <x-lucide-circle-check class="size-5 shrink-0 text-interactive" aria-hidden="true" />
                            <div class="min-w-0 flex-1">
                                <p class="text-base font-semibold">{{ $role->name }}</p>
                                <p class="phm-helper">{{ $role->description }}</p>
                            </div>
                            <span class="phm-badge shrink-0 bg-brand/15 text-interactive ring-brand/30">Aktif</span>
                        </div>
                    @else
                        <form method="POST" action="{{ route('profile.switch-role') }}">
                            @csrf
                            <input type="hidden" name="role" value="{{ $role->code }}">
                            <button type="submit"
                                    class="flex w-full items-center gap-3 rounded-[var(--radius-control)] border border-line bg-surface p-4 text-left transition-colors duration-150 hover:border-brand hover:bg-brand/5">
                                <x-lucide-refresh-cw class="size-5 shrink-0 text-muted" aria-hidden="true" />
                                <span class="min-w-0 flex-1">
                                    <span class="block text-base font-semibold">{{ $role->name }}</span>
                                    <span class="phm-helper block">{{ $role->description }}</span>
                                </span>
                                <span class="shrink-0 text-sm font-semibold text-interactive">Pindah</span>
                            </button>
                        </form>
                    @endif
                @endforeach
            </div>
        </section>

        {{-- -------------------------------------------------------- preferensi --}}
        <section class="phm-card">
            <h3 class="phm-card-heading">Preferensi</h3>
            <dl class="mt-3">
                <x-info-row label="Tema tampilan">
                    <span class="flex items-center justify-end gap-2">
                        <span class="text-sm text-muted">Gunakan ikon di sudut kanan atas</span>
                    </span>
                </x-info-row>
                <x-info-row label="Bahasa" value="Bahasa Indonesia" />
                <x-info-row label="Aksesibilitas">
                    <span class="text-sm text-muted">
                        Mengikuti pengaturan perangkat (ukuran teks &amp; kurangi animasi)
                    </span>
                </x-info-row>
            </dl>
        </section>

        {{-- ---------------------------------------------------- keamanan akun --}}
        <section class="phm-card">
            <h3 class="phm-card-heading">Keamanan Akun</h3>

            <form method="POST" action="{{ route('profile.password') }}" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="phm-label">Kata sandi saat ini</label>
                    <input id="current_password" name="current_password" type="password"
                           class="phm-input" autocomplete="current-password" required>
                    @error('current_password')
                        <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="phm-label">Kata sandi baru</label>
                    <input id="password" name="password" type="password"
                           class="phm-input" autocomplete="new-password" required>
                    @error('password')
                        <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
                    @enderror
                    <p class="phm-helper mt-2">Minimal 8 karakter.</p>
                </div>

                <div>
                    <label for="password_confirmation" class="phm-label">Ulangi kata sandi baru</label>
                    <input id="password_confirmation" name="password_confirmation" type="password"
                           class="phm-input" autocomplete="new-password" required>
                </div>

                <button type="submit" class="phm-btn phm-btn-primary w-full sm:w-auto">
                    Ubah Kata Sandi
                </button>
            </form>

            {{-- Honest about what is NOT implemented, rather than faking it. --}}
            <div class="mt-5 space-y-3 border-t border-line pt-5">
                <div class="flex items-start gap-3">
                    <x-lucide-fingerprint class="mt-0.5 size-5 shrink-0 text-muted" aria-hidden="true" />
                    <div>
                        <p class="text-base font-medium">Passkey / Biometrik</p>
                        <p class="phm-helper">
                            Belum tersedia. Memerlukan WebAuthn dengan verifikasi di sisi server
                            dan persetujuan perusahaan. Aplikasi ini tidak merekam sidik jari
                            dan tidak mensimulasikan autentikasi biometrik.
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <x-lucide-key class="mt-0.5 size-5 shrink-0 text-muted" aria-hidden="true" />
                    <div>
                        <p class="text-base font-medium">Lupa Kata Sandi</p>
                        <p class="phm-helper">
                            @if (config('phm.demo_mode'))
                                Belum tersedia di mode demo. Pemulihan kata sandi memerlukan
                                layanan email atau identitas resmi perusahaan.
                            @else
                                Hubungi Administrator Sistem untuk mereset kata sandi.
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <x-lucide-shield-check class="mt-0.5 size-5 shrink-0 text-muted" aria-hidden="true" />
                    <div>
                        <p class="text-base font-medium">Status keamanan akun</p>
                        <p class="phm-helper">
                            @if (config('phm.demo_mode'))
                                Mode demo: autentikasi berbasis kata sandi lokal, bukan SSO perusahaan.
                            @else
                                Autentikasi melalui penyedia identitas perusahaan.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ---------------------------------------------------------- sesi --}}
        <section class="phm-card">
            <h3 class="phm-card-heading">Sesi</h3>
            <p class="phm-helper mt-1">
                Keluar tidak menghapus laporan atau data pekerjaan yang sudah Anda simpan.
            </p>
            <form method="POST" action="{{ route('logout') }}" class="mt-4"
                  onsubmit="return confirm('Keluar dari PHM One sekarang?')">
                @csrf
                <button type="submit" class="phm-btn phm-btn-danger w-full sm:w-auto">
                    <x-lucide-log-out class="size-5" aria-hidden="true" />
                    Keluar
                </button>
            </form>
        </section>
    </div>
</x-app-layout>
