<x-app-layout title="Kelola Peran" page-title="Kelola Peran">
    <div class="space-y-5">
        <x-page-header title="Kelola Peran"
                       subtitle="Matriks peran dan izin yang berlaku di server." />

        <div class="flex items-start gap-3 rounded-[var(--radius-card)] border border-line bg-surface-muted p-4">
            <x-lucide-shield-check class="mt-0.5 size-5 shrink-0 text-interactive" aria-hidden="true" />
            <p class="text-sm leading-relaxed text-muted">
                Setiap izin di bawah ini diperiksa di sisi server melalui middleware rute dan Gate
                Laravel. Antarmuka hanya menyembunyikan hal yang sudah ditolak. Perubahan matriks
                dilakukan lewat seeder <code class="rounded bg-surface px-1.5 py-0.5">RolePermissionSeeder</code>
                agar tercatat dalam kode dan dapat ditinjau.
            </p>
        </div>

        @foreach ($roles as $role)
            <article class="phm-card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="phm-card-heading">{{ $role->name }}</h2>
                        <p class="phm-helper mt-1">{{ $role->description }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-base font-semibold tabular-nums">{{ $role->users->count() }}</p>
                        <p class="phm-helper">karyawan</p>
                    </div>
                </div>

                <dl class="mt-4">
                    <x-info-row label="Kode peran" :value="$role->code" />
                    <x-info-row label="Halaman awal setelah masuk" :value="$role->default_route" />
                    <x-info-row label="Jumlah izin" :value="$role->permissions->count() . ' izin'" />
                </dl>

                <div class="mt-4 border-t border-line pt-3">
                    <p class="phm-helper mb-2">Izin:</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($role->permissions->sortBy('group') as $permission)
                            <span class="phm-badge bg-surface-muted text-body ring-line">
                                {{ $permission->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</x-app-layout>
