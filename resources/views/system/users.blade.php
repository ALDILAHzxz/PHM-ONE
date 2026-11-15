<x-app-layout title="Kelola Akun" page-title="Kelola Akun">
    <div class="space-y-5">
        <x-page-header title="Kelola Akun"
                       subtitle="Daftar karyawan dan peran yang dimilikinya." />

        <form method="GET" action="{{ route('system.users') }}" class="phm-card">
            <label for="cari" class="phm-label">Cari karyawan</label>
            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1">
                    <input id="cari" name="cari" type="search" value="{{ $search }}"
                           class="phm-input pl-12" placeholder="Nama atau Nomor Induk Karyawan">
                    <x-lucide-search class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-muted"
                                     aria-hidden="true" />
                </div>
                <button type="submit" class="phm-btn phm-btn-secondary sm:w-32">Cari</button>
            </div>
        </form>

        @if (config('phm.demo_mode'))
            <div class="flex items-start gap-3 rounded-[var(--radius-card)] border border-line bg-surface-muted p-4">
                <x-lucide-info class="mt-0.5 size-5 shrink-0 text-interactive" aria-hidden="true" />
                <p class="text-sm leading-relaxed text-muted">
                    Dalam mode demo, akun hanya dapat dilihat. Pembuatan dan penghapusan akun
                    memerlukan integrasi dengan sistem identitas resmi perusahaan, sehingga belum
                    diaktifkan di prototipe ini.
                </p>
            </div>
        @endif

        <div class="space-y-3">
            @foreach ($users as $user)
                <article class="phm-card">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid size-11 shrink-0 place-items-center rounded-full bg-brand/10 text-base font-semibold text-interactive">
                                {{ $user->initials() }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-base font-semibold">{{ $user->full_name }}</p>
                                <p class="phm-helper truncate">
                                    {{ $user->employee_number }}
                                    @if ($user->department) · {{ $user->department->name }} @endif
                                    @if ($user->position) · {{ $user->position }} @endif
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            @if ($user->is_demo)
                                <span class="phm-badge bg-amber-100 text-amber-900 ring-amber-300 dark:bg-amber-400/15 dark:text-amber-200 dark:ring-amber-400/40">
                                    Demo
                                </span>
                            @endif
                            <span class="phm-badge {{ $user->isActive()
                                ? 'bg-emerald-100 text-emerald-900 ring-emerald-300 dark:bg-emerald-400/15 dark:text-emerald-200 dark:ring-emerald-400/40'
                                : 'bg-rose-100 text-rose-900 ring-rose-300 dark:bg-rose-400/15 dark:text-rose-200 dark:ring-rose-400/40' }}">
                                {{ $user->isActive() ? 'Aktif' : 'Tidak aktif' }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2 border-t border-line pt-3">
                        @forelse ($user->roles as $role)
                            <span class="phm-badge bg-brand/12 text-interactive ring-brand/30">
                                {{ $role->name }}
                                @if ($role->pivot->is_primary)
                                    <span class="text-[0.6875rem] opacity-80">(utama)</span>
                                @endif
                            </span>
                        @empty
                            <span class="phm-helper">Belum memiliki peran.</span>
                        @endforelse
                    </div>

                    <p class="phm-helper mt-3">
                        Terakhir masuk:
                        {{ $user->last_login_at?->translatedFormat('d M Y · H:i') ?? 'belum pernah' }}
                    </p>
                </article>
            @endforeach
        </div>

        @if ($users->hasPages())
            <div class="phm-card py-4">{{ $users->links() }}</div>
        @endif
    </div>
</x-app-layout>
