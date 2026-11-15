# Alur Pengguna

## 1. Masuk ke aplikasi (semua peran)

```
/masuk
  │
  ├─ Langkah 1  [Nomor Induk Karyawan] → Lanjutkan
  │     ├─ tidak ditemukan / tidak aktif → pesan kesalahan, tetap di langkah 1
  │     └─ ditemukan → langkah 2
  │
  ├─ Langkah 2  nama + jabatan tampil → [Kata Sandi] → Masuk
  │     ├─ salah → pesan kesalahan (maks 5 percobaan / menit)
  │     └─ benar → regenerasi sesi → ruang kerja sesuai peran
  │
  └─ Mode demo: tombol Masuk Cepat → langsung ke ruang kerja
```

Tujuan akhir per peran:

| Peran | Mendarat di |
| --- | --- |
| Operator Produksi | `/operator/tugas` |
| Supervisor / QC | `/supervisor/pemeriksaan` |
| Admin Pesanan | `/pesanan` |
| PPIC | `/perencanaan` |
| Petugas Gudang | `/pengiriman` |
| Driver | `/driver/pengiriman` |
| Pimpinan | `/dashboard` |
| Administrator Sistem | `/sistem/pengguna` |

## 2. Operator — mencatat hasil produksi

Alur inti aplikasi.

```
/operator/tugas  "Tugas Saya"
  │
  ├─ Peringatan laporan perlu diperbaiki (bila ada) → tombol Catat Ulang Hasil
  │
  ├─ Kartu pekerjaan
  │     Produk · Work order · Tahap · Stasiun · Shift
  │     ┌─────────┬──────────────────┬───────────────┐
  │     │ Target  │ Sudah dilaporkan │ Sisa pekerjaan│
  │     │   500   │       320        │      180      │
  │     └─────────┴──────────────────┴───────────────┘
  │     Batang progres dua lapis (terverifikasi + dilaporkan)
  │     "320 pcs menunggu pemeriksaan atasan" (bila ada)
  │
  │     [ Catat Hasil Kerja ]   ← satu aksi utama
  │
  ├─ Hasil Saya Hari Ini: dilaporkan / terverifikasi / menunggu
  ├─ Laporan Terakhir Saya (4 terbaru, dengan lencana status)
  └─ Pekerjaan Berikutnya (3 mendatang)

           │ tekan Catat Hasil Kerja
           ▼
/operator/catat-hasil/{assignment}
  │
  ├─ (bila >1 penugasan aktif dan belum dipilih) → pemilih pekerjaan
  │
  ├─ Kartu 1  Informasi Pekerjaan — SELURUHNYA baca-saja
  │     Nama produk · Nomor pekerjaan · Nama petugas · Tahap
  │     Target · Sudah tercatat · Sisa target
  │     [Ganti pekerjaan] (bila ada lebih dari satu)
  │
  ├─ Kartu 2  Hasil Pekerjaan *
  │     "Berapa unit yang selesai pada pencatatan ini?"
  │     [        35        ] pcs      ← SATU-SATUNYA angka yang diketik
  │     "Masukkan tambahan unit yang selesai, bukan jumlah keseluruhan."
  │
  ├─ Kartu 3  Kendala Pekerjaan
  │     ( ) Tidak ada      ( ) Ada kendala
  │       └─ bila "Ada kendala":
  │            Jenis Kendala *  ( ) Mesin/Peralatan ( ) Bahan/Material
  │                             ( ) Kualitas Produk ( ) Lainnya
  │            Jelaskan Kendala *  [ textarea ]
  │
  ├─ Kartu 4  Keterangan Tambahan (opsional)
  │
  └─ [ Periksa & Simpan Hasil ]        Batal
           │
           ├─ validasi gagal → tetap di form, nilai dipertahankan,
           │                   pesan di field yang bersangkutan
           │
           ▼
      Periksa Hasil Pekerjaan          ← BELUM disimpan
        Produk: Knalpot Standar Motor A
        Jumlah selesai: 35 pcs
        Kendala: Tidak ada
        "Apakah hasil pekerjaan sudah sesuai?"
        [ Perbaiki ]   [ Ya, Simpan Hasil ]
           │
           ├─ Perbaiki → kembali ke form
           │
           ▼ Ya, Simpan Hasil
      ProductionReportService::submit()
        izin → idempotensi → transaksi → batas sisa target → simpan
        → audit → commit
           │
           ├─ melebihi sisa target → kembali ke form dengan pesan yang
           │   menyebut angka sisa dan angka yang dimasukkan
           │
           ▼ berhasil
      Hasil Berhasil Dicatat
        "35 pcs telah masuk ke laporan pekerjaan Anda."
        Status: Menunggu Pemeriksaan
        Nomor laporan LP-20261115-0004 · 15 Nov 2026, 16:42
        [ Kembali ke Tugas Saya ] [ Catat Lagi ] [ Lihat Riwayat ]
```

