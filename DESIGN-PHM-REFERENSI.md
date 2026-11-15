# Design Reference — Dasbor "Jarwo" PT Putra Hatim Mandiri

Acuan **frontend only** (warna, tipografi, bentuk, komponen visual) hasil ekstraksi langsung dari
`https://phmjarwo-dasbor.netlify.app/` → `app.css` (264 KB, 1.033 rule) + computed style di browser.

> Dokumen ini **tidak** membahas fitur/alur sistem PHM. Isinya murni bahasa visual, untuk
> diterapkan ulang pada sistem baru yang kita bangun (Next.js).

---

## 1. Karakter Desain (ringkasan)

| Aspek | Temuan |
|---|---|
| Mood | Industrial–teknis, tenang, gelap sebagai default. Bukan playful, bukan korporat-biru-kaku. |
| Warna inti | **Navy kehijauan (teal-navy)** sebagai dasar + **sian/cyan** sebagai satu-satunya aksen merek |
| Default tema | **Gelap** (`:root`), tema terang sebagai alternatif (`html[data-tema="terang"]`) |
| Tipografi | 100% **system font stack** — tanpa webfont sama sekali (nol request font) |
| Bentuk | Radius kecil–sedang (6–12 px), sudut kontrol standar **8 px** |
| Kedalaman | Shadow sangat tipis + **glassmorphism** (backdrop-blur 1–2.5 px) |
| Kepadatan | **Padat/dense** — tinggi kontrol 30 px, font dasar 15 px, gap 4–12 px |
| Aksesibilitas | Warna aksen dipilih berdasarkan **pengukuran kontras**, bukan selera (lihat §2.4) |

**Prinsip yang layak diwarisi:** satu warna merek saja (sian), warna lain hanya untuk *status*.
Jangan menambah warna dekoratif kedua.

---

## 2. Color Palette

### 2.1 Token Semantik — Tema Gelap (DEFAULT)

| Token | Nilai | Fungsi |
|---|---|---|
| `--latar` | `#071620` | Background halaman (navy sangat gelap) |
| `--kartu` | `#0c1c26` | Permukaan kartu/panel |
| `--navy` | `#04101a` | Permukaan paling dalam (header, bar, well) |
| `--teks` | `#e6f1f5` | Teks utama |
| `--redup` | `#9db4c0` | Teks sekunder / label / caption |
| `--garis` | `rgba(255,255,255,.14)` | Border & pemisah |
| `--indigo` | `#38bdf8` | Warna merek / aksi utama |
| `--indigo-muda` | `rgba(56,189,248,.18)` | Latar aksen lembut (highlight row, chip aktif) |
| `--sian` | `#38bdf8` | Aksen / garis fokus |
| `--sian-redup` | `rgba(56,189,248,.35)` | Aksen transparan (ring, inset) |
| `--kacaAlpha` | `.8` | Opasitas permukaan "kaca" |
| `--jarwo-nada` | `#a6fcfd` | Aksen tertinggi (judul/brand highlight) |

Warna teks di atas tombol primer gelap: `#04212b`.

### 2.2 Token Semantik — Tema Terang

| Token | Nilai | Fungsi |
|---|---|---|
| `--latar` | `#f5f5f7` | Background halaman (abu netral, bukan putih) |
| `--kartu` | `#ffffff` | Permukaan kartu |
| `--navy` | `#072a38` | Header gelap / kontras |
| `--teks` | `#1f2937` | Teks utama |
| `--redup` | `#6b7280` | Teks sekunder |
| `--garis` | `#e5e7eb` | Border |
| `--indigo` | `#0d6076` | Warna merek / aksi utama |
| `--indigo-muda` | `#cffafe` | Latar aksen lembut |
| `--sian` | `#06b6d4` | Aksen |
| `--sian-redup` | `rgba(6,182,212,.38)` | Aksen transparan |
| `--kacaAlpha` | `.82` | Opasitas permukaan kaca |
| `--jarwo-nada` | `#0d6076` | Aksen brand |

### 2.3 Warna Status (identik di kedua tema — sengaja)

| Token | Nilai | Makna |
|---|---|---|
| `--aman` | `#15803d` | OK / hijau |
| `--waspada` | `#b45309` | Perhatian / amber |
| `--kritis` | `#b91c1c` | Kritis / merah |
| `--merah` | `#b91c1c` | Destruktif (tombol hapus) |
| `--merah-tua` | `#991b1b` | Destruktif hover/pressed |

