<x-app-layout title="Status Sistem" page-title="Status Sistem">
    <div class="mx-auto w-full max-w-[52rem] space-y-5">
        <x-page-header title="Status Sistem"
                       subtitle="Konfigurasi aktif dan batasan prototipe ini."
                       :updated-at="now()->translatedFormat('H:i')" />

        <section class="phm-card">
            <h2 class="phm-card-heading">Data Tersimpan</h2>
            <dl class="mt-3">
                @foreach ($counts as $label => $value)
                    <x-info-row :label="$label" :value="number_format($value, 0, ',', '.')" />
                @endforeach
            </dl>
        </section>

        <section class="phm-card">
            <h2 class="phm-card-heading">Lingkungan</h2>
            <dl class="mt-3">
                @foreach ($runtime as $label => $value)
                    <x-info-row :label="$label" :value="$value" />
                @endforeach
            </dl>
        </section>

        <section class="phm-card">
            <h2 class="phm-card-heading">Pembaruan Data</h2>
            <dl class="mt-3">
                @foreach ($realtime as $label => $value)
                    <x-info-row :label="$label" :value="$value" />
                @endforeach
            </dl>
            <p class="phm-helper mt-3">
                PHM One menggunakan polling terotorisasi ke server, bukan WebSocket. Setiap pembaruan
                menjalankan ulang kueri yang sama dengan pemeriksaan izin, sehingga perubahan dari
                sesi lain terlihat tanpa memasukkan ulang data.
            </p>
        </section>

        <section class="phm-card">
            <h2 class="phm-card-heading">Beesha AI</h2>
            <dl class="mt-3">
                @foreach ($beesha as $label => $value)
                    <x-info-row :label="$label">
                        @if (is_bool($value))
                            <span class="phm-badge {{ $value
                                ? 'bg-emerald-100 text-emerald-900 ring-emerald-300 dark:bg-emerald-400/15 dark:text-emerald-200 dark:ring-emerald-400/40'
                                : 'bg-slate-200 text-slate-700 ring-slate-300 dark:bg-slate-500/20 dark:text-slate-300 dark:ring-slate-500/40' }}">
                                {{ $value ? 'Ya' : 'Tidak' }}
                            </span>
                        @else
                            {{ $value }}
                        @endif
                    </x-info-row>
                @endforeach
            </dl>
        </section>

        <section class="phm-card">
            <h2 class="phm-card-heading">Tanda Konfigurasi</h2>
            <dl class="mt-3">
                @foreach ($flags as $label => $value)
                    <x-info-row :label="$label">
                        <span class="phm-badge {{ $value
                            ? 'bg-amber-100 text-amber-900 ring-amber-300 dark:bg-amber-400/15 dark:text-amber-200 dark:ring-amber-400/40'
                            : 'bg-slate-200 text-slate-700 ring-slate-300 dark:bg-slate-500/20 dark:text-slate-300 dark:ring-slate-500/40' }}">
                            {{ $value ? 'Aktif' : 'Tidak aktif' }}
                        </span>
                    </x-info-row>
                @endforeach
            </dl>
        </section>

        {{-- Limitations stated in the product, not just in the docs. --}}
        <section class="phm-card">
            <h2 class="phm-card-heading">Batasan Yang Diketahui</h2>
            <ul class="mt-3 space-y-2.5">
                @foreach ([
                    'Belum ada integrasi ke ERP, INDIGO, DPR/MPR, atau database produksi PHM. Seluruh data bersifat sintetis.',
                    'Autentikasi masih berbasis kata sandi lokal. Batas integrasi OIDC (Authorization Code + PKCE) sudah disiapkan tetapi belum terhubung ke penyedia identitas.',
                    'Pemulihan kata sandi dan passkey/biometrik belum tersedia karena memerlukan layanan resmi perusahaan.',
                    'Pembaruan lintas sesi memakai polling, bukan WebSocket, sehingga ada jeda hingga ' . config('phm.poll_seconds') . ' detik.',
                    'Kebijakan produksi melebihi target memakai aturan demo yang terdokumentasi, bukan kebijakan resmi PHM.',
                    'Leaderboard karyawan belum diaktifkan karena memerlukan definisi penilaian yang disetujui.',
                ] as $limitation)
                    <li class="flex gap-3">
                        <x-lucide-info class="mt-0.5 size-5 shrink-0 text-muted" aria-hidden="true" />
                        <span class="text-base leading-relaxed">{{ $limitation }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
</x-app-layout>