### Bila operator salah input

Laporan yang tersimpan tidak dapat dihapus sendiri — riwayat pelaporan tetap
utuh. Alurnya:

```
Operator memberi tahu atasan
  → Supervisor menandai laporan "Perlu Perbaikan" dengan alasan
  → Laporan keluar dari total dilaporkan, tetapi barisnya tetap ada
  → Operator melihat peringatan di Tugas Saya beserta catatan atasan
  → Operator menekan "Catat Ulang Hasil" dan memasukkan angka yang benar
```

## 3. Supervisor — memeriksa laporan

```
/supervisor/pemeriksaan  "Pemeriksaan Hari Ini"   (memperbarui diri tiap 5 detik)
  │
  ├─ Tiga KPI: Dilaporkan · Terverifikasi · Menunggu pemeriksaan
  │
  ├─ Menunggu Pemeriksaan (n)
  │    Kartu laporan:
  │      Nomor laporan · Nama operator · Produk · Work order · Tahap
  │      Jumlah besar di kanan
  │      Waktu pencatatan · Kendala · Penjelasan · Keterangan
  │      Progres penugasan: 355 / 500 pcs
  │      ⚠ penanda khusus bila laporan adalah pengecualian
  │
  │      [ Verifikasi ]        [ Perlu Perbaikan ]
  │          │                      │
  │          │                      ▼
  │          │                 textarea "Apa yang perlu diperbaiki?" *
  │          │                 [ Kirim Permintaan Perbaikan ] [ Batal ]
  │          │                      │
  │          ▼                      ▼
  │    review record + status laporan + audit, dalam satu transaksi
  │          │
  │          └─ sudah diperiksa pihak lain → pesan "Laporan ini sudah
  │             diperiksa (status: …). Muat ulang daftar pemeriksaan."
  │
  ├─ Menunggu Dicatat Ulang (laporan perlu perbaikan + catatan)
  └─ Baru Diverifikasi Hari Ini
```

Aturan yang ditegakkan: supervisor **tidak dapat** memverifikasi laporannya
sendiri, dan satu laporan **tidak dapat** diperiksa dua kali.

## 4. Pimpinan — memantau operasional

```
/dashboard  "Ringkasan Perusahaan"   (memperbarui diri tiap 5 detik)
  │
  ├─ Empat KPI utama (semuanya dapat ditelusuri)
  │    Pesanan Aktif │ Produksi Dilaporkan │ Produksi Terverifikasi │ Perlu Perhatian
  │
  ├─ Catatan transparansi data
  │    "Dilaporkan adalah jumlah yang dicatat operator, termasuk N pcs dari
  │     M laporan yang belum diperiksa. Terverifikasi hanya mencakup laporan
  │     yang sudah disetujui supervisor. Angka diperbarui otomatis setiap 5 detik."
  │
  ├─ Progres Produksi Hari Ini — batang dua lapis per penugasan
  ├─ Pengiriman · Pesanan Mendekati Tenggat
  ├─ Menunggu Verifikasi · Peringatan
  └─ Aktivitas Terbaru (dari audit trail)

  → /dashboard/produksi    perbandingan hari ini vs kemarin, progres per penugasan
  → /dashboard/pengiriman  KPI pengiriman + daftar lengkap
  → /dashboard/peringatan  peringatan terurut dari paling penting
```