Tingkat urgensi bertingkat yang dipakai: `#7f1d1d` (paling tinggi) → `#b91c1c` → `#b45309`.
Netral/non-aktif: `#6b7280`.

**Aturan penting:** status tidak pernah memakai warna merek. Sian = merek, bukan keadaan.

### 2.4 Catatan Kontras (dari komentar di source code)

Pengembang PHM mencatat secara eksplisit bahwa `--indigo` terang **`#0e7490` ditolak** karena
teks putih di atasnya hanya mencapai **4,4:1** dan teks aksen di atas panel bertumpuk **3,4:1** —
keduanya di bawah ambang WCAG AA. Nilainya digelapkan menjadi **`#0d6076`** sampai mencapai
**5,5:1 dan 4,5:1**.

➡️ **Untuk sistem kita:** pakai `#0d6076` (bukan `#0e7490`) untuk aksi utama di tema terang.
Ukur ulang setiap kali mengganti aksen.

### 2.5 Palet Pendukung (abu & utilitas)

`#111827` · `#1f2937` · `#374151` · `#4b5563` · `#6b7280` · `#9ca3af` · `#e5e7eb` · `#f8fafc`
Slate: `#475569` · `#64748b` · `#94a3b8`
Latar status lembut: `#fee2e2` / `#fef2f2` (merah), `#fef3c7` / `#fff3bf` (amber), `#dcfce7` (hijau)

### 2.6 Aksen Visual Khusus (gelombang/indikator)

| Token | Nilai |
|---|---|
| `--gel-tenang` | `#0891b2` |
| `--gel-sedang` | `#38bdf8` |
| `--gel-nyala` | `#f0abfc` |

Dipakai pada gradient animasi:
`linear-gradient(90deg, rgba(8,145,178,.45) 0%, var(--gel-tenang) 34%, var(--gel-sedang) 66%, var(--gel-nyala) 92%)`

Magenta `#f0abfc` **hanya** muncul sebagai puncak intensitas — bukan warna UI umum.

---

## 3. Tipografi

### 3.1 Font Stack

```css
/* Teks */
font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;

/* Angka/kode/monospace */
font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
```

**Tidak ada webfont.** Tidak ada Google Fonts, tidak ada `@font-face`. Ini keputusan performa yang
sebaiknya kita tiru — kalau mau karakter lebih kuat, cukup ganti satu font display untuk judul.

### 3.2 Skala Ukuran

| Ukuran | Pemakaian |
|---|---|
| `9px` · `10px` · `10.5px` | Micro-label, satuan, footnote padat |
| `11px` · `11.5px` | Caption, keterangan dalam kartu |
| `12px` · `12.5px` | Label tabel, chip, badge |
| `13px` | Judul kartu kecil, label form |
| **`15px`** | **Body default** |
| `14px` · `16px` · `17px` | Teks sekunder & subjudul |
| `18px` · `19px` · `20px` | Judul halaman (h1 = 20px) |
| `21px` · `22px` · `24px` · `26px` | Judul besar / angka KPI |
| `2.6rem` | Angka hero |
| `clamp(11.5px, 1.55vmin, 16.1px)` | Teks responsif pada layar TV/tablet |

**Line-height body: `1.45`** (21,75 px pada 15 px).

### 3.3 Bobot

`400` (body) · `500` · `600` (judul kartu, header tabel) · `700` (judul halaman) · `800` (angka hero)

h1 = 20px/700 · h2 = 16px/700 (berwarna aksen `--indigo`) · label = 13px/400 warna `--redup`

### 3.4 Letter-spacing

| Nilai | Pemakaian |
|---|---|
| `-.02em` | Judul besar / angka (dirapatkan) |
| `0` – `.01em` | Body |
| `.02em` – `.04em` | Label kecil |
| `.10em` · `.12em` · `.14em` · `.18em` | **UPPERCASE micro-label** (satu-satunya `text-transform` yang dipakai) |

Pola khas: label kategori kecil ditulis UPPERCASE + letter-spacing lebar + warna `--redup`.

---

## 4. Bentuk & Kedalaman

### 4.1 Border Radius

| Nilai | Pemakaian |
|---|---|
| `2px` – `5px` | Elemen mikro, indikator |
| `6px` | Inset / area scroll dalam kartu |
| **`8px`** | **Standar kontrol** (`--sudut-kendali`) — tombol, input, select |
| `9px` · `10px` | Chip, panel kecil |
| `12px` · `14px` | Kartu / panel |
| `16px` · `22px` | Kontainer besar, sheet |
| `999px` | Pil / badge bulat penuh |
| `50%` | Avatar, titik status |

