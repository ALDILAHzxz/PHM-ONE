<x-guest-layout title="Bantuan Masuk">
    <div class="phm-card">
        <a href="{{ route('login') }}" class="phm-btn phm-btn-ghost -ml-3 mb-4 min-h-11 px-3 text-base">
            <x-lucide-arrow-left class="size-5" aria-hidden="true" />
            Kembali ke halaman masuk
        </a>

        <h1 class="phm-page-title">Tidak Bisa Masuk?</h1>
        <p class="mt-2 text-base text-muted">Berikut langkah yang bisa Anda coba.</p>

        <div class="mt-6 space-y-5">
            <div>
                <h2 class="phm-card-heading">1. Periksa Nomor Induk Karyawan</h2>
                <p class="phm-helper mt-1">
                    Nomor karyawan diberikan oleh perusahaan, contohnya <code class="rounded bg-surface-muted px-1.5 py-0.5">PHM-EMP-001</code>.
                    Nomor ini berbeda dari NIK pada kartu identitas.
                </p>
            </div>

            <div>
                <h2 class="phm-card-heading">2. Lupa Kata Sandi</h2>
                @if (config('phm.demo_mode'))
                    <div class="mt-2 flex items-start gap-3 rounded-[var(--radius-control)] border border-amber-300 bg-amber-50 p-3 text-amber-900 dark:border-amber-400/40 dark:bg-amber-400/10 dark:text-amber-100">
                        <x-lucide-triangle-alert class="mt-0.5 size-5 shrink-0" aria-hidden="true" />
                        <p class="text-sm leading-relaxed">
                            <strong>Belum tersedia di mode demo.</strong>
                            Pemulihan kata sandi memerlukan layanan email atau identitas resmi perusahaan
                            yang belum terhubung ke prototipe ini. Fitur ini sengaja tidak disimulasikan
                            agar tidak memberi kesan aman padahal belum.
                        </p>
                    </div>
                @else
                    <p class="phm-helper mt-1">
                        Hubungi Administrator Sistem untuk mereset kata sandi akun Anda.
                    </p>
                @endif
            </div>

            <div>
                <h2 class="phm-card-heading">3. Akun Tidak Aktif</h2>
                <p class="phm-helper mt-1">
                    Jika akun Anda dinonaktifkan, hubungi atasan langsung atau Administrator Sistem
                    untuk pengaktifan kembali.
                </p>
            </div>

            <div>
                <h2 class="phm-card-heading">4. Masih Belum Bisa Masuk</h2>
                <p class="phm-helper mt-1">
                    Catat pesan kesalahan yang muncul dan laporkan ke tim IT PHM beserta nomor
                    karyawan Anda dan waktu kejadian.
                </p>
            </div>
        </div>
    </div>
</x-guest-layout>