## 5. Admin Pesanan — mencatat pesanan mitra

```
/pesanan  "Pesanan Mitra"
  │
  ├─ Pencarian (nomor pesanan / PO / mitra / barang) + filter status
  ├─ Kartu pesanan yang dapat diklik:
  │    Mitra · Nomor pesanan · PO · Barang
  │    Jumlah │ Sudah dikirim │ Sisa kirim │ Target kirim
  │    ⚠ "Melewati target pengiriman" bila terlambat
  │
  └─ [ Tambah Pesanan ]
        │
        ▼
     /pesanan/tambah
        Nama Mitra *        → memilih menampilkan kontak + alamat otomatis
        Nomor PO *
        Nama Barang *       → memilih menampilkan deskripsi + satuan
        Jumlah Pesanan *
        Target Pengiriman *
        Catatan (opsional)
        │
        └─ [ Periksa & Simpan Pesanan ] → ringkasan → simpan
              │
              ├─ PO ganda untuk mitra yang sama → ditolak dengan pesan jelas
              └─ berhasil → nomor pesanan dibuat; langsung terlihat oleh PPIC
```

## 6. PPIC — merencanakan produksi

```
/perencanaan  "Rencana Produksi"
  ├─ KPI: pesanan perlu direncanakan · work order terbuka · penugasan hari ini
  │        · terverifikasi hari ini
  ├─ Pesanan Yang Perlu Direncanakan (jumlah yang belum punya work order)
  └─ Target Harian Hari Ini (progres per operator)

/perencanaan/work-order
  └─ [ Buat Work Order ]
       Pesanan Mitra (opsional) → mengisi produk dan sisa jumlah otomatis
       Produk * · Target Produksi * · Tanggal Rencana * · Catatan
       → total work order tidak boleh melebihi jumlah pesanan

/perencanaan/penugasan
  └─ [ Buat Penugasan ]
       Work Order *   → mengisi sisa yang belum ditugaskan + tanggal otomatis
       Karyawan *     → hanya karyawan yang berizin mencatat produksi
       Jumlah Ditugaskan *  → tidak boleh melebihi sisa work order
       Tanggal * · Shift · Tahap · Stasiun · Catatan
       → setelah disimpan, langsung muncul di "Tugas Saya" operator
```

## 7. Petugas Gudang — mencatat pengiriman

```
/pengiriman  "Pengiriman Hari Ini"
  ├─ KPI: dikirim hari ini · siap dikirim · dalam perjalanan · bermasalah
  ├─ Barang Siap Dikirim
  │    "Hanya produksi yang sudah diverifikasi supervisor dihitung
  │     sebagai barang siap kirim."
  └─ Pengiriman Berjalan (bermasalah diurutkan paling atas)

/pengiriman/catat
  ├─ (tidak ada stok terverifikasi → keadaan kosong yang menjelaskan sebabnya)
  │
  ├─ Pesanan *   → konteks baca-saja muncul:
  │                 mitra, barang, dipesan, siap dikirim, sudah dikirim,
  │                 sisa kirim, tujuan
  │                 ⚠ peringatan bila siap kirim = 0
  ├─ Jumlah Dikirim *
  ├─ Nomor Surat Jalan (dibuat otomatis bila kosong)
  ├─ Driver · Catatan
  │
  └─ [ Periksa & Simpan Pengiriman ] → ringkasan → simpan
        ├─ melebihi sisa pesanan → ditolak
        ├─ melebihi stok terverifikasi → ditolak dengan penjelasan
        └─ berhasil → status "Disiapkan", driver mengambil alih
```

## 8. Driver — memperbarui status perjalanan

Mobile-first; tujuan ditampilkan paling atas karena itu yang paling dibutuhkan.