### 4.2 Shadow

```css
/* Kartu — sangat tipis, dua lapis */
box-shadow: 0 1px 2px rgba(0,0,0,.04), 0 4px 14px rgba(0,0,0,.03);

/* Elevasi sedang (dropdown, popover) */
box-shadow: 0 8px 24px rgba(15,23,42,.18);
box-shadow: 0 8px 24px rgba(15,23,42,.22);

/* Modal / overlay (tema terang) */
box-shadow: 0 20px 60px rgba(0,0,0,.35);

/* Modal / overlay (tema gelap) */
box-shadow: 0 18px 60px rgba(0,0,0,.55);

/* Ring fokus & penanda */
box-shadow: inset 0 0 0 2px var(--indigo);
box-shadow: inset 0 0 0 2px var(--sian);
box-shadow: inset 3px 0 0 var(--sian);   /* penanda baris aktif di sisi kiri */
```

Catatan: kedalaman dibangun dari **border tipis + blur**, bukan shadow tebal.

### 4.3 Border

- Lebar dominan **1px**.
- Tema gelap: `1px solid rgba(255,255,255,.14)`
- Tema terang: `1px solid #e5e7eb`; input: `1px solid #cbd5e1`
- Aksen: `1px solid var(--indigo)`

### 4.4 Glassmorphism

```css
background: rgba(255,255,255, var(--kacaAlpha));
backdrop-filter: blur(1px);        /* juga: .75px, 1.75px, 2.5px */
-webkit-backdrop-filter: blur(1px);
```

Blur sangat halus (≤2,5 px) — efeknya terasa tanpa mengaburkan data. `--kacaAlpha` berubah per tema
(`.82` terang / `.8` gelap), jadi satu rule bekerja di kedua tema.

---

## 5. Spacing & Layout

| Token | Nilai | Fungsi |
|---|---|---|
| `--tinggi-kendali` | `30px` | Tinggi baku tombol/input kecil |
| `--sudut-kendali` | `8px` | Radius baku kontrol |
| `--bilah-bawah` | `10px` | Jarak bar bawah ke tepi layar |
| `--pk-teks` | `12.5px` | Ukuran teks panel kontrol |

- **Gap:** `1px, 2px, 3px, 4px, 5px, 6px, 7px, 8px, 10px, 12px` → skala dasar **2 px**, bukan 4 px.
- **Padding kontrol:** tombol `9px 14px`, input `9px 10px`, tombol ikon `4px 8px`.
- Kepadatan tinggi: ini dasbor operasional, bukan landing page.

> Komentar di source: `--bilah-bawah` ditulis satu kali agar bar bawah dan kedua panel samping
> berakhir di garis yang sama. **Pelajaran: satu angka untuk satu makna.**

---

## 6. Spesifikasi Komponen

### 6.1 Tombol

| Varian | Tema Terang | Tema Gelap |
|---|---|---|
| **Primer** | bg `var(--indigo)` `#0d6076`, teks `#fff` | bg `rgba(56,189,248,.92)`, teks `#04212b` |
| **Sekunder** | bg `rgba(255,255,255,.85)` + `blur(1px)`, border `--garis` | sama, dengan `--kacaAlpha` |
| **Destruktif** | bg `var(--merah)` `#b91c1c`, teks `#fff` | sama |
| **Ikon** | bg `transparent`, font-size `18px`, padding `4px 8px` | sama |

Baku: `border-radius: 8px` · `padding: 9px 14px` · `font: inherit` (15 px) · `border: 0`
Catatan: di tema gelap tombol primer memakai **teks gelap di atas sian terang** — pola yang tepat
untuk menjaga kontras.

### 6.2 Input / Select / Textarea

```css
input, select, textarea {
  font: inherit;
  padding: 9px 10px;
  border: 1px solid #cbd5e1;      /* gelap: rgba(255,255,255,.14) */
  border-radius: 8px;
  width: 100%;
  background: #fff;                /* gelap: rgba(12,28,38,.8) */
}
input::placeholder { color: var(--redup); opacity: 1; }
```

Fokus: ring inset 2 px warna `--indigo` / `--sian` (bukan outline default browser).

### 6.3 Kartu / Tile

