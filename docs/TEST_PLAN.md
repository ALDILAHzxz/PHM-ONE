# Rencana Pengujian

## 1. Strategi

Pengujian berjalan di tiga tingkat, semuanya lewat satu perintah
(`php artisan test`):

| Tingkat | Cara | Yang dibuktikan |
| --- | --- | --- |
| Service domain | memanggil service langsung dengan `User` nyata | aturan bisnis berlaku terlepas dari antarmuka |
| Komponen Livewire | `Livewire::test()` dengan aksi dan properti nyata | perilaku form, validasi, langkah, pencegahan submit ganda |
| HTTP | `$this->actingAs()->get()` lewat kernel penuh | middleware, otorisasi, dan markup yang benar-benar dirender |

Yang **tidak** dipakai: mock untuk database, mock untuk otorisasi, atau stub
untuk service domain. Setiap tes memakai database SQLite in-memory yang
di-migrate dan di-seed dengan data demo yang sama dengan yang dilihat pengguna.

## 2. Menjalankan tes

```bash
# seluruh suite
php artisan test

# satu kelas
php artisan test --filter=ProductionReportingTest

# satu metode
php artisan test --filter=test_operator_submits_thirty_five_units_and_totals_move_correctly

# beberapa kelas
php artisan test --filter="PlanningTest|MetricsConsistencyTest"
```

Konfigurasi lingkungan tes di `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="SESSION_DRIVER" value="array"/>
<env name="CACHE_STORE" value="array"/>
<env name="PHM_DEMO_MODE" value="true"/>
<env name="BEESHA_PROVIDER" value="demo"/>
<env name="BEESHA_API_KEY" value=""/>
<env name="APP_TIMEZONE" value="Asia/Jakarta"/>
<env name="APP_LOCALE" value="id"/>
<env name="BCRYPT_ROUNDS" value="4"/>
```

`BEESHA_API_KEY` dikosongkan secara sengaja: tidak ada tes yang boleh menghubungi
layanan luar.

## 3. Infrastruktur tes

`tests/TestCase.php` menyediakan:

| Helper | Kegunaan |
| --- | --- |
| `seedFoundation()` | peran, izin, dan data referensi saja |
| `seedDemoData()` | semua di atas + akun demo + data operasional (termasuk baseline §37) |
| `operator()` … `sysadmin()` | akses cepat ke setiap akun demo per peran |
| `demoUser(string $employeeNumber)` | akun demo tertentu |
| `scenarioAssignment()` | penugasan DEMO-WO-004 dari skenario spesifikasi |
| `userWithRole(string $roleCode, array $attributes = [])` | pengguna baru dengan satu peran, untuk tes otorisasi |

`database/factories/UserFactory.php` menyediakan state `inactive()` dan `demo()`.

## 4. Cakupan per kelas tes

### `AuthenticationTest` (18 tes)
Halaman login dapat dijangkau tamu; tamu dialihkan dari rute terlindungi;
identifikasi NIK berhasil/gagal/akun nonaktif; kata sandi benar dan salah;
pengalihan per peran untuk 8 peran; login demo hanya untuk akun demo; login demo
ditolak saat mode demo mati; persistensi sesi; logout tidak menghapus record;
middleware akun nonaktif (diuji terisolasi, karena `actingAs` menyimpan satu
instance pengguna); timestamp login terakhir; pengguna multi-peran mulai di peran
utama; panel demo hanya memuat akun satu-peran; profil menampilkan NIK.

### `AuthorizationMatrixTest` (30 tes)
Data provider berisi 23 rute ruang kerja; setiap rute dijalankan terhadap
**kedelapan** peran dan menegaskan 200 untuk yang berhak serta **403** untuk yang
tidak. Ditambah: entri drawer tidak pernah menghasilkan 403; gate laporan per
record; driver tidak dapat memindahkan pengiriman orang lain; perpindahan peran
ditolak untuk peran yang tidak dimiliki dan diterima untuk yang dimiliki;
halaman Bantuan dan Profil terbuka untuk semua peran.

### `ProductionReportingTest` (24 tes)
Baseline §37; operator melihat penugasannya; validasi jumlah (wajib, > 0,
negatif, desimal); kategori dan penjelasan kendala wajib saat ada kendala dan
tidak wajib saat tidak ada; field kendala dibersihkan saat beralih ke "tidak
ada"; nilai dipertahankan saat validasi gagal; langkah konfirmasi tidak
menyimpan; submit 35 pcs memindahkan total dengan benar; kejadian audit tertulis;
penugasan berubah menjadi "berjalan"; idempotency key tidak membuat laporan
kedua; `save()` dua kali menghasilkan satu laporan; operator tidak dapat
melaporkan penugasan orang lain (lewat URL dan lewat service); penugasan tertutup
menolak laporan; jumlah melebihi sisa target ditolak tanpa dipotong; jumlah
tepat sisa target diterima; izin pengecualian memperbolehkan melebihi dan
menandai laporan; riwayat hanya memuat laporan sendiri; detail kendala tersimpan.

