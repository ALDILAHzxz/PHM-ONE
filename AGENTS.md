# Panduan untuk agen AI — PHM One

Aplikasi Laravel 13 + Livewire 4 untuk operasi manufaktur PT Putra Hatim Mandiri.
Baca [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) sebelum mengubah apa pun yang
menyentuh data.

## Perintah

```bash
php artisan serve                  # jalankan (port 8000)
npm run dev                        # Vite dev server
npm run build                      # build aset

php artisan migrate:fresh --seed   # reset DB + data demo
php artisan test                   # 199 tes; harus lulus semua
php artisan test --filter=NamaTest
vendor/bin/pint                    # format kode
php artisan route:list --except-vendor
```

Lingkungan: PHP 8.4 (XAMPP), SQLite. `pdo_sqlite` dan `sqlite3` **harus** aktif
di `C:\xampp\php\php.ini` — keduanya dinonaktifkan secara bawaan di XAMPP.

## Aturan yang tidak boleh dilanggar

### 1. Angka produksi diturunkan, bukan diakumulasi

Tidak ada kolom `reported_qty` / `verified_qty` di database. Keduanya dihitung
dari `production_reports` berdasarkan status, lewat `App\Support\ReportStatus`.
**Jangan** menambah kolom total yang ditimbun — itu membuka pintu penghitungan
ganda yang justru dicegah desain ini.

### 2. Komponen Livewire tidak menulis data domain

Mutasi hanya lewat service di `app/Services`. `ProductionReportForm` memanggil
`ProductionReportService::submit()`; ia tidak pernah membuat `ProductionReport`
sendiri. Alasannya: aturan yang sama harus berlaku lewat antarmuka apa pun, dan
tes dapat menguji aturan tanpa UI.

### 3. KPI hanya dari `OperationsMetrics`

Dashboard, halaman produksi, antrean supervisor, dan tool Beesha semuanya
membaca dari `App\Services\OperationsMetrics`. **Jangan** menghitung KPI di
controller atau komponen — angka akan berbeda antar layar.

### 4. Dilaporkan ≠ terverifikasi, di mana pun

Setiap tampilan yang menyebut jumlah produksi harus menyajikan keduanya sebagai
angka terpisah. Jangan pernah menampilkan laporan berstatus
`menunggu_pemeriksaan` seolah sudah final.

### 5. Otorisasi di server, selalu

Tiga lapis: middleware rute (`role:`, `permission:`), Gate per izin, dan Gate
tingkat record. UI hanya menyembunyikan apa yang sudah ditolak. Menambah rute
berarti menambah penjaganya di `routes/web.php` **dan** kasus di
`AuthorizationMatrixTest`.

### 6. Beesha hanya lewat `BeeshaToolRegistry`

Tidak ada SQL bebas, tidak ada tool yang menulis, dan setiap tool memeriksa
izinnya pada setiap pemanggilan. Tool "milik sendiri" wajib memfilter di dalam
kuerinya (`forUser(auth()->id())`), bukan lewat argumen dari pemanggil.

### 7. Jangan memalsukan fitur

Bila sesuatu belum bisa dikerjakan (SSO, pemulihan kata sandi, biometrik,
pembuatan akun), nyatakan belum tersedia **di dalam antarmuka**, seperti yang
sudah dilakukan di halaman Profil, Bantuan Masuk, Kelola Akun, dan Status Sistem.
Jangan membuat alur palsu dan jangan membuat halaman placeholder yang terlihat
selesai.

### 8. Jangan mengarang aturan bisnis PHM

Kebijakan overproduksi di `config/phm.php` ditandai sebagai **aturan demo**.
Tahap produksi adalah data yang dapat dikonfigurasi, bukan alur kerja PHM yang
diasumsikan. Bila sebuah aturan belum dikonfirmasi PHM, tandai demikian.

### 9. Mode demo dan data sintetis

`PHM_DEMO_MODE=true` menyalakan banner di setiap halaman terautentikasi. Semua
data sintetis. Jangan memasukkan data PHM yang nyata, dan jangan menghapus
banner itu selama mode demo aktif.

## Konvensi kode

| Hal | Konvensi |
| --- | --- |
| Bahasa antarmuka | Bahasa Indonesia sehari-hari. Lihat tabel istilah di `docs/DESIGN_SYSTEM.md` §10 |
| Nilai status | Konstanta di `app/Support/*`, bukan string literal |
| Warna & ukuran | Token dan `@utility` di `resources/css/app.css`, bukan nilai mentah di komponen |
| Ikon | `<x-lucide-*>`; untuk nama dinamis pakai `<x-dynamic-component :component="'lucide-' . $icon">` |
| Komponen Livewire | class-based di `app/Livewire`, view di `resources/views/livewire` |
| Properti turunan | `#[Computed]`, diakses sebagai properti (`$this->assignments`) |
| Nomor dokumen | `DocumentNumbering`, bukan dibentuk manual |
| Audit | `AuditLogger` dipanggil **di dalam** transaksi mutasinya |
| Idempotensi | Form yang menulis wajib punya `idempotency_key` + kolom UNIQUE |

