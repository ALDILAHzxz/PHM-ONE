# PHM One — Powered by Beesha

Platform operasi manufaktur untuk **PT Putra Hatim Mandiri (PHM Group)**.

> Masuk sekali. Lihat tugas. Catat hasil. Selesai.

Aplikasi ini menyatukan alur kerja harian PHM dalam satu antarmuka yang sederhana:

```
Login → Identifikasi Peran → Tugas Harian → Catat Hasil Produksi
      → Verifikasi Supervisor → Dashboard Operasional → Beesha AI
```

dengan alur pendukung:

```
Pesanan Mitra → Perencanaan Produksi → Produksi → Pemeriksaan → Pengiriman → Pengantaran
```

---

## ⚠️ Status proyek dan batasan

**Ini adalah prototipe (student-led) dengan fokus perbaikan UI/UX. Bukan sistem produksi.**

| Hal | Status |
| --- | --- |
| Data | **Sintetis seluruhnya.** Tidak ada data karyawan, mitra, atau produksi PHM yang nyata. |
| ERP / INDIGO / DPR / MPR | **Tidak terhubung.** Tidak ada integrasi, dan tidak diasumsikan ada. |
| Beesha produksi | **Tidak terhubung.** Asisten di aplikasi ini adalah asisten demo deterministik. |
| SSO perusahaan | **Belum ada.** Batas integrasi OIDC sudah disiapkan tetapi tidak tersambung. |
| Pemulihan kata sandi | **Belum tersedia.** Memerlukan layanan email/identitas resmi. |
| Passkey / biometrik | **Belum tersedia.** Tidak ada sidik jari yang direkam dan tidak disimulasikan. |
| Pembaruan lintas sesi | **Polling terotorisasi**, bukan WebSocket. Jeda hingga 5 detik. |
| Deployment | **Belum di-deploy.** Tidak ada URL produksi. |

Daftar lengkap: [`docs/QA_REPORT.md`](docs/QA_REPORT.md) dan halaman **Status Sistem** di dalam aplikasi.

---

## Teknologi

| Lapisan | Pilihan |
| --- | --- |
| Framework | Laravel 13.17 (PHP 8.4) |
| Antarmuka | Blade + Livewire 4.4 + Alpine (dibundel Livewire) |
| Styling | Tailwind CSS 4 (via Vite 8), token tema kustom |
| Ikon | Lucide (`mallardduck/blade-lucide-icons`) |
| Font | Inter (dibundel lokal lewat Vite, tanpa CDN pihak ketiga) |
| Database | SQLite (prototipe) — skema kompatibel MySQL/PostgreSQL |
| Autentikasi | Sesi Laravel, dua langkah (NIK → kata sandi) |
| Otorisasi | Tabel peran/izin sendiri + Gate + middleware rute |
| Pembaruan data | `wire:poll` terotorisasi |
| AI | Lapisan penyedia yang dapat ditukar (demo / Anthropic) |
| Tes | PHPUnit 12 + Livewire test helpers |

