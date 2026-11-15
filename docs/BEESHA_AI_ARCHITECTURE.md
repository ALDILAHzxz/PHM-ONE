# Arsitektur Beesha AI

## 1. Masalah yang diselesaikan

Beesha harus bisa menjawab pertanyaan operasional dalam Bahasa Indonesia sehari-hari
tanpa pernah:

- membocorkan data di luar izin pengguna,
- mengarang angka, nama, atau nomor dokumen,
- menyebut data yang belum diperiksa sebagai hasil final,
- menyetujui laporan produksi atau mengubah pengiriman,
- mengirim data perusahaan ke layanan luar tanpa persetujuan.

Semua jaminan itu ditegakkan oleh **kode**, bukan oleh instruksi di prompt.

## 2. Susunan lapisan

```
Pengguna menulis pertanyaan di panel chat
   │
   ▼
App\Livewire\BeeshaAssistant          ← UI; tidak menyentuh data operasional
   │
   ▼
App\Services\Beesha\BeeshaService     ← gerbang: cek beesha.use, simpan pesan, audit
   │
   ▼
AssistantProvider  (kontrak, dapat ditukar)
   ├── DemoAssistantProvider          ← bawaan: deterministik, tanpa model
   └── AnthropicAssistantProvider     ← opt-in: model memilih tool
   │
   ▼
App\Services\Beesha\BeeshaToolRegistry  ← SATU-SATUNYA pintu ke data
   │
   ▼
App\Services\OperationsMetrics + Eloquent  ← kueri yang sama dengan dashboard
```

Tidak ada jalur lain dari AI ke database. Tidak ada SQL yang dibentuk dari
masukan pengguna atau keluaran model.

## 3. Lapisan tool

Sepuluh tool, masing-masing mendeklarasikan izin yang dibutuhkan.

| Tool | Izin | Yang dikembalikan |
| --- | --- | --- |
| `getMyAssignments` | `production.report.create` | Penugasan aktif **milik sendiri**: target, dilaporkan, terverifikasi, sisa |
| `getMyProductionSummary` | `production.report.view_own` | Hasil **sendiri** hari ini |
| `getMyReportStatus` | `production.report.view_own` | 5 laporan terakhir **sendiri** + catatan pemeriksaan |
| `getPendingReviews` | `production.report.review` | Laporan yang menunggu pemeriksaan |
| `getTeamProductionSummary` | `production.report.view_team` | Hasil tim hari ini + penugasan aktif |
| `getOrderSummary` | `order.view` | Ringkasan pesanan + yang mendekati tenggat |
| `getShippingSummary` | `shipment.view` | Pengiriman hari ini, dalam perjalanan, diterima |
| `getMyShipments` | `shipment.view_own` | Pengiriman **milik driver sendiri** + tindakan berikutnya |
| `getAuthorizedOperationalKPIs` | `kpi.view` | KPI perusahaan + catatan dilaporkan vs terverifikasi |
| `getOperationalAlerts` | `dashboard.view` | Peringatan operasional terurut |

### Jaminan yang ditegakkan kode

1. **Pemeriksaan izin di setiap pemanggilan.** `BeeshaToolRegistry::call()`
   memanggil `userCanCall()` sebelum mengeksekusi apa pun, dan melempar
   `BeeshaAuthorizationException` bila gagal. Tidak ada jalan pintas.
2. **Pencakupan wajib di kueri.** Tool "milik sendiri" memfilter dengan
   `forUser($user->id)` / `where('user_id', …)` / `forDriver($user->id)` di dalam
   kuerinya. Pencakupan bukan parameter yang bisa diubah pemanggil — tidak ada
   argumen `user_id` yang diterima dari luar.
3. **Tidak ada SQL bebas.** Tool adalah metode PHP dengan kueri Eloquent tetap.
   Model tidak dapat meminta kueri sembarang.
4. **Tidak ada tool tulis.** Katalog hanya berisi operasi baca. Diuji dengan
   memanggil **setiap** tool sebagai **setiap** peran dan memastikan jumlah baris
   di empat tabel inti tidak berubah
   (`BeeshaAssistantTest::test_no_tool_in_the_catalogue_performs_a_write`).
5. **Model tidak diberi tahu tool yang tak boleh dipakai.** `toolSchemas()`
   hanya mengirim skema tool yang lolos `availableTo($user)`.