```
/driver/pengiriman  "Pengiriman Saya"
  │
  └─ Kartu pengiriman
       📍 Nama mitra
          Alamat tujuan
       ┌────────────────────────────────────────┐
       │ Nomor pengiriman                        │
       │ Nama barang                             │
       │ 150 pcs · surat jalan SRT-2026-0003     │  [Dalam Perjalanan]
       └────────────────────────────────────────┘
       Catatan dari gudang (bila ada)

       Tombol selebar layar, hanya transisi yang valid:
         disiapkan  → [ Mulai Pengiriman ] [ Laporkan Kendala ]
         dikirim    → [ Sudah Tiba ]       [ Laporkan Kendala ]
         tiba       → [ Barang Diterima ]  [ Laporkan Kendala ]

       Barang Diterima → wajib "Nama penerima barang" + catatan opsional
       Laporkan Kendala → wajib penjelasan kendala

       ▸ Riwayat perjalanan (dapat dibuka)
           Disiapkan → Dalam Perjalanan → Sudah Tiba → Barang Diterima
           dengan waktu, pelaku, nama penerima, catatan
```

Status yang sudah "Barang Diterima" tidak memiliki transisi lanjutan. Status
yang tidak valid untuk keadaan saat ini **tidak ditampilkan** dan juga ditolak
server bila dicoba.

## 9. Administrator Sistem

```
/sistem/pengguna   daftar karyawan, peran, status, terakhir masuk
                   (baca-saja di mode demo, dinyatakan di halaman)
/sistem/peran      matriks peran × izin, jumlah karyawan per peran
/sistem/audit      seluruh kejadian audit, dapat difilter per jenis aksi
/sistem/status     jumlah data, lingkungan, mekanisme pembaruan,
                   konfigurasi Beesha, tanda konfigurasi,
                   dan daftar batasan yang diketahui
```

## 10. Beesha AI (semua peran)

```
Halaman mana pun (terautentikasi)
  │
  └─ [🤖 Tanya Beesha]  ← tetap di kanan bawah, tidak hilang saat pindah halaman
        │ klik
        ▼
     Panel chat (drawer kanan di desktop, nyaris penuh layar di mobile)
       Header: Beesha · "Asisten demo · menjawab dari database PHM One"
               [🗑 hapus percakapan] [✕ tutup]
       Banner: penjelasan keterbatasan asisten demo
       │
       ├─ Area percakapan (aria-live)
       │    Pesan pengguna di kanan, jawaban Beesha di kiri
       │    Jawaban yang memakai data diberi label:
       │      "Dari data PHM One (getMyAssignments)"
       │
       ├─ Pertanyaan cepat (tertutup sendiri setelah pertanyaan pertama)
       │    Hanya menampilkan pertanyaan yang boleh dijawab untuk peran ini
       │
       └─ [ textarea ]  [➤ kirim]   (Enter mengirim)
```

Contoh per peran:

| Peran | Pertanyaan | Jawaban |
| --- | --- | --- |
| Operator | "Berapa target produksi saya hari ini?" | Produk, target 500, dilaporkan 320, terverifikasi 320, sisa 180 pcs |
| Operator | "Bagaimana cara mencatat hasil produksi?" | Lima langkah, tanpa menyentuh data |
| Supervisor | "Laporan mana yang belum diperiksa?" | Jumlah laporan, total unit, daftar per operator |
| Admin | "Pesanan mana yang mendekati tenggat?" | Pesanan 7 hari ke depan, menandai yang terlambat |
| Driver | "Pengiriman apa yang harus saya selesaikan hari ini?" | Pengiriman miliknya, tujuan, tindakan berikutnya |
| Pimpinan | "Berapa produksi yang dilaporkan dan diverifikasi hari ini?" | KPI lengkap dengan catatan perbedaan dilaporkan vs terverifikasi |

Bila pertanyaan di luar izin peran, Beesha mengatakan terus terang bahwa data
tersebut di luar akses — **tidak menebak**. Bila pertanyaan tidak dipahami,
Beesha mengatakan demikian dan menampilkan daftar kemampuannya.
