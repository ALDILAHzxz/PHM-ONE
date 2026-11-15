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