### `SupervisorVerificationTest` (12 tes)
Antrean terlihat; antrean hanya memuat laporan menunggu; verifikasi memindahkan
total terverifikasi; record pemeriksaan dan audit tertulis; permintaan perbaikan
wajib beralasan; laporan perlu perbaikan keluar dari total tetapi barisnya tetap
ada dan tidak menjadi terverifikasi; laporan tidak dapat diperiksa dua kali;
tidak ada yang dapat memverifikasi laporannya sendiri; operator tidak dapat
membuka layar pemeriksaan maupun memverifikasi lewat service; **skenario
spesifikasi §37 lengkap** dari baseline hingga verifikasi; dua sesi melihat
keadaan server yang sama.

### `MetricsConsistencyTest` (16 tes)
dilaporkan = menunggu + terverifikasi; laporan perlu perbaikan dan dibatalkan
dikecualikan dari total perusahaan dan dari total penugasan; verifikasi tidak
menghitung ganda; dashboard dan Beesha melaporkan angka identik; antrean
supervisor dan Beesha sepakat; layar operator dan Beesha sepakat soal sisa
target; persentase hanya dihitung bila target > 0; sisa tidak negatif; dashboard
menampilkan dilaporkan dan terverifikasi terpisah; dashboard menyatakan
kesegaran data; dashboard memakai interval polling terkonfigurasi; laporan baru
menaikkan KPI dilaporkan tanpa menaikkan terverifikasi; peringatan diturunkan
dari record hidup; peringatan terurut dari paling parah; hanya produksi
terverifikasi yang dihitung siap kirim.

### `OrderManagementTest` (14 tes)
Daftar terlihat; pencarian per mitra dan per nomor PO; filter status; detail
menampilkan angka pemenuhan; semua field wajib divalidasi; jumlah harus bilangan
bulat positif; target kirim tidak boleh di masa lalu; langkah review tidak
menyimpan; pesanan dibuat dan langsung tersedia bagi PPIC beserta kejadian
auditnya; PO ganda untuk mitra yang sama ditolak; PO sama untuk mitra berbeda
diizinkan; pengguna tanpa izin buat tidak dapat membuka form; operator tidak
dapat membuka detail pesanan.

### `PlanningTest` (13 tes)
Ikhtisar perencanaan terlihat; work order dibuat dari pesanan dengan audit;
memilih pesanan mengisi produk dan sisa jumlah yang belum direncanakan; work
order tidak boleh melebihi jumlah pesanan; penugasan tidak boleh melebihi sisa
work order; hanya karyawan berizin produksi dapat ditugaskan; penugasan baru
muncul di Tugas Saya operator; penugasan dibuat lewat form Livewire; daftar
karyawan hanya memuat operator; tahap produksi dapat dinonaktifkan tanpa
perubahan kode; operator tidak dapat membuat penugasan; supervisor tidak dapat
membuat work order; service menolak pemanggil tak berwenang.

### `ShippingAndDeliveryTest` (18 tes)
Ruang kerja gudang terlihat; jumlah divalidasi; pengiriman sebagian dicatat
dengan nomor surat jalan otomatis; melebihi sisa pesanan ditolak; melebihi stok
terverifikasi ditolak; kiriman ganda menghasilkan satu record; status pemenuhan
pesanan diperbarui; operator tidak dapat mencatat pengiriman; driver hanya
melihat pengirimannya; driver memulai pengiriman dan kejadiannya tercatat; status
"diterima" mewajibkan nama penerima; perjalanan lengkap tercatat dengan minimal
empat kejadian; laporan kendala mewajibkan penjelasan; kendala tersimpan dan
muncul sebagai peringatan; transisi tidak valid ditolak; pengiriman yang sudah
diterima tidak dapat bergerak lagi; riwayat gudang memuat pengiriman selesai;
riwayat driver tercakup pada driver yang masuk.

### `BeeshaAssistantTest` (26 tes)
Tombol mengapung hadir di **19 halaman lintas 7 peran**; tidak dirender untuk
tamu; tidak dirender bagi pengguna tanpa izin; chat buka/tutup; pertanyaan
menyimpan dua pesan; pertanyaan kosong ditolak; saran menutup setelah pertanyaan
pertama; riwayat dapat dihapus; pertanyaan target operator mengembalikan angka
nyata; jawaban membedakan dilaporkan dan terverifikasi; jawaban menyebut periode;
pertanyaan supervisor dijawab dari data; pertanyaan driver mengembalikan
pengirimannya; pertanyaan prosedural dijawab tanpa menyentuh data; pertanyaan
"salah input" menjelaskan jalur perbaikan; pertanyaan tak dikenal dijawab apa
adanya tanpa mengarang; operator tidak dapat menjangkau KPI manajemen maupun
antrean pemeriksaan; operator yang bertanya pertanyaan manajemen hanya mendapat
angkanya sendiri; jawaban operator tidak memuat nama karyawan lain; katalog tool
tersaring per peran; **tidak ada tool yang menulis** (setiap tool dipanggil
sebagai setiap peran, lalu jumlah baris di empat tabel inti dibandingkan); saran
tersaring sesuai izin; **setiap pertanyaan saran untuk 6 peran benar-benar
terjawab**; penyedia demo dipakai dan dilabeli saat tidak ada API key;
pemanggilan tool diaudit.