6. **Audit.** Setiap pemanggilan tool mencatat `beesha.tool_called` di
   `audit_events` (dengan `is_visible_feed = false`), berisi nama tool dan
   penyedia — bukan isi percakapan.

## 4. Penyedia demo (bawaan)

`DemoAssistantProvider` **tidak** memakai model bahasa. Cara kerjanya:

```
normalisasi pertanyaan (lowercase, rapikan spasi)
   │
   ├── cocokkan intent prosedural ("bagaimana cara…", "salah input")
   │      └── jawaban langkah-langkah tetap, tanpa akses data
   │
   └── cocokkan intent data → daftar kandidat tool (urut dari paling spesifik)
          └── ambil kandidat pertama yang diizinkan pengguna
                 └── panggil tool → format nilai nyata ke Bahasa Indonesia
```

Jika tidak ada yang cocok, asisten mengatakan tidak paham dan menampilkan
daftar hal yang bisa ia bantu — **tidak menebak**.

### Konsekuensi yang jujur

| Keuntungan | Kerugian |
| --- | --- |
| Tidak ada data yang keluar dari server | Hanya memahami frasa yang punya kata kuncinya |
| Setiap angka pasti ada di database | Tidak bisa berbasa-basi atau menyimpulkan bebas |
| Jawaban deterministik dan dapat diuji | Perlu penambahan kata kunci untuk frasa baru |
| Biaya nol, tanpa ketergantungan jaringan | Bukan AI generatif |

Di antarmuka, penyedia ini dilabeli **"Asisten demo · menjawab dari database PHM
One"** dengan banner penjelasan. Tidak pernah disajikan sebagai AI produksi yang
terhubung.

### Fallback kandidat tool

Daftar kandidat memungkinkan satu pertanyaan dijawab berbeda sesuai peran. Contoh:
"Berapa produksi hari ini?"

| Peran | Tool yang dipakai | Hasil |
| --- | --- | --- |
| Pimpinan | `getAuthorizedOperationalKPIs` | angka perusahaan |
| Supervisor | `getTeamProductionSummary` | angka tim |
| Operator | `getMyProductionSummary` | angka dirinya sendiri |

Operator tidak menerima pesan error otorisasi, melainkan jawaban yang relevan
untuk dirinya. Diuji oleh
`test_an_operator_asking_a_management_question_gets_only_their_own_figures`.

### Pertanyaan cepat yang selalu bisa dijawab

`suggestionsFor($user)` menyaring daftar saran berdasarkan tool yang boleh
dipanggil pengguna, sehingga sebuah chip saran tidak pernah menghasilkan error
izin. Diuji untuk enam peran sekaligus oleh
`test_every_suggested_question_produces_a_usable_answer`.

## 5. Penyedia model eksternal (opt-in)

`AnthropicAssistantProvider` dinonaktifkan secara bawaan.

```env
BEESHA_PROVIDER=anthropic
BEESHA_API_KEY=...        # memerlukan persetujuan tertulis PHM
BEESHA_MODEL=claude-haiku-5-5
```

### Apa yang dikirim keluar bila diaktifkan

1. Pertanyaan pengguna.
2. Hingga 8 pesan terakhir dari percakapan.
3. Nama dan peran pengguna di dalam system prompt.
4. **Hasil pemanggilan tool** — yaitu data operasional yang sudah tercakup pada
   izin pengguna.

Ini berarti data operasional PHM meninggalkan server. **Karena itu fitur ini
memerlukan persetujuan organisasi.** Bawaan `demo` tidak mengirim apa pun.

### Apa yang tetap terjaga

- Model tidak menerima kredensial database dan tidak memiliki akses tabel.
- Model hanya dapat meminta tool yang pengguna ini berhak panggil.
- Setiap hasil tool tetap dihasilkan `BeeshaToolRegistry` dengan pemeriksaan
  izinnya sendiri. Model meminta; registry yang memutuskan.
- Bila model meminta tool yang tidak diizinkan, ia menerima `tool_result` berisi
  penolakan dan instruksi eksplisit untuk tidak menebak nilainya.
- Tidak ada tool tulis yang diekspos, jadi model secara struktural tidak dapat
  menyetujui produksi atau mengubah pengiriman.
- Loop tool dibatasi 4 putaran; bila habis, asisten mengatakan tidak dapat
  menyimpulkan — bukan mengarang kesimpulan.

### Perilaku saat gagal