```css
background: var(--kartu);
border: 1px solid var(--garis);
border-radius: 12px;
box-shadow: 0 1px 2px rgba(0,0,0,.04), 0 4px 14px rgba(0,0,0,.03);
```

- Judul kartu: `13px / 600`
- Keterangan kanan: `11px`, warna `--redup`, `display:flex; gap:8px`
- Varian `polos`: tanpa background, border, shadow, padding — untuk kartu yang hanya pembungkus.

### 6.4 Badge / Pil Status

- `border-radius: 999px`, teks `11–12.5px`, padding ±`2px 8px` / `2px 10px`
- Warna solid dari token status: `--kritis` / `--waspada` / `--aman` / `#6b7280` (netral)
- Tingkat urgensi: `#7f1d1d` → `#b91c1c` → `#b45309`
- Teks di dalam badge: putih, sering UPPERCASE + letter-spacing lebar

### 6.5 Tabel

- Header (`th`): `600`, min-width `120px` untuk header baris
- Kolom angka: `white-space: nowrap` + font monospace → angka sejajar dan tidak terpotong
- Baris aktif ditandai `inset 3px 0 0 var(--sian)` di sisi kiri, bukan background mencolok
- Highlight baris: `var(--indigo-muda)`

### 6.6 Overlay / Modal

- Tema terang: `box-shadow: 0 20px 60px rgba(0,0,0,.35)`
- Tema gelap: `box-shadow: 0 18px 60px rgba(0,0,0,.55)`
- Radius `16px`–`22px`
- Fade bawah: `linear-gradient(to bottom, transparent, var(--pudar, var(--latar)))`

---

## 7. Motion

| Jenis | Nilai |
|---|---|
| Hover/background | `background .15s` · `background-color .18s ease` |
| Fade | `opacity .12s ease` · `opacity .2s ease` · `opacity .24s ease` |
| Transform ringan | `transform .15s ease` · `transform .26s cubic-bezier(.22,.61,.36,1)` |
| Panel masuk/keluar | `transform .36s cubic-bezier(.22,.61,.36,1), opacity .36s ease` |
| Progress bar | `width .06s linear` |

Easing khas: **`cubic-bezier(.22,.61,.36,1)`** (ease-out tegas) untuk panel/sheet.

> Catatan dari source: transisi CSS **berhenti di tab yang tidak aktif**. Untuk indikator yang harus
> selalu akurat (sorotan real-time), mereka sengaja **tidak** memakai transisi. Layak ditiru untuk
> indikator live.

---

## 8. Mekanisme Theming

```html
<html data-tema="gelap">   <!-- default -->
<html data-tema="terang">  <!-- alternatif -->
```

- Semua token didefinisikan di `:root`, di-override oleh `html[data-tema="terang"]`.
- `<meta name="theme-color" content="#071620">` → warna UI browser mobile ikut gelap.
- PWA-ready: `apple-mobile-web-app-capable`, `viewport-fit=cover`.
- **Tidak** memakai `prefers-color-scheme` — tema dipilih pengguna, bukan sistem.

---

## 9. Implementasi untuk Sistem Kita (Next.js)

### 9.1 `app/globals.css`

```css
:root {
  /* permukaan */
  --latar:#071620; --kartu:#0c1c26; --navy:#04101a;
  /* teks */
  --teks:#e6f1f5; --redup:#9db4c0;
  /* garis */
  --garis:rgba(255,255,255,.14);
  /* merek */
  --brand:#38bdf8; --brand-muda:rgba(56,189,248,.18);
  --brand-redup:rgba(56,189,248,.35); --brand-teks:#04212b;
  /* status */
  --aman:#15803d; --waspada:#b45309; --kritis:#b91c1c; --netral:#6b7280;
  /* bentuk */
  --r-kontrol:8px; --r-kartu:12px; --r-pil:999px;
  --h-kontrol:30px; --kaca:.8;
  /* bayangan */
  --sh-kartu:0 1px 2px rgba(0,0,0,.04), 0 4px 14px rgba(0,0,0,.03);
  --sh-pop:0 8px 24px rgba(15,23,42,.18);
  --sh-modal:0 18px 60px rgba(0,0,0,.55);
}

html[data-tema="terang"] {
  --latar:#f5f5f7; --kartu:#fff; --navy:#072a38;
  --teks:#1f2937; --redup:#6b7280; --garis:#e5e7eb;
  --brand:#0d6076; --brand-muda:#cffafe;
  --brand-redup:rgba(6,182,212,.38); --brand-teks:#ffffff;
  --kaca:.82;
  --sh-modal:0 20px 60px rgba(0,0,0,.35);
}

html, body {
  background: var(--latar);
  color: var(--teks);
  font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  font-size: 15px;
  line-height: 1.45;
}
```

