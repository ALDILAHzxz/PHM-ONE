# Sistem Desain PHM One

Semua token berada di `resources/css/app.css`. Tidak ada nilai warna, ukuran
font, atau radius yang ditulis langsung di komponen kecuali lewat token atau
utility di berkas tersebut.

## 1. Warna

### Token semantik

Warna didefinisikan sebagai custom property yang **berubah per tema**, lalu
dipetakan ke utility Tailwind dengan `@theme inline`. Markup memakai
`bg-surface`, `text-body`, `border-line` — bukan `dark:` di setiap elemen.

| Token Tailwind | Variabel | Terang | Gelap |
| --- | --- | --- | --- |
| `bg-page` | `--phm-page` | `#F2F4F7` | `#0B111B` |
| `bg-surface` | `--phm-surface` | `#FFFFFF` | `#172131` |
| `bg-surface-raised` | `--phm-surface-raised` | `#FFFFFF` | `#1D2A3D` |
| `bg-surface-muted` | `--phm-surface-muted` | `#F6F8FB` | `#131D2B` |
| `text-body` | `--phm-body` | `#1E293B` | `#F2F7FF` |
| `text-muted` | `--phm-muted` | `#475569` | `#BDCCE0` |
| `text-interactive` | `--phm-interactive` | `#0077C8` | `#83C7FF` |
| `text-icon-accent` | `--phm-icon-accent` | `#0077C8` | `#8CCBFF` |
| `border-line` | `--phm-border` | `#E2E8F0` | `#2C3E57` |
| `border-line-strong` | `--phm-border-strong` | `#64748B` | `#4A719A` |
| `bg-navbar` | `--phm-navbar` | `#1B365D` | `#0F1B2D` |

### Konstanta merek

| Token | Nilai | Dipakai untuk |
| --- | --- | --- |
| `bg-navy` / `--phm-navy` | `#1B365D` | navbar mode terang, identitas |
| `bg-brand` / `--phm-blue` | `#0077C8` | aksi utama, batang terverifikasi, fokus |

### Kontras (WCAG 2.2 AA)

| Kombinasi | Rasio | Target | Hasil |
| --- | --- | --- | --- |
| `#1E293B` pada `#FFFFFF` | ~13.9:1 | 4.5:1 | ✅ |
| `#475569` pada `#FFFFFF` | ~7.4:1 | 4.5:1 | ✅ |
| `#0077C8` pada `#FFFFFF` | ~4.7:1 | 4.5:1 | ✅ |
| Putih pada `#0077C8` | ~4.7:1 | 4.5:1 | ✅ |
| Putih pada `#1B365D` | ~11.4:1 | 4.5:1 | ✅ |
| `#F2F7FF` pada `#172131` | ~14.8:1 | 4.5:1 | ✅ |
| `#BDCCE0` pada `#172131` | ~9.7:1 | 4.5:1 | ✅ |
| `#83C7FF` pada `#172131` | ~8.6:1 | 4.5:1 | ✅ |
| `#64748B` (border input) pada `#FFFFFF` | ~4.6:1 | 3:1 non-teks | ✅ |
| `#4A719A` (border input gelap) pada `#172131` | ~3.2:1 | 3:1 non-teks | ✅ |

Border kontrol form sengaja memakai `--phm-border-strong` (bukan `--phm-border`)
agar memenuhi syarat kontras non-teks 3:1. Border kartu bersifat dekoratif dan
boleh lebih lembut.

### Warna status

Dipakai hanya bila bermakna, selalu disertai teks (tidak pernah warna saja):

| Keadaan | Terang | Gelap |
| --- | --- | --- |
| Terverifikasi / berhasil | emerald-100 / emerald-900 | emerald-400/15 / emerald-200 |
| Menunggu / perhatian | amber-100 / amber-900 | amber-400/15 / amber-200 |
| Perlu perbaikan / kritis | rose-100 / rose-900 | rose-400/15 / rose-200 |
| Informasi | brand/12 / interactive | brand/12 / interactive |

Tanpa gradien, tanpa efek menyala.