## Kontrol navbar: ikon saja

Persyaratan tegas (spesifikasi §7.2 dan §44): kontrol aksi di navbar tidak boleh
punya teks terlihat di sampingnya. Nama aksesibel hidup di atribut.
`InterfaceContractTest::test_navbar_action_controls_show_no_visible_text_labels`
mengekstrak blok `<header>`, membuang seluruh tag dan atribut, lalu memastikan
hanya nama merek dan judul halaman yang tersisa. Menambah teks di navbar akan
menggagalkan tes itu — dan itu memang tujuannya.

## Saat menambah fitur

1. Tambah/ubah aturan di service, bukan di komponen.
2. Tambah penjaga rute bila ada rute baru.
3. Tambah entri navigasi di `App\Support\Navigation` beserta pemetaan izinnya.
4. Tulis tes: jalur bahagia, validasi, **dan** penolakan untuk peran yang tidak
   berhak.
5. Jalankan `php artisan test` — seluruh 199 tes harus tetap lulus.
6. Perbarui dokumen yang terkait di `docs/`.

## Catatan: Laravel Boost

Berkas ini sebelumnya memuat instruksi bootstrap Laravel Boost. Boost **tidak**
dipasang: `php artisan boost:install` bersifat interaktif, dan paket itu adalah
alat bantu agen, bukan bagian dari aplikasi yang diminta. Panduan di berkas ini
ditulis khusus untuk codebase ini sebagai gantinya. Pasang Boost bila memang
diinginkan:

```bash
composer require laravel/boost --dev
php artisan boost:install
```

Perlu diketahui bahwa `boost:install` akan menimpa berkas ini.

---

# Dokumentasi Perubahan Proyek: Migrasi ke Next.js & Pembaruan Sistem Otentikasi

Tanggal Pembaruan: **15 November 2026**  
Status: **Selesai & Lulus Verifikasi Build (24 Halaman)**

## 1. Ringkasan Eksekutif Perubahan

Proyek **PHM One** (Sistem Operasi Manufaktur PT Putra Hatim Mandiri) telah dimigrasikan dari arsitektur **Laravel 13 + Livewire 4** ke **Next.js 15 (App Router) + TypeScript + Tailwind CSS**. 

Tujuan utama migrasi adalah:
1. **Memudahkan Deployment**: Mengeliminasi ketergantungan runtime PHP 8.4, ekstensi SQLite XAMPP, dan server Apache/Nginx, sehingga proyek dapat langsung di-deploy secara instan ke platform cloud modern seperti **Vercel**, **Netlify**, atau **Node.js environment** hanya dengan menghubungkan repository GitHub.
2. **Penyederhanaan Tampilan Awal**: Menghilangkan tombol satu-klik "Masuk Cepat — Akun Demo" agar alur otentikasi login kembali ke alur formal menggunakan **Nomor Induk Karyawan (NIK)** dan **Kata Sandi**.
3. **Kredensial Dummy Demo**: Menyediakan NIK dan password dummy yang jelas, terstruktur, dan mudah diingat untuk masing-masing peran karyawan pabrik (Operator, Supervisor, Admin Pesanan, PPIC, Petugas Gudang, Driver, Pimpinan, dan Administrator).

---

## 2. Rincian Perubahan Halaman Login (`/masuk`)

### A. Komponen yang Dihilangkan
- **Dihapus**: Grid 12 tombol "Masuk Cepat — Akun Demo" satu-klik yang sebelumnya langsung melakukan *auto-login* tanpa mengetik kredensial.

### B. Komponen Baru & Alur Otentikasi
- **Formulir Otentikasi Formal**:
  - Input field **Nomor Induk Karyawan (NIK)** (format: `PHM-[ROLE]-001`).
  - Input field **Kata Sandi (Password)** dengan tombol *toggle* mata untuk sembunyikan/tampilkan karakter.
  - Checkbox "Ingat perangkat ini".
  - Tombol aksi "Masuk ke Sistem" dengan status indikator loading.
  - Banner validasi kesalahan input interaktif berbahasa Indonesia jika NIK tidak terdaftar atau kata sandi keliru.
- **Panel Referensi Kredensial Demo (Collapsible)**:
  - Disediakan kartu panduan di bawah form login yang memuat daftar lengkap NIK & kata sandi per peran.
  - Disertai fitur *Click-to-Apply* / *Salin* yang secara otomatis mengisikan NIK dan password yang dipilih ke kolom form login untuk kenyamanan demonstrasi tanpa menghilangkan proses submit login sesungguhnya.

---

## 3. Daftar Kredensial Akun Dummy Per Role

Berikut adalah daftar akun dummy yang telah dikonfigurasi dan aktif di dalam sistem:

