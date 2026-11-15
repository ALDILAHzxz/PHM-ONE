# Ringkasan Lengkap Perubahan Proyek: Migrasi PHM One ke Next.js

Berkas ini mendokumentasikan seluruh riwayat perubahan teknis, struktural, fungsional, dan desain antarmuka yang telah diimplementasikan pada repositori **PHM One** (PT Putra Hatim Mandiri).

---

## 1. Latar Belakang & Tujuan Migrasi

Proyek awal **PHM One** dibangun menggunakan kombinasi **Laravel 13 + Livewire 4 + SQLite**. Meskipun fungsional di lingkungan lokal XAMPP, arsitektur tersebut memiliki beberapa tantangan untuk deployment cepat:
- Memerlukan instalasi runtime PHP 8.4 dengan ekstensi `pdo_sqlite` dan `sqlite3` yang dikonfigurasi manual.
- Memerlukan konfigurasi web server Apache/Nginx serta proses background daemon.
- Sulit di-deploy langsung secara instan (*one-click deploy*) ke layanan cloud hosting modern.

**Solusi**:
Seluruh aplikasi dimigrasikan ke **Next.js 15 (App Router)** dengan **React 19**, **TypeScript**, dan **Tailwind CSS**. Dengan arsitektur ini:
- Aplikasi dapat di-deploy ke **Vercel** atau **Netlify** dalam hitungan detik tanpa server backend terpisah.
- Kinerja halaman jauh lebih responsif dengan rendering statis dan *client-side state management*.
- Desain antarmuka, warna, serta aturan manufaktur PHM tetap dipertahankan 100%.

---

## 2. Rincian Perubahan Tampilan Login (`/masuk`)

Sesuai instruksi pengguna:
1. **Penghapusan Fitur "Masuk Cepat — Akun Demo"**:
   - Blok 12 tombol satu-klik yang sebelumnya menampilkan daftar peran demo di bawah form telah **dihapus**.
   - Tidak ada lagi tombol instan yang langsung melompati tahap autentikasi.
2. **Penerapan Form Login Formal (NIK & Password)**:
   - Pengguna kini login menggunakan **Nomor Induk Karyawan (NIK)** dan **Kata Sandi (Password)**.
   - Dilengkapi fitur *toggle* mata (lihat/sembunyikan kata sandi) dan *checkbox* "Ingat perangkat ini".
   - Dilengkapi validasi pesan kesalahan berbahasa Indonesia yang jelas bila NIK tidak terdaftar atau kata sandi tidak cocok.
3. **Panel Referensi Kredensial Demo (Collapsible / Bantuan Penguji)**:
   - Disediakan kartu bantuan rapi di bawah form yang dapat diciutkan (*collapse/expand*).
   - Menampilkan daftar NIK dan kata sandi untuk setiap peran.
   - Penguji/evaluator dapat mengklik salah satu kartu peran untuk **mengisi NIK & password secara otomatis** ke kolom input tanpa harus mengetik manual, namun proses masuk tetap diverifikasi melalui penekanan tombol **"Masuk ke Sistem"**.

---

## 3. Daftar Lengkap Akun & Kredensial Dummy Per Role

| Peran (Role) | Nama Karyawan | Jabatan | NIK | Kata Sandi | Halaman Utama (Default Route) |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Operator Produksi** | Ahmad Fauzi | Operator Mesin CNC Milling | `PHM-OP-001` | `operator123` | `/operator/tugas` |
| **Supervisor Produksi** | Siti Rahayu | Supervisor Lini Produksi A | `PHM-SPV-001` | `supervisor123` | `/supervisor/pemeriksaan` |
| **Admin Pesanan** | Dewi Kartika | Staff Administrasi Penjualan | `PHM-ADM-001` | `admin123` | `/pesanan` |
| **PPIC (Perencanaan)** | Agus Wijaya | Production Planning & Control | `PHM-PPIC-001` | `ppic123` | `/perencanaan` |
| **Petugas Gudang** | Rudi Hartono | Koordinator Gudang & Ekspedisi | `PHM-GDG-001` | `gudang123` | `/pengiriman` |
| **Driver Pengiriman** | Joko Prasetyo | Pengemudi Truk Logistik PHM | `PHM-DRV-001` | `driver123` | `/driver/pengiriman` |
| **Pimpinan / Manajemen** | Hartono Putra | Direktur Operasional PHM | `PHM-MGT-001` | `manajer123` | `/dashboard` |
| **Administrator Sistem** | Rizki Permana | System & IT Administrator | `PHM-SYS-001` | `sysadmin123` | `/sistem/pengguna` |