## 2. Tipografi

Inter, dibundel lokal melalui Vite (`bunny('Inter')`) sehingga tidak ada
permintaan ke CDN pihak ketiga saat halaman dibuka.

| Elemen | Utility | Mobile | Desktop |
| --- | --- | --- | --- |
| Judul halaman | `phm-page-title` | 24 px | 30 px |
| Judul bagian | `phm-section-heading` | 20 px | 22 px |
| Judul kartu | `phm-card-heading` | 18 px | 18 px |
| Isi | bawaan `body` | 16 px | 16 px |
| Label form | `phm-label` | 16 px | 16 px |
| Input form | `phm-input` | 18 px | 18 px |
| Tombol | `phm-btn` | 17 px | 17 px |
| Angka KPI | `phm-kpi` | 28 px | 34 px |
| Teks bantu | `phm-helper` | 14 px | 14 px |

`line-height` isi 1.6. Angka memakai `tabular-nums` agar kolom jumlah sejajar.
Tidak ada teks di bawah 14 px.

## 3. Jarak dan dimensi

Skala 4 px bawaan Tailwind: `4, 8, 12, 16, 20, 24, 32, 40, 48, 64`.

| Elemen | Nilai | Utility |
| --- | --- | --- |
| Navbar mobile | 64 px | `h-16` |
| Navbar desktop | 72 px | `md:h-18` |
| Padding navbar | 12–16 px → 32 px | `px-3 sm:px-4 md:px-8` |
| Input | min 56 px | `phm-input` → `min-h-[3.5rem]` |
| Tombol utama | min 52 px | `phm-btn` → `min-h-[3.25rem]` |
| Tombol ikon | 44 px → 48 px | `phm-icon-btn` → `size-11 md:size-12` |
| Ikon di tombol navbar | 24 px | `size-6` |
| Radius kartu | 14 px | `--radius-card` |
| Radius kontrol | 12 px | `--radius-control` |
| Padding kartu | 20 px → 24 px | `phm-card` → `p-5 md:p-6` |
| Jarak antar kartu | 16–24 px | `space-y-5` / `gap-4` |
| Baris drawer | min 52 px | `phm-drawer-link` |
| Lebar drawer | 88 vw, maks 320 px | `w-[88vw] max-w-[320px]` |
| Panel chat desktop | 448–480 px | `md:w-[28rem] lg:w-[30rem]` |
| Lebar form produksi | maks 720 px | `max-w-[45rem]` |

## 4. Utility komponen

Didefinisikan dengan `@utility` di `app.css`:

| Utility | Kegunaan |
| --- | --- |
| `phm-card` | permukaan kartu standar |
| `phm-card-interactive` | **hanya** untuk kartu yang benar-benar dapat diklik |
| `phm-input` / `phm-textarea` / `phm-label` | kontrol form |
| `phm-btn` + `phm-btn-primary` / `-secondary` / `-ghost` / `-danger` | tombol |
| `phm-icon-btn` | kontrol navbar ikon-saja |
| `phm-badge` | lencana status |
| `phm-drawer-link` / `phm-drawer-link-active` | baris navigasi drawer |
| `phm-scroll` | scrollbar tipis untuk panel chat dan daftar panjang |

### Komponen Blade

| Komponen | Kegunaan |
| --- | --- |
| `<x-app-layout>` | layout terautentikasi: navbar, drawer, main, Beesha FAB |
| `<x-guest-layout>` | layout login/bantuan |
| `<x-theme-toggle>` | tombol tema ikon-saja |
| `<x-page-header>` | judul + subjudul + waktu pembaruan + slot aksi |
| `<x-stat-card>` | kartu KPI dengan nada dan tautan opsional |
| `<x-progress-bar>` | batang dua lapis: terverifikasi (gelap) + dilaporkan (muda) |
| `<x-info-row>` | baris `<dt>/<dd>` untuk daftar informasi |
| `<x-empty-state>` | keadaan kosong dengan ikon, judul, penjelasan |
| `<x-demo-banner>` | pemberitahuan mode demo |
| `<x-flash-messages>` | pesan sukses/gagal |