> Catatan: pada PHM, nama token `--indigo` dipertahankan meski nilainya sudah sian, agar ratusan
> pemakaian tidak perlu disentuh. **Untuk sistem baru, pakai nama netral (`--brand`) sejak awal**
> supaya tidak mewarisi kebingungan yang sama.

### 9.2 `tailwind.config.ts`

```ts
export default {
  darkMode: ['class', '[data-tema="gelap"]'],
  theme: {
    extend: {
      colors: {
        latar:  'var(--latar)',
        kartu:  'var(--kartu)',
        navy:   'var(--navy)',
        teks:   'var(--teks)',
        redup:  'var(--redup)',
        garis:  'var(--garis)',
        brand:  { DEFAULT:'var(--brand)', muda:'var(--brand-muda)', teks:'var(--brand-teks)' },
        aman:'#15803d', waspada:'#b45309', kritis:'#b91c1c',
      },
      borderRadius: { kontrol:'8px', kartu:'12px', pil:'999px' },
      boxShadow: { kartu:'var(--sh-kartu)', pop:'var(--sh-pop)', modal:'var(--sh-modal)' },
      fontSize: {
        micro:['10px',{lineHeight:'1.3'}], mini:['11px',{lineHeight:'1.35'}],
        label:['12.5px',{lineHeight:'1.4'}], kartu:['13px',{lineHeight:'1.4'}],
        body:['15px',{lineHeight:'1.45'}], judul:['20px',{lineHeight:'1.25'}],
        kpi:['26px',{lineHeight:'1.1'}],
      },
      transitionTimingFunction: { panel:'cubic-bezier(.22,.61,.36,1)' },
    },
  },
}
```

### 9.3 Checklist Adopsi

- [ ] Tema gelap sebagai default, toggle via `data-tema` di `<html>` (hindari flash: set atribut di script inline sebelum paint)
- [ ] `<meta name="theme-color">` mengikuti tema aktif
- [ ] Satu warna merek saja; warna lain hanya status
- [ ] Verifikasi kontras AA setiap pasangan teks/latar aksen sebelum dikunci
- [ ] Angka selalu monospace + `nowrap` di tabel
- [ ] Ring fokus inset 2 px, jangan hapus outline tanpa pengganti
- [ ] Skala spacing 2 px, tinggi kontrol 30 px (dense)
- [ ] Nol webfont; kalau perlu font display, batasi untuk judul saja
- [ ] Hormati `prefers-reduced-motion` (PHM memakai banyak animasi — kita sebaiknya menambah guard ini)

### 9.4 Yang Sebaiknya **Tidak** Ditiru

| Hal | Alasan |
|---|---|
| Nama token bahasa Indonesia campur (`--indigo` bernilai sian) | Menyesatkan; pakai nama semantik |
| Satu file `app.css` 264 KB / 1.033 rule | Sulit dirawat; pakai CSS Modules atau Tailwind per komponen |
| Kelas spesifik-fitur (`.pita-kirim`, `.kotak-grafik`, `.kpi-pbi`) | Terikat ke fitur PHM; sistem kita punya domain sendiri |
| Gradient magenta `#f0abfc` | Khusus indikator gelombang PHM; tidak ada padanannya di sistem kita |
| Ukuran font 9px–10.5px | Di bawah ambang nyaman baca; naikkan ke minimum 11px |

---

## 10. Ringkasan Satu Halaman

```
MEREK      sian  #38bdf8 (gelap) / #0d6076 (terang)
DASAR      #071620 → #0c1c26 → #04101a   |   #f5f5f7 → #ffffff
TEKS       #e6f1f5 / #9db4c0             |   #1f2937 / #6b7280
STATUS     #15803d · #b45309 · #b91c1c · #6b7280
FONT       system-ui · 15px · 1.45 · bobot 400/600/700
RADIUS     kontrol 8 · kartu 12 · pil 999
SHADOW     0 1px 2px rgba(0,0,0,.04), 0 4px 14px rgba(0,0,0,.03)
GERAK      .15s–.26s · cubic-bezier(.22,.61,.36,1)
```

---

*Sumber: ekstraksi langsung `app.css` + computed style dari https://phmjarwo-dasbor.netlify.app/ (15 Nov 2026).*