| Keadaan | Yang terjadi |
| --- | --- |
| `BEESHA_PROVIDER=anthropic` tanpa API key | Jatuh ke asisten demo, dan `provider` yang dicatat adalah `demo` — tidak mengaku terhubung |
| Panggilan API error/timeout | Jatuh ke asisten demo, jawaban diberi catatan "_Layanan AI sedang tidak dapat dihubungi, jawaban ini disusun langsung dari database._" |
| Loop tool habis | Minta pertanyaan yang lebih spesifik |

## 6. Mutu jawaban

Aturan yang berlaku untuk kedua penyedia:

| Aturan | Cara ditegakkan |
| --- | --- |
| Bahasa Indonesia | Seluruh formatter dan system prompt |
| Menyebut periode data | Setiap tool mengembalikan field `period`; formatter selalu mencantumkannya |
| Menyebut satuan | Field `satuan` pada hasil tool |
| Membedakan dilaporkan vs terverifikasi | Dicetak sebagai dua baris terpisah + field `catatan` |
| Tidak mengarang angka | Demo: angka hanya dari hasil tool. Anthropic: aturan prompt + penolakan tool |
| Menjelaskan saat data tidak ada | Cabang khusus `count === 0` di setiap formatter |
| Menandai sumber | Tiap balasan menyimpan `tool_used` dan `provider`, dan UI menampilkan "Dari data PHM One (<tool>)" |

## 7. Keamanan antarmuka chat

`App\Support\ChatMarkup::render()` meng-escape seluruh teks **lebih dulu**, baru
mengizinkan `**tebal**`, `_miring_`, baris baru, dan indentasi butir. Tidak ada
HTML lain yang diinterpretasikan.

Konsekuensinya, jawaban apa pun — termasuk yang berasal dari model eksternal atau
dari isi catatan yang diisi pengguna lain — tidak dapat menyuntikkan markup ke
halaman.

## 8. Penempatan dan perilaku UI

Sesuai spesifikasi §11:

- Tombol mengapung **tetap** di kanan bawah (`fixed right-5 bottom-6`,
  `z-40`), dirender satu kali di dalam layout terautentikasi bersama sehingga
  hadir di semua rute dan tidak hilang saat berpindah halaman.
- `z-index` berada di bawah overlay drawer navigasi (`z-[60]`) dan panel chat
  (`z-50`), sehingga tidak pernah menutupi dialog aktif.
- Tombol **disembunyikan** saat panel chat terbuka, agar tidak menutupi percakapan.
- `<main>` memiliki `pb-28` / `md:pb-32` sehingga tombol tidak menutupi tombol
  simpan, pesan validasi, atau aksi di bagian bawah halaman.
- Desktop: drawer kanan 448–480 px dengan header tetap dan input di bawah.
- Mobile: panel nyaris penuh layar, teks pesan 16 px, input di atas keyboard
  (`env(safe-area-inset-bottom)`).
- Saran pertanyaan **otomatis menutup** setelah pertanyaan pertama agar ruang
  jawaban lebih lega, dan dapat dibuka kembali.

Kehadiran tombol pada setiap ruang kerja diuji oleh
`BeeshaAssistantTest::test_the_floating_button_is_present_on_every_authenticated_workspace`
(19 halaman di 7 peran), dan ketidakhadirannya bagi pengunjung yang belum masuk
diuji terpisah.

## 9. Riwayat percakapan

Satu percakapan per sesi browser (`session('phm.beesha_conversation')`), sehingga
riwayat bertahan saat pengguna berpindah halaman. Disimpan di `beesha_messages`,
dibatasi `phm.beesha.max_history` (40) saat dibaca, dan dapat dihapus pengguna
dari header panel.

## 10. Batasan yang diketahui

1. Asisten demo memahami frasa berdasarkan kata kunci. Pertanyaan dengan susunan
   kata yang jauh berbeda akan dijawab "belum menangkap maksud" beserta daftar
   kemampuannya.
2. Tidak ada pemahaman lintas pertanyaan pada penyedia demo (tidak ada rujukan
   "bagaimana dengan kemarin?" setelah pertanyaan sebelumnya).
3. Rentang waktu terbatas pada hari ini; belum ada tool untuk rentang tanggal
   bebas.
4. Penyedia Anthropic sudah diimplementasikan tetapi **belum pernah diuji
   terhadap API sungguhan** di lingkungan ini, karena tidak ada API key dan
   pengiriman data perusahaan ke layanan luar belum diotorisasi. Alur fallback-nya
   yang diuji, bukan jalur suksesnya.