## 5. Navbar — aturan ikon-saja

Spesifikasi §7.2 dan §44 mewajibkan kontrol navbar berupa ikon saja.

**Diterapkan:**

| Posisi | Kontrol | Ikon | Nama aksesibel |
| --- | --- | --- | --- |
| Kiri | Hamburger | `menu` | `aria-label="Buka menu navigasi"` |
| Kiri | Brand "PHM One" + judul halaman | — | teks biasa, bukan tombol |
| **Kanan** | **Pengalih tema** | `sun` / `moon` | `:aria-label="Ubah ke mode gelap/terang"` |
| **Kanan** | **Profil** | `circle-user` | `aria-label="Profil dan pengaturan akun"` |

Tidak ada teks "Menu", "Dark Mode", "Light Mode", "Profil", atau "Pengaturan"
yang terlihat di samping ikon-ikon ini. Nama aksesibel hidup di atribut.

Hal ini **diuji secara ketat**: `InterfaceContractTest` mengekstrak blok
`<header>`, membuang semua tag dan atribut, lalu menegaskan satu-satunya teks
yang tersisa adalah nama merek dan judul halaman.

Tombol ikon tetap memiliki `title` untuk tooltip, dan fokus terlihat
(`focus-visible:outline-2 outline-white`).

Aturan ikon-saja **tidak** berlaku untuk baris drawer — baris drawer memakai ikon
**dan** label teks yang terbaca, sesuai spesifikasi §08.

## 6. Tema terang/gelap

```html
<!-- di <head>, sebelum render pertama -->
<script>
  var stored = localStorage.getItem('phm-theme');
  var dark = stored === 'dark' ||
             (stored === null && matchMedia('(prefers-color-scheme: dark)').matches);
  document.documentElement.classList.toggle('dark', dark);
</script>
```

- Berjalan di `<head>` sehingga **tidak ada kedip tema** saat halaman dimuat.
- Bawaan mengikuti preferensi sistem; pilihan pengguna menimpanya.
- Disimpan di `localStorage['phm-theme']`, bertahan antar halaman dan muat ulang.
- Seluruh akses `localStorage` dibungkus `try/catch` agar aman pada mode privat
  atau saat penyimpanan diblokir — halaman tetap tampil dengan tema terang.
- Varian gelap memakai `@custom-variant dark (&:where(.dark, .dark *))`.

`<meta name="theme-color">` disediakan untuk kedua skema agar bilah browser
mobile serasi.

## 7. Interaksi dan animasi

| Elemen | Hover | Active |
| --- | --- | --- |
| Kartu yang dapat diklik | naik 3 px, border lebih terang, bayangan bertambah, 200 ms | turun 1 px, skala 0.995 |
| Tombol | perubahan warna, elevasi halus | skala 0.985 |
| Baris drawer | latar `brand/10`, teks `interactive`, 150 ms | — |
| Tombol ikon navbar | latar `--phm-navbar-hover` | — |

Aturan yang dipatuhi:

- **Kartu informasi statis tidak dianimasikan.** `phm-card-interactive`
  diterapkan hanya pada elemen `<a>`/`<button>`. Diuji oleh
  `test_only_clickable_cards_get_the_hover_lift` — halaman Tugas Saya, yang tidak
  punya kartu dapat diklik, diperiksa agar tidak memuat kelas tersebut.
- Durasi 150–220 ms; tidak ada animasi otomatis atau yang mengalihkan perhatian.
- `@media (prefers-reduced-motion: reduce)` mematikan transisi dan animasi, dan
  `motion-reduce:` menonaktifkan transform pada hover/active.
- Tidak ada fungsi yang hanya tersedia lewat hover — semua kontrol dapat disentuh
  dan dapat dicapai keyboard.

## 8. Mobile-first

Titik uji: 320, 360, 390, 430, 600, 768, 1024, 1280, 1440 px.

Prinsip yang diterapkan:

- Layout mulai dari satu kolom, `sm:`/`md:`/`lg:`/`xl:` menambah kolom.
- Target sentuh minimum 44 px pada semua kontrol.
- Tidak ada tabel bergulir ke samping — daftar dirender sebagai kartu bertumpuk,
  sehingga tidak ada overflow horizontal dan tidak ada label terpotong.
- Input angka: `inputmode="numeric"` agar papan tombol angka muncul.
- `env(safe-area-inset-bottom)` dihormati pada `body`, tombol Beesha, dan input chat.
- Drawer maksimum 88 % lebar viewport; panel chat nyaris penuh layar.
- `viewport-fit=cover` pada meta viewport.
- Dashboard pimpinan memakai grid KPI padat (`sm:grid-cols-2 xl:grid-cols-4`)
  dan bagian sekunder dua kolom di `lg:` — bukan deretan kartu desktop yang
  memanjang di ponsel.

## 9. Aksesibilitas

| Kriteria WCAG 2.2 AA | Penerapan |
| --- | --- |
| 1.4.3 Kontras teks | lihat §1; semua kombinasi lulus |
| 1.4.11 Kontras non-teks | border kontrol form `#64748B` / `#4A719A` |
| 2.1.1 Keyboard | semua kontrol adalah `<button>`/`<a>`/input asli |
| 2.4.1 Lewati blok | tautan "Lewati ke konten utama" sebagai elemen fokus pertama |
| 2.4.3 Urutan fokus | drawer memakai `x-trap.noscroll`; Escape menutup |
| 2.4.7 Fokus terlihat | `:focus-visible` global, outline 2 px + offset |
| 2.5.8 Ukuran target | minimum 44 px |
| 3.3.1 Identifikasi kesalahan | pesan `role="alert"` + `aria-invalid` + `aria-describedby` |
| 3.3.3 Saran kesalahan | pesan menyebut tindakan yang perlu dilakukan |
| 4.1.2 Nama, peran, nilai | `aria-label` pada tombol ikon, `role="dialog"` + `aria-modal` pada drawer dan panel chat |
| 4.1.3 Pesan status | `aria-live="polite"` pada area chat; `role="status"` pada pesan sukses |

Heading mengikuti hierarki: satu `<h1>` per halaman (judul halaman), `<h2>` untuk
bagian, `<h3>` untuk kartu di dalam bagian. Daftar informasi memakai
`<dl>/<dt>/<dd>`.

## 10. Bahasa antarmuka

Bahasa Indonesia sehari-hari, bukan istilah ERP.

| Dipakai | Bukan |
| --- | --- |
| Catat Hasil Produksi | Submit Production Actual |
| Jumlah Selesai | Production Quantity Input |
| Simpan Hasil | Submit |
| Menunggu Pemeriksaan | Pending Approval |
| Sisa Pekerjaan | Outstanding Quantity |
| Perlu Perbaikan | Rejected |
| Tugas Saya | My Work Orders |

Pesan kesalahan menyebut tindakan, bukan istilah teknis:

- "Masukkan jumlah hasil pekerjaan."
- "Jumlah harus lebih dari 0."
- "Masukkan jumlah dalam satuan unit utuh."
- "Pilih jenis kendala."
- "Jelaskan kendala yang dialami."
- "Ada perubahan pada penugasan ini. Silakan periksa kembali."

Tidak ada pesan berorientasi pengembang yang ditampilkan ke pengguna; service
domain melempar `ProductionReportException` yang sudah membawa pesan siap-pakai.

## 11. Keadaan yang diimplementasikan

Sesuai spesifikasi §35, tidak ada fitur besar yang hanya punya keadaan ideal:

default, hover, focus, active, disabled, loading (`wire:loading`), keadaan kosong
(`<x-empty-state>`), kesalahan validasi, kesalahan jaringan (Livewire menampilkan
dialog kegagalan bawaannya), berhasil, menunggu verifikasi, terverifikasi, perlu
perbaikan, tidak ada penugasan, sesi berakhir (middleware `active` → pengalihan
dengan pesan), data tidak tersedia (cabang `count === 0` pada jawaban Beesha).