> **Catatan Fleksibilitas**:
> - Password cadangan universal: `demo1234` (dapat digunakan untuk semua akun di atas).
> - NIK format lama (`PHM-EMP-001` s/d `PHM-EMP-008`) tetap dapat dikenali dan dipetakan secara otomatis.

---

## 4. Struktur Berkas & Modul Aplikasi (Next.js)

### A. Pengarsipan Berkas Lama
Seluruh berkas Laravel 13 dipindahkan ke dalam direktori `legacy-laravel/` sebagai arsip:
- `legacy-laravel/app/`
- `legacy-laravel/bootstrap/`
- `legacy-laravel/config/`
- `legacy-laravel/database/`
- `legacy-laravel/routes/`
- `legacy-laravel/storage/`
- `legacy-laravel/resources/`
- `legacy-laravel/artisan`, `composer.json`, `composer.lock`, `phpunit.xml`

### B. Struktur Baru Next.js di Root
```
PHM-ONE/
├── public/                     # Aset statis & logo
├── src/
│   ├── app/                    # 24 Halaman Rute Next.js (App Router)
│   │   ├── globals.css         # Token desain & utilitas Tailwind PHM
│   │   ├── layout.tsx          # Root layout membungkus Auth & Operations Providers
│   │   ├── page.tsx            # Redirection handler
│   │   ├── masuk/page.tsx      # Formulir Login NIK & Password
│   │   ├── dashboard/page.tsx  # Dashboard KPI Utama (Eksekutif)
│   │   ├── operator/
│   │   │   ├── tugas/page.tsx          # Daftar tugas produksi operator
│   │   │   ├── catat-hasil/page.tsx    # Formulir pelaporan hasil pengerjaan mesin
│   │   │   └── riwayat/page.tsx        # Riwayat laporan & status verifikasi
│   │   ├── supervisor/
│   │   │   ├── pemeriksaan/page.tsx    # Antrean verifikasi QC supervisor
│   │   │   ├── kendala/page.tsx        # Catatan kendala mesin/material
│   │   │   └── riwayat/page.tsx        # Arsip keputusan pemeriksaan
│   │   ├── pesanan/
│   │   │   ├── page.tsx                # Daftar Purchase Order pelanggan
│   │   │   └── tambah/page.tsx         # Formulir input pesanan baru
│   │   ├── perencanaan/
│   │   │   ├── page.tsx                # Ikhtisar perencanaan produksi PPIC
│   │   │   ├── work-order/page.tsx     # Penerbitan Work Order lini
│   │   │   └── penugasan/page.tsx      # Alokasi mesin & shift operator
│   │   ├── pengiriman/
│   │   │   ├── page.tsx                # Jadwal ekspedisi gudang
│   │   │   └── catat/page.tsx          # Penerbitan Surat Jalan resmi
│   │   ├── driver/
│   │   │   ├── pengiriman/page.tsx     # Antrean antaran aktif driver
│   │   │   └── riwayat/page.tsx        # Riwayat pengiriman selesai
│   │   └── sistem/
│   │       ├── pengguna/page.tsx       # Manajemen akun & NIK karyawan
│   │       ├── audit/page.tsx          # Jejak audit transaksi (Audit Trail)
│   │       └── status/page.tsx         # Status kesehatan server & runtime
│   ├── components/
│   │   ├── layout/
│   │   │   ├── Navbar.tsx      # Top bar (Profil, Role switcher, Asisten AI, Dark Mode)
│   │   │   ├── Sidebar.tsx     # Sidebar navigasi sesuai hak akses peran
│   │   │   └── AppLayout.tsx   # Pembungkus layout + Banner Mode Demo
│   │   ├── assistant/
│   │   │   └── BeeshaAssistant.tsx # Drawer Chat Asisten AI Cerdas Beesha
│   │   └── ui/
│   │       ├── PageHeader.tsx  # Standar judul & aksi halaman
│   │       ├── StatCard.tsx    # Kartu KPI angka besar
│   │       └── StatusBadge.tsx # Label status warna konsisten
│   ├── context/
│   │   ├── AuthContext.tsx     # Otentikasi, sesi login, dan switch role
│   │   └── OperationsContext.tsx # Manajemen state reaktif operasional
│   ├── data/
│   │   └── mockData.ts         # Data awal simulasi manufaktur lengkap
│   └── types/
│       └── index.ts            # Tipe data TypeScript untuk semua entitas
├── next.config.mjs             # Konfigurasi Next.js (outputFileTracingRoot)
├── tailwind.config.ts          # Konfigurasi warna Navy (#1B365D), Biru (#0077C8), font
├── tsconfig.json               # Konfigurasi compiler TypeScript
├── package.json                # Dependensi Next.js 15, React 19, Lucide React
└── README.md                   # Dokumentasi publik proyek
```