Mengapa Laravel dan bukan Next.js seperti pada spesifikasi awal: lihat
[`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md#penyimpangan-dari-spesifikasi).

---

## Persyaratan

- PHP **8.3+** (diuji pada 8.4.16) dengan ekstensi `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `fileinfo`
- Composer 2.x
- Node.js 20+ dan npm

> **Catatan XAMPP:** `pdo_sqlite` dan `sqlite3` dimatikan secara bawaan.
> Hapus tanda `;` pada dua baris ini di `php.ini`, lalu restart terminal:
>
> ```ini
> extension=pdo_sqlite
> extension=sqlite3
> ```
>
> Verifikasi dengan `php -m | grep sqlite`.

---

## Instalasi

```bash
# 1. Dependensi
composer install
npm install

# 2. Konfigurasi
cp .env.example .env
php artisan key:generate

# 3. Database + data demo
php artisan migrate:fresh --seed

# 4. Aset frontend
npm run build

# 5. Jalankan
php artisan serve
```

Buka <http://127.0.0.1:8000>.

Untuk pengembangan dengan hot reload, jalankan dua terminal:

```bash
php artisan serve     # terminal 1
npm run dev           # terminal 2
```

### Memakai MySQL/MariaDB sebagai gantinya

Skema sengaja dibuat portabel. Ubah `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=phm_one
DB_USERNAME=root
DB_PASSWORD=
```

lalu `php artisan migrate:fresh --seed`.

---

## Konfigurasi lingkungan

Variabel khusus PHM One (lihat `config/phm.php`):

| Variabel | Bawaan | Arti |
| --- | --- | --- |
| `PHM_DEMO_MODE` | `true` | Menandai seluruh aplikasi sebagai mode demo, mengaktifkan panel login demo, dan menandai fitur keamanan yang belum tersedia. **Harus `false` sebelum deployment nyata.** |
| `PHM_POLL_SECONDS` | `5` | Interval pembaruan otomatis dashboard dan antrean pemeriksaan. |
| `BEESHA_PROVIDER` | `demo` | `demo` (deterministik, data tidak keluar dari server) atau `anthropic`. |
| `BEESHA_API_KEY` | kosong | Hanya diperlukan bila `BEESHA_PROVIDER=anthropic`. Jangan pernah di-commit. |
| `BEESHA_MODEL` | `claude-haiku-5-5` | Model yang dipakai bila penyedia eksternal diaktifkan. |
| `OIDC_ENABLED` | `false` | Penanda batas integrasi SSO; belum ada implementasi penyedia. |

> **Privasi:** mengaktifkan `BEESHA_PROVIDER=anthropic` berarti mengirim pertanyaan
> pengguna dan hasil pemanggilan tool ke layanan model di luar perusahaan. Ini
> memerlukan persetujuan tertulis dari PHM. Bawaan aplikasi adalah `demo`.

---

## Akun demo

Berlaku **hanya** saat `PHM_DEMO_MODE=true`. Semua akun ini fiktif dan ditandai
`is_demo = true` di database, sehingga tidak dapat tertukar dengan identitas nyata.

Kata sandi demo bersama: **`demo1234`** (sengaja tidak rahasia; bukan kredensial perusahaan).

| Nomor Induk Karyawan | Nama (fiktif) | Peran | Mendarat di |
| --- | --- | --- | --- |
| `PHM-EMP-001` | Budi Santoso | Operator Produksi | Tugas Saya |
| `PHM-EMP-002` | Siti Nurhaliza | Supervisor Produksi / QC | Pemeriksaan |
| `PHM-EMP-003` | Dewi Kartika | Admin Pesanan | Pesanan Mitra |
| `PHM-EMP-004` | Agus Wijaya | PPIC | Rencana Produksi |
| `PHM-EMP-005` | Rudi Hartono | Petugas Gudang | Pengiriman Hari Ini |
| `PHM-EMP-006` | Joko Prasetyo | Driver | Pengiriman Saya |
| `PHM-EMP-007` | Hartono Putra | Pimpinan / Management | Ringkasan Perusahaan |
| `PHM-EMP-008` | Rizki Permana | Administrator Sistem | Kelola Akun |
| `PHM-EMP-009` | Yuni Astuti | Supervisor **dan** PPIC | Pemeriksaan (bisa berpindah) |

Halaman login menyediakan tombol **Masuk Cepat** untuk delapan akun satu-peran.
`PHM-EMP-009` dipakai untuk mencoba perpindahan ruang kerja dari menu Profil.

---

## Perintah

```bash
php artisan serve                  # jalankan aplikasi
npm run dev                        # Vite dev server
npm run build                      # build aset produksi

php artisan migrate:fresh --seed   # reset database + data demo
php artisan db:seed                # seed ulang tanpa menghapus

php artisan test                   # seluruh tes (199 tes)
php artisan test --filter=NamaTest # satu kelas tes
vendor/bin/pint                    # format kode
php artisan route:list --except-vendor
```

---

## Fitur yang sudah berjalan

**Autentikasi & peran**
- Login dua langkah: Nomor Induk Karyawan → kata sandi, dengan pembatasan percobaan
- Panel login demo yang terisolasi dari autentikasi nyata
- 8 peran, 24 izin, diperiksa di server lewat middleware rute + Gate
- Perpindahan ruang kerja untuk karyawan multi-peran
- Pengalihan otomatis ke ruang kerja sesuai peran, tanpa layar pemilihan dashboard

**Operator**
- Tugas Saya: target, sudah dilaporkan, sisa pekerjaan, satu tombol aksi utama
- Catat Hasil Produksi gaya Google Forms: satu kolom, kartu bertumpuk, satu input angka
- Validasi lengkap, field kendala muncul hanya bila ada kendala
- Langkah konfirmasi sebelum menyimpan, pencegahan kiriman ganda (idempotency key)
- Riwayat Saya dengan filter status dan hasil pemeriksaan

**Supervisor / QC**
- Antrean pemeriksaan dengan pembaruan otomatis
- Verifikasi dan permintaan perbaikan (wajib menyertakan alasan)
- Daftar kendala produksi, riwayat tim
- Tidak dapat memeriksa laporan sendiri

**Admin Pesanan & PPIC**
- Daftar dan pencarian pesanan mitra, detail pemenuhan pesanan
- Tambah pesanan dengan data mitra/produk terisi otomatis
- Work order dan penugasan operator dengan batas jumlah yang divalidasi

**Gudang & Driver**
- Catat pengiriman — hanya produksi **terverifikasi** yang dihitung siap kirim
- Status pengiriman dengan transisi yang dibatasi (tidak bisa kontradiktif)
- Ruang kerja driver mobile-first, konfirmasi penerima, pelaporan kendala

**Pimpinan**
- Empat KPI utama, progres produksi, kinerja pengiriman, peringatan, aktivitas terbaru
- Pemisahan tegas antara **dilaporkan** dan **terverifikasi** di setiap tampilan

**Beesha AI**
- Tombol mengapung di kanan bawah pada **semua** halaman terautentikasi
- 10 tool data dengan pemeriksaan izin per pemanggilan, tanpa SQL bebas, tanpa tool tulis
- Jawaban Bahasa Indonesia dengan periode data dan satuan yang jelas
- Asisten demo deterministik; penyedia model eksternal bersifat opt-in

**Antarmuka**
- Navbar sticky dengan kontrol **ikon saja** (tema + profil di kanan atas)
- Drawer navigasi per peran dengan label teks yang terbaca
- Mode terang/gelap dengan persistensi dan tanpa kedip saat muat
- Mobile-first, target sentuh besar, fokus keyboard terlihat, dukungan reduced-motion

---

## Batasan yang diketahui

Lihat [`docs/QA_REPORT.md`](docs/QA_REPORT.md) untuk daftar lengkap beserta alasannya.
Ringkasnya: tidak ada integrasi enterprise, tidak ada SSO, pemulihan kata sandi dan
biometrik belum tersedia, pembaruan memakai polling bukan WebSocket, kebijakan
produksi melebihi target memakai aturan demo yang terdokumentasi, dan leaderboard
karyawan belum diaktifkan karena memerlukan definisi penilaian yang disetujui.

---

## Dokumentasi

| Dokumen | Isi |
| --- | --- |
| [`docs/PROJECT_OVERVIEW.md`](docs/PROJECT_OVERVIEW.md) | Konteks, masalah, sasaran produk |
| [`docs/PRODUCT_REQUIREMENTS.md`](docs/PRODUCT_REQUIREMENTS.md) | Kebutuhan per peran dan kriteria terima |
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | Struktur aplikasi, batas lapisan, penyimpangan dari spesifikasi |
| [`docs/ROLE_PERMISSION_MATRIX.md`](docs/ROLE_PERMISSION_MATRIX.md) | Matriks peran × izin × rute |
| [`docs/DATABASE_SCHEMA.md`](docs/DATABASE_SCHEMA.md) | Entitas, kolom, aturan penghitungan |
| [`docs/AUTHENTICATION_DESIGN.md`](docs/AUTHENTICATION_DESIGN.md) | Login dua langkah, batas OIDC, keterbatasan keamanan |
| [`docs/BEESHA_AI_ARCHITECTURE.md`](docs/BEESHA_AI_ARCHITECTURE.md) | Lapisan tool, otorisasi, privasi data |
| [`docs/DESIGN_SYSTEM.md`](docs/DESIGN_SYSTEM.md) | Palet, tipografi, komponen, aturan interaksi |
| [`docs/USER_FLOWS.md`](docs/USER_FLOWS.md) | Alur langkah-demi-langkah tiap peran |
| [`docs/API_REFERENCE.md`](docs/API_REFERENCE.md) | Rute, service, dan kontrak Livewire |
| [`docs/TEST_PLAN.md`](docs/TEST_PLAN.md) | Cakupan pengujian dan cara menjalankan |
| [`docs/QA_REPORT.md`](docs/QA_REPORT.md) | Hasil pengujian aktual dan batasan |
| [`docs/DEPLOYMENT_GUIDE.md`](docs/DEPLOYMENT_GUIDE.md) | Persiapan deployment dan daftar periksa |
| [`docs/DEMO_SCRIPT.md`](docs/DEMO_SCRIPT.md) | Skrip demonstrasi untuk pemangku kepentingan |

---

## Lisensi

Kode kerangka Laravel berlisensi MIT. Materi khusus PHM One disiapkan untuk
keperluan evaluasi internal PT Putra Hatim Mandiri.
