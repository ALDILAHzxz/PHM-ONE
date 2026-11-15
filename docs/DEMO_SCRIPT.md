# Skrip Demonstrasi PHM One

Durasi: ±15 menit. Ditulis untuk presentasi di depan pemangku kepentingan PHM.

## 0. Persiapan (sebelum demo)

```bash
cd c:/xampp/htdocs/PHM-ONE
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Buka dua jendela browser:

- **Jendela A** — akan dipakai sebagai **Pimpinan**
- **Jendela B** — akan dipakai sebagai **Operator** lalu **Supervisor**

Gunakan jendela penyamaran/profil berbeda agar sesi tidak saling menimpa.

Alamat: <http://127.0.0.1:8000>

Kata sandi semua akun demo: `demo1234` (atau pakai tombol **Masuk Cepat**).

> **Kalimat pembuka yang perlu diucapkan:** "Semua data yang akan Anda lihat
> adalah data sintetis. Aplikasi ini belum terhubung ke ERP, INDIGO, DPR/MPR,
> atau Beesha produksi. Banner kuning di setiap halaman menegaskan hal itu."

---

## 1. Halaman masuk (1 menit)

Buka `/masuk` di jendela B.

**Yang ditunjukkan:**

- Satu judul, satu kalimat penjelas, **satu field**, satu tombol. Tidak ada form
  panjang di langkah pertama.
- Teks bantu: "Gunakan nomor karyawan dari perusahaan, bukan NIK kartu identitas."
- Tombol **Tidak bisa masuk?** untuk pekerja yang tersangkut.
- Panel **Masuk Cepat — Akun Demo** dengan label tegas: "Ini **bukan** SSO
  perusahaan dan tidak terhubung ke sistem PHM."

**Peragakan alur dua langkah** (jangan pakai tombol cepat dulu):

1. Ketik `PHM-EMP-001` → **Lanjutkan**.
2. Langkah 2 menampilkan **Budi Santoso · Operator Produksi · PHM-EMP-001**.

> "Menampilkan nama di langkah kedua penting di lantai produksi: pekerja bisa
> memastikan ia masuk ke akunnya sendiri, bukan akun rekannya, sebelum mengetik
> kata sandi."

3. Kata sandi `demo1234` → **Masuk**.

Operator langsung mendarat di **Tugas Saya** — tidak ada layar pemilihan dashboard.

---

## 2. Ruang kerja operator (3 menit)

### 2.1 Tugas Saya

**Yang ditunjukkan:**

- Judul **Tugas Saya**, subjudul "Berikut pekerjaan yang perlu Anda selesaikan hari ini."
- Satu kartu pekerjaan: Knalpot Standar Motor A, DEMO-WO-004.
- Tiga angka besar: **Target 500 · Sudah dilaporkan 320 · Sisa pekerjaan 180**.
- Batang progres dua lapis.
- **Satu** tombol besar: **Catat Hasil Kerja**.
- Tidak ada grafik manajemen di layar operator.

Buka drawer (ikon hamburger):

> "Operator hanya melihat empat menu: Tugas Saya, Catat Hasil, Riwayat Saya,
> Bantuan. Menu disaring berdasarkan peran, jadi tidak ada operator yang
> dihadapkan pada dua puluh menu yang bukan urusannya."

Tunjukkan navbar:

> "Di kanan atas hanya ada dua ikon: tema dan profil. Tanpa teks di sampingnya.
> Nama tombolnya ada untuk pembaca layar, tapi tidak memenuhi layar."

Tekan ikon matahari/bulan → tema berganti. Muat ulang halaman:

> "Pilihannya diingat, dan tidak ada kedip tema saat halaman dimuat."

### 2.2 Catat Hasil Produksi

Tekan **Catat Hasil Kerja**.

**Yang ditunjukkan:**

- Satu kolom terpusat, kartu bertumpuk. Tanpa sidebar, tabel, atau grafik.
- **Kartu 1 — Informasi Pekerjaan: seluruhnya baca-saja.** Nama produk, nomor
  pekerjaan, nama petugas, tahap, target, sudah tercatat, sisa target.

> "Tidak ada satu pun dari tujuh baris ini yang perlu diketik ulang. Semuanya
> diambil dari identitas yang masuk dan dari penugasannya."

- **Kartu 2 — Hasil Pekerjaan.** Satu input besar, satuan `pcs` di dalam field,
  dan kalimat: "Masukkan **tambahan** unit yang selesai, bukan jumlah keseluruhan."

> "Di seluruh formulir ini, operator mengetik tepat satu angka."

**Peragakan validasi** — ini bagian penting:

1. Kosongkan field → **Periksa & Simpan Hasil** → "Masukkan jumlah hasil pekerjaan."
2. Isi `0` → "Jumlah harus lebih dari 0."
3. Isi `12.5` → "Masukkan jumlah dalam satuan unit utuh."
4. Isi `500` → setelah konfirmasi akan ditolak: "Jumlah yang Anda masukkan
   (500 pcs) melebihi sisa target (180 pcs)…"

> "Perhatikan: angkanya tidak dipotong diam-diam menjadi 180. Sistem menolak dan
> meminta operator memeriksa, karena PHM belum menetapkan kebijakan resmi untuk
> produksi melebihi target. Aturan yang dipakai di prototipe ini ditulis terbuka
> di konfigurasi dan di dokumentasi."

5. **Kartu 3 — Kendala.** Pilih "Ada kendala" → jenis kendala dan kolom
   penjelasan **muncul**. Pilih kembali "Tidak ada" → keduanya **hilang**.

> "Operator yang pekerjaannya lancar tidak pernah melihat kolom tambahan."

6. Isi **35** di kartu 2, kembalikan kendala ke "Tidak ada" → **Periksa & Simpan Hasil**.

### 2.3 Konfirmasi dan sukses

**Halaman Periksa Hasil Pekerjaan** muncul.

> "Belum ada yang tersimpan. Ini ringkasan, dengan pertanyaan 'Apakah hasil
> pekerjaan sudah sesuai?' dan dua pilihan: Perbaiki, atau Ya, Simpan Hasil."

Tekan **Ya, Simpan Hasil**.

**Halaman sukses:**

- "Hasil Berhasil Dicatat"
- "35 pcs telah masuk ke laporan pekerjaan Anda."
- Lencana **Menunggu Pemeriksaan**
- Nomor laporan dan waktu
- Tiga aksi: Kembali ke Tugas Saya · Catat Lagi · Lihat Riwayat

Tekan **Kembali ke Tugas Saya**. Tunjukkan angka berubah:

| | Sebelum | Sesudah |
| --- | --- | --- |
| Sudah dilaporkan | 320 | **355** |
| Sisa pekerjaan | 180 | **145** |

Dan muncul: "35 pcs menunggu pemeriksaan atasan."

---

## 3. Pimpinan melihat perubahannya (2 menit)

Pindah ke **jendela A**. Masuk sebagai **Pimpinan** (`PHM-EMP-007`).

**Yang ditunjukkan di Ringkasan Perusahaan:**

- Empat KPI utama saja di bagian pembuka.
- **Produksi Dilaporkan** sudah memuat 35 pcs tadi.
- **Produksi Terverifikasi** **belum** bertambah.

Baca catatan transparansi data di bawah KPI:

> "Dilaporkan adalah jumlah yang dicatat operator, termasuk N pcs dari M laporan
> yang belum diperiksa. Terverifikasi hanya mencakup laporan yang sudah disetujui
> supervisor."

> "Ini inti janji produk untuk manajemen: dashboard tidak pernah membuat angka
> yang belum diperiksa tampak seperti hasil final. Keduanya selalu disajikan
> sebagai dua angka terpisah, dengan batang progres dua warna."

Tunjukkan juga waktu **Diperbarui** di kanan atas dan keterangan interval
pembaruan otomatis.

---

## 4. Demonstrasi pembaruan lintas sesi (2 menit)

**Ini bagian yang perlu dijelaskan dengan jujur.**

1. Letakkan jendela A (Pimpinan, `/dashboard`) dan jendela B berdampingan.
2. Di jendela B, keluar dan masuk lagi sebagai Operator (`PHM-EMP-001`).
3. Catat hasil lagi, misalnya **20 pcs**.
4. **Jangan sentuh jendela A.** Tunggu hingga lima detik.

Angka **Produksi Dilaporkan** di jendela A bertambah 20 dengan sendirinya.

> "Yang baru saja terjadi: operator menyimpan ke database, lalu dashboard
> membaca ulang sendiri. Mekanismenya adalah **polling terotorisasi setiap lima
> detik**, bukan push WebSocket. Jadi ada jeda sampai lima detik, dan kami tidak
> menyebutnya real-time. Halaman ini menampilkan waktu pembaruan terakhir supaya
> tidak ada yang salah paham. Kalau PHM nanti menyetujui infrastruktur broadcast,
> titik pasangnya sudah jelas di satu service."

---

## 5. Supervisor memverifikasi (2 menit)

Di jendela B: keluar, masuk sebagai **Supervisor** (`PHM-EMP-002`).

**Halaman Pemeriksaan Hari Ini:**

- Tiga KPI: Dilaporkan · Terverifikasi · Menunggu pemeriksaan.
- Kartu laporan Budi Santoso dengan 35 pcs, waktu, kendala, dan progres penugasan.

Tekan **Perlu Perbaikan** pada salah satu laporan lain:

- Kolom "Apa yang perlu diperbaiki?" muncul dan **wajib** diisi. Coba kirim
  kosong → ditolak.
- Isi alasan, kirim.

> "Laporan itu sekarang keluar dari total dilaporkan, tapi barisnya tidak hilang.
> Ia pindah ke daftar 'Menunggu Dicatat Ulang', lengkap dengan catatan atasan,
> dan operatornya akan melihat peringatan di halaman Tugas Saya."

Sekarang tekan **Verifikasi** pada laporan 35 pcs milik Budi.

Pindah ke jendela A dan tunggu pembaruan:

| | Sebelum verifikasi | Sesudah |
| --- | --- | --- |
| Produksi Dilaporkan | 355 | **355** (tidak berubah) |
| Produksi Terverifikasi | 320 | **355** |

> "Perhatikan angka dilaporkan tidak menjadi 390. Jumlah yang sama tidak pernah
> dihitung dua kali. Di database tidak ada kolom total yang ditimbun — kedua
> angka selalu dihitung dari status laporan, jadi penghitungan ganda tidak
> mungkin terjadi secara struktural."

---

## 6. Beesha AI (3 menit)

Masih di jendela A sebagai Pimpinan. Tekan tombol biru **Tanya Beesha** di kanan
bawah.

> "Tombol ini ada di setiap halaman setelah masuk, di sudut yang sama, dan tidak
> hilang saat berpindah halaman. Ia juga tidak pernah menutupi tombol simpan,
> karena setiap halaman menyisakan ruang di bawah."

Baca label di header panel:

> "Asisten demo · menjawab dari database PHM One" dan banner: "Beesha versi demo
> menjawab langsung dari data PHM One tanpa mengirim apa pun ke layanan luar."

**Tanya:** "Berapa produksi yang dilaporkan dan diverifikasi hari ini?"

Tunjukkan jawabannya memuat: pesanan aktif, produksi dilaporkan, produksi
terverifikasi, menunggu pemeriksaan, dikirim hari ini, perlu perhatian, periode
data, satuan, dan catatan pembeda dilaporkan vs terverifikasi.

Tunjukkan label kecil di bawah jawaban: **"Dari data PHM One
(getAuthorizedOperationalKPIs)"**.

> "Setiap jawaban yang memakai data menyebut tool mana yang dipakai, sehingga
> selalu bisa dilacak apakah sebuah angka bersandar pada record resmi."

**Tanya:** "Apa kendala operasional terbaru?" → daftar peringatan terurut.

### Peragakan batas izin — bagian terpenting

Pindah ke jendela B sebagai **Operator**. Buka Beesha.

**Tanya pertanyaan yang sama:** "Berapa produksi yang dilaporkan hari ini?"

> "Operator mendapat angka **dirinya sendiri**, bukan angka perusahaan. Bukan
> karena menunya disembunyikan, tapi karena setiap tool memeriksa izin setiap
> kali dipanggil. Operator secara teknis tidak bisa menjangkau KPI perusahaan,
> bahkan kalau ia tahu nama tool-nya."

**Tanya:** "Bagaimana cara mencatat hasil produksi?" → lima langkah, tanpa
menyentuh data sama sekali.

**Tanya:** "Apa yang harus saya lakukan kalau salah input?" → penjelasan jalur
perbaikan lewat atasan.

**Tanya sesuatu di luar cakupan:** "Berapa harga saham perusahaan tetangga?"

> "Beesha mengatakan terus terang bahwa ia belum menangkap maksudnya, lalu
> menampilkan daftar hal yang bisa ia bantu. Ia tidak menebak dan tidak mengarang
> angka."

---

## 7. Alur bisnis lainnya (2 menit, pilih sesuai minat audiens)

### Admin Pesanan (`PHM-EMP-003`)
`/pesanan` → **Tambah Pesanan**. Pilih mitra → kontak dan alamat muncul sendiri.
Pilih barang → deskripsi dan satuan muncul sendiri.

> "Kembali ke prinsip yang sama: yang sudah diketahui perusahaan tidak diketik ulang."

### PPIC (`PHM-EMP-004`)
`/perencanaan` → "Pesanan Yang Perlu Direncanakan". Buat work order: memilih
pesanan mengisi produk dan sisa jumlah otomatis. Lalu `/perencanaan/penugasan` —
daftar karyawan hanya memuat yang berizin mencatat produksi.

### Gudang (`PHM-EMP-005`)
`/pengiriman` → "Barang Siap Dikirim" dengan catatan: "Hanya produksi yang sudah
diverifikasi supervisor dihitung sebagai barang siap kirim."

> "Jadi pengiriman tidak bisa mendahului pemeriksaan. Barang yang laporannya
> masih menunggu belum dianggap ada."

### Driver (`PHM-EMP-006`) — tunjukkan di ukuran layar ponsel
`/driver/pengiriman`. Tujuan di paling atas. Tombol selebar layar. Hanya
transisi yang valid yang ditampilkan: dari "Disiapkan" hanya ada **Mulai
Pengiriman** dan **Laporkan Kendala** — tidak ada tombol "Barang Diterima" yang
melompati langkah.

Tekan **Barang Diterima** pada pengiriman yang sudah tiba → nama penerima wajib.

### Administrator Sistem (`PHM-EMP-008`)
`/sistem/peran` → matriks izin. `/sistem/audit` → jejak siapa melakukan apa.
`/sistem/status` → **buka bagian "Batasan Yang Diketahui"**.

> "Batasan aplikasi ini tidak hanya ada di dokumentasi; ada di dalam aplikasinya
> sendiri, supaya tidak ada yang menyimpulkan lebih dari yang sebenarnya ada."

---

## 8. Penutup (1 menit)

Yang sudah berjalan:

- Alur inti lengkap: masuk → tugas → catat hasil → verifikasi → dashboard.
- Modul pendukung: pesanan, perencanaan, pengiriman, pengantaran, administrasi.
- Delapan peran dengan izin yang ditegakkan di server.
- Beesha AI di semua halaman, dengan batas izin yang nyata.
- 199 tes otomatis lulus, termasuk reproduksi persis skenario uji di spesifikasi.

Yang perlu keputusan PHM:

1. Kebijakan produksi melebihi target.
2. Tahap produksi resmi per lini produk.
3. Definisi penilaian kinerja, sebelum peringkat apa pun ditampilkan.
4. Penyedia identitas untuk SSO, dan sumber data peran karyawan.
5. Persetujuan pengiriman data bila Beesha akan memakai model sungguhan.
6. Apakah jeda lima detik memadai, atau perlu infrastruktur broadcast.

Yang belum ada, dan sengaja tidak dipalsukan:

- Integrasi ERP/INDIGO/DPR/MPR.
- SSO perusahaan, pemulihan kata sandi, passkey/biometrik.
- Deployment produksi.

> "Prinsip yang dipegang sepanjang pengerjaan: lebih baik menyatakan sebuah fitur
> belum ada daripada menampilkan halaman yang terlihat jadi tetapi tidak
> mengerjakan apa pun."

---

## Lampiran — daftar akun demo

| NIK | Nama | Peran |
| --- | --- | --- |
| `PHM-EMP-001` | Budi Santoso | Operator Produksi |
| `PHM-EMP-002` | Siti Nurhaliza | Supervisor Produksi / QC |
| `PHM-EMP-003` | Dewi Kartika | Admin Pesanan |
| `PHM-EMP-004` | Agus Wijaya | PPIC |
| `PHM-EMP-005` | Rudi Hartono | Petugas Gudang |
| `PHM-EMP-006` | Joko Prasetyo | Driver |
| `PHM-EMP-007` | Hartono Putra | Pimpinan / Management |
| `PHM-EMP-008` | Rizki Permana | Administrator Sistem |
| `PHM-EMP-009` | Yuni Astuti | Supervisor **dan** PPIC (untuk peragaan multi-peran) |

Kata sandi semua akun: `demo1234`

### Peragaan multi-peran (opsional)

Masuk sebagai `PHM-EMP-009` lewat alur kata sandi (akun ini tidak ada di tombol
cepat karena punya dua peran). Ia mendarat di Pemeriksaan. Buka **Profil** →
bagian **Ruang Kerja** → tekan **Pindah** pada PPIC → langsung diarahkan ke
Rencana Produksi, dan isi drawer berubah mengikuti peran barunya.