---

## 5. Kepatuhan Aturan Bisnis Inti PHM-ONE

Seluruh aturan arsitektur manufaktur PHM-ONE tetap ditegakkan:
1. **Angka Produksi Bersifat Turunan (*Derived Quantity*)**:
   Tidak ada kolom total kuantitas yang ditimbun sembarangan. Angka hasil dihitung dinamis dari status laporan produksi.
2. **Dilaporkan ≠ Terverifikasi**:
   Laporan berstatus `menunggu_pemeriksaan` tidak pernah digabungkan atau dianggap sebagai stok barang jadi yang terverifikasi (`disetujui`).
3. **Pencegahan Self-Review**:
   Supervisor dicegah memverifikasi laporan produksinya sendiri.
4. **Validasi Surat Jalan**:
   Gudang hanya dapat menerbitkan kuantitas pengiriman berdasarkan stok yang sudah lolos uji verifikasi QC.
5. **Mesin Transisi Status Pengiriman**:
   Armada driver mengikuti alur: `pending` → `loaded` (dimuat) → `in_transit` (dalam rute) → `delivered` (diterima mitra) atau `failed` (gagal).
6. **Asisten AI Beesha**:
   Dapat merespons kueri operasional dalam Bahasa Indonesia: *cek tugas saya*, *ringkasan produksi*, *antrian pemeriksaan*, *cek pesanan*, *data pengiriman*.
7. **Audit Logging Otomatis**:
   Setiap transaksi otomatis mencatat riwayat pembuat, waktu, dan rincian aksi pada tabel *Audit Trail*.

---

## 6. Status Pengujian & Akses Server

- **Kompilasi TypeScript**: Lulus 100% tanpa error (`npm run build`).
- **Generasi Halaman Statis**: 24/24 rute halaman berhasil dibuat secara optimal.
- **Development Server**: Aktif berjalan di `http://localhost:3000`.
- **Kesiapan Deploy**: Siap dihubungkan ke GitHub dan di-deploy ke Vercel kapan saja saat pengguna menghendaki.

---

# Perbaikan Desain Antarmuka (16 November 2026)

Dilakukan setelah migrasi Next.js, berdasarkan temuan tampilan yang berantakan
pada halaman Riwayat Laporan dan acuan `DESIGN-PHM-REFERENSI.md`.

## A. Bug tata letak: logo navbar terpotong sidebar

**Gejala:** blok "PHM ONE / MANUFAKTUR" tertutup panel sidebar.

**Penyebab:** pita mode demo berada di aliran normal dan mendorong navbar ±28px
ke bawah, sedangkan sidebar dipasang `fixed top-16` (64px). Navbar menempati
y=28..92 sementara sidebar mulai di y=64, sehingga 28px bagian bawah navbar
tertutup.

**Perbaikan:** pita + navbar digabung menjadi satu blok `sticky top-0`, dan
tingginya ditulis sekali sebagai `--h-shell` di `globals.css`. Sidebar, konten,
dan drawer membaca angka yang sama. Konten juga tidak lagi memakai `ml-64` dan
`mx-auto` bersamaan (yang membuat halaman miring); sekarang `md:pl-sidebar` pada
pembungkus, `mx-auto` pada wadah di dalamnya.