### `InterfaceContractTest` (28 tes)
Navbar sticky dengan `top-0` dan `z-50`; tinggi 64/72 px; **kontrol navbar tanpa
teks terlihat** (blok `<header>` diekstrak, tag dan atribut dibuang, lalu
dipastikan hanya nama merek dan judul halaman yang tersisa); tombol ikon punya
nama aksesibel; urutan hamburger → tema → profil; target sentuh 44 px; entri
drawer punya label teks; drawer dapat ditutup Escape dan backdrop dengan
`role="dialog"`; baris drawer minimum 52 px; tema diterapkan sebelum render
pertama di `<head>`; pilihan tema disimpan; kedua tema mendefinisikan seluruh
token semantik; nilai palet korporat dipakai; FAB `fixed` kanan bawah dengan
`z-40`; FAB di bawah overlay drawer; `<main>` punya `pb-28`; panel chat punya
tombol tutup dan input; form produksi satu kolom maks 720 px; form menampilkan
nilai yang diketahui sebagai baca-saja dengan **tepat satu** input angka; field
kendala tersembunyi sampai kendala dilaporkan; satu tombol utama dominan;
viewport mendukung mobile dan safe-area; skip link adalah elemen fokus pertama;
reduced-motion dihormati; fokus selalu terlihat; ukuran input dan tombol sesuai
spesifikasi; **hanya kartu yang dapat diklik** mendapat efek angkat; mode demo
diungkapkan di setiap halaman terautentikasi.

## 5. Pemetaan ke §36 spesifikasi

| Butir §36 | Kelas tes |
| --- | --- |
| Autentikasi — pengalihan peran, penolakan, sesi, logout, profil | `AuthenticationTest`, `AuthorizationMatrixTest` |
| Operator — tampilan, input, validasi, field kondisional, konfirmasi, submit, cegah ganda, status | `ProductionReportingTest` |
| Supervisor — daftar menunggu, verifikasi, permintaan perbaikan, riwayat audit | `SupervisorVerificationTest` |
| Pesanan — buat, cari, lihat detail, perbarui | `OrderManagementTest` |
| Pengiriman — sebagian, validasi, status pesanan | `ShippingAndDeliveryTest` |
| Dashboard — progres dilaporkan, terverifikasi setelah persetujuan, sinkronisasi lintas sesi, konsistensi | `MetricsConsistencyTest`, `SupervisorVerificationTest` |
| Beesha AI — FAB bertahan, buka/tutup, respons sesuai peran, pertanyaan cepat, tanpa paparan tak sah | `BeeshaAssistantTest` |
| Antarmuka — navbar sticky, tema, ikon saja, profil, mobile, keyboard, kontras gelap | `InterfaceContractTest` |

## 6. Prinsip penulisan tes di proyek ini

1. **Uji aturan di tempat aturan itu hidup.** Batas kuantitas diuji lewat
   service, bukan hanya lewat form, sehingga antarmuka lain tetap terlindungi.
2. **Tegaskan markup, bukan niat.** Persyaratan "ikon saja" diuji dengan
   mengekstrak navbar dan membuang seluruh atribut — bukan dengan mencari
   keberadaan sebuah kelas CSS.
3. **Buktikan negatifnya.** Untuk setiap "peran X dapat", ada "peran Y tidak
   dapat" yang menegaskan 403.
4. **Buat kondisi secara eksplisit.** Bila sebuah tes bergantung pada keadaan
   tertentu (misalnya laporan berstatus dikecualikan bertanggal hari ini), tes
   itu membuat keadaannya sendiri alih-alih berharap data seed memenuhinya.
5. **Jangan pernah menghubungi layanan luar.** `BEESHA_API_KEY` kosong di
   `phpunit.xml`; hanya jalur fallback penyedia eksternal yang diuji.

## 7. Yang belum tercakup

Dinyatakan terbuka; lihat [`QA_REPORT.md`](QA_REPORT.md) §5 untuk alasan dan
mitigasinya: rendering visual di browser sungguhan, breakpoint di perangkat
fisik, pengukuran kontras otomatis, perilaku papan tombol mobile, panggilan API
Anthropic sungguhan, pembacaan pembaca layar, dan pengujian beban konkurensi.