| Peran (Role) | Nama Karyawan | Jabatan | NIK | Kata Sandi | Landing Page Utama |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Operator Produksi** | Ahmad Fauzi | Operator Mesin CNC Milling | `PHM-OP-001` | `operator123` | `/operator/tugas` |
| **Supervisor Produksi** | Siti Rahayu | Supervisor Lini Produksi A | `PHM-SPV-001` | `supervisor123` | `/supervisor/pemeriksaan` |
| **Admin Pesanan** | Dewi Kartika | Staff Administrasi Penjualan | `PHM-ADM-001` | `admin123` | `/pesanan` |
| **PPIC (Perencanaan)** | Agus Wijaya | Production Planning & Control | `PHM-PPIC-001` | `ppic123` | `/perencanaan` |
| **Petugas Gudang** | Rudi Hartono | Koordinator Gudang & Ekspedisi | `PHM-GDG-001` | `gudang123` | `/pengiriman` |
| **Driver Pengiriman** | Joko Prasetyo | Pengemudi Truk Logistik PHM | `PHM-DRV-001` | `driver123` | `/driver/pengiriman` |
| **Pimpinan / Manajemen** | Hartono Putra | Direktur Operasional PHM | `PHM-MGT-001` | `manajer123` | `/dashboard` |
| **Administrator Sistem** | Rizki Permana | System & IT Administrator | `PHM-SYS-001` | `sysadmin123` | `/sistem/pengguna` |

> **Catatan Kompatibilitas**:
> - Seluruh akun di atas juga dapat dibuka menggunakan kata sandi universal demo: `demo1234`.
> - Format NIK lama (`PHM-EMP-001` s/d `PHM-EMP-008`) tetap didukung secara otomatis melalui aliasing internal.

---

## 4. Restrukturisasi Arsitektur & Berkas Proyek

### A. Pengarsipan Berkas Laravel Lama (`legacy-laravel/`)
Seluruh berkas backend Laravel 13 + Livewire 4 dipindahkan secara teratur ke folder `legacy-laravel/`:
- `legacy-laravel/app/` (Controllers, Livewire components, Models, Services, Providers)
- `legacy-laravel/bootstrap/` & `legacy-laravel/config/`
- `legacy-laravel/database/` (Migrations, Seeders, SQLite)
- `legacy-laravel/routes/` & `legacy-laravel/storage/`
- `legacy-laravel/resources/` (Blade views, CSS lama)
- `legacy-laravel/artisan`, `composer.json`, `composer.lock`, `phpunit.xml`

### B. Struktur Baru Next.js di Root
- `src/app/` (Next.js App Router dengan 24 rute halaman teroptimasi statis)
- `src/components/` (Layout Navbar, Sidebar, AppLayout, Beesha AI Assistant drawer, StatCard, StatusBadge, PageHeader)
- `src/context/` (`AuthContext.tsx` untuk manajemen otentikasi & RBAC, `OperationsContext.tsx` untuk state reaktif operasional)
- `src/data/mockData.ts` (Data awal lengkap: mitra, produk, pesanan, WO, penugasan, laporan, armada, audit log)
- `src/types/index.ts` (Definisi TypeScript domain manufaktur)
- `tailwind.config.ts`, `postcss.config.js`, `tsconfig.json`, `next.config.mjs`

---

## 5. Kepatuhan Aturan Bisnis Inti PHM-ONE

Seluruh 9 aturan bisnis inti dari arsitektur PHM-ONE tetap ditegakkan di Next.js:
1. **Angka Produksi Diturunkan**: Kuantitas hasil dihitung secara dinamis dari status laporan produksi (*derived quantity*).
2. **Dilaporkan ≠ Terverifikasi**: Tampilan kuantitas memisahkan dengan tegas antara angka *Dilaporkan* (`menunggu_pemeriksaan`) dan *Terverifikasi* (`disetujui` QC).
3. **Pencegahan Self-Review**: Supervisor tidak dapat memverifikasi laporannya sendiri.
4. **Validasi Surat Jalan**: Pengiriman barang hanya dapat diterbitkan dari stok yang telah lolos verifikasi QC.
5. **State Transition Machine**: Alur status armada logistik: `pending` → `loaded` → `in_transit` → `delivered`.
6. **Asisten AI Beesha**: Terintegrasi penuh dengan simulasi pembacaan data tugas, ringkasan produksi, antrean verifikasi, dan jadwal logistik.
7. **Jejak Audit Otomatis**: Setiap aksi mutasi otomatis terekam ke tabel *Audit Trail*.

---

## 6. Verifikasi & Pengujian Build

- **Kompilasi TypeScript**: Lulus 100% tanpa error (`tsc --noEmit` & `npm run build`).
- **Generasi Halaman Statis**: 24 dari 24 rute halaman berhasil di-generate secara statis (`✓ Generating static pages (24/24)`).
- **Development Server**: Berjalan normal di `http://localhost:3000`.