## B. Tabel yang terhimpit

7–8 kolom berteks 12px tanpa kontrol lebar membuat nama komponen pecah menjadi
empat baris. Dibuat komponen `DataTable` yang:
- mewajibkan setiap kolom bisa menyatakan lebarnya dan memakai `table-fixed`;
- meringkas kolom (mis. catatan operator + hasil review digabung);
- berubah menjadi kartu bertumpuk di layar < 1024px, bukan scroll horizontal.

Diterapkan ke 9 halaman bertabel; tidak ada lagi `<table>` mentah di `src/app`.

## C. Palet: satu warna merek

Mengikuti prinsip dasbor Jarwo — satu warna merek, warna lain hanya status.
- Merek: `#0d6076` (terang) / `#38bdf8` (gelap). Nilai terang dipilih karena
  `#0e7490` hanya mencapai 4,4:1 dengan teks putih.
- Dihapus: gradien biru→indigo pada tombol Beesha, varian dekoratif
  `blue|emerald|amber|indigo|rose` pada `StatCard` (diganti nada bermakna
  `brand|aman|waspada|kritis`), dan status berwarna merek pada `StatusBadge`.
- Token didefinisikan sebagai CSS custom property dan dipetakan ke Tailwind,
  sehingga `bg-kartu`/`text-teks` bekerja di kedua tema tanpa varian `dark:`.

**Catatan:** tema **terang** dijadikan default (referensi memakai gelap). Alasan:
aplikasi didemonstrasikan di ruang rapat dan dipakai di lantai produksi yang
terang. Tema gelap tetap lengkap dan dapat diaktifkan dari navbar.

## D. Keterbacaan untuk pengguna Gen X & milenial

- Ukuran dasar 15px; tidak ada lagi teks di bawah 12px (sebelumnya 37 tempat
  memakai 11px atau lebih kecil, 120 tempat memakai 12px).
- Target sentuh minimum 44px pada seluruh tombol, input, dan baris navigasi.
- Ikon dalam tombol dinaikkan dari 14px ke 18px.
- Navigasi sidebar naik dari 12px ke 15px.

## E. Penyederhanaan

- Tombol "Tanya Beesha AI" berwarna gradien dengan titik berdenyut dipindahkan
  dari navbar menjadi tombol mengapung di kanan bawah. Navbar kini hanya berisi
  tiga kontrol: menu (mobile), tema, dan profil.
- Panel kredensial demo di halaman login diringkas dari tiga baris mono per akun
  menjadi dua baris terbaca.
- Radius disatukan ke dua nilai: 8px (kontrol) dan 12px (kartu).

## F. Perbaikan teknis lain

- **Tabrakan nama kelas:** token ukuran font bernama `kartu` bertabrakan dengan
  warna bernama `kartu`. Tailwind menghasilkan dua aturan `.text-kartu` dan versi
  warna menang, sehingga teks menjadi sewarna permukaan kartu (tidak terbaca).
  Token ukuran diganti namanya menjadi `isi`.
- **Pita mode demo:** memakai token tetap `--pita-latar` (#b45309) alih-alih
  `--waspada`, karena `--waspada` berubah menjadi kuning terang di mode gelap dan
  teks putih di atasnya hanya ~1,7:1.
- Skrip anti-kedip tema ditambahkan di `<head>`, beserta `theme-color` per tema.
- Tautan "Lewati ke konten utama", `aria-label` pada tombol ikon, `aria-current`
  pada menu aktif, dan dukungan `prefers-reduced-motion`.

## G. Verifikasi

- `npx tsc --noEmit` — bersih.
- `npx next build` — 24/24 halaman statis ter-generate.
- 21/21 rute diuji lewat HTTP pada `next start`, seluruhnya 200.
- **Belum diverifikasi secara visual di browser** (tidak tersedia alat otomasi
  browser di lingkungan pengerjaan). Pemeriksaan dilakukan terhadap markup yang
  dirender, aturan CSS hasil build, dan perhitungan tata letak.
