# Arsitektur PHM One

## 1. Gambaran umum

PHM One adalah aplikasi Laravel monolitik dengan antarmuka server-rendered
(Blade) yang dibuat reaktif oleh Livewire. Tidak ada SPA terpisah, tidak ada API
publik, dan tidak ada state bisnis yang disimpan di browser.

```
┌─────────────────────────────────────────────────────────────────┐
│ Browser                                                         │
│  Blade + Livewire 4 + Alpine (dibundel Livewire) + Tailwind 4   │
│  localStorage HANYA untuk preferensi tema                        │
└───────────────────────────┬─────────────────────────────────────┘
                            │ HTTP / POST livewire update
┌───────────────────────────▼─────────────────────────────────────┐
│ Lapisan presentasi                                              │
│  routes/web.php  →  middleware auth, active, role, permission    │
│  Controllers (tipis)  →  View Blade  →  Komponen Livewire        │
└───────────────────────────┬─────────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────────┐
│ Lapisan domain (app/Services)                                   │
│  ProductionReportService   OrderService      PlanningService     │
│  ShipmentService           OperationsMetrics DocumentNumbering   │
│  AuditLogger               RoleRouting                           │
│  Beesha\{BeeshaService, BeeshaToolRegistry, *Provider}           │
└───────────────────────────┬─────────────────────────────────────┘
                            │ Eloquent, transaksi, constraint
┌───────────────────────────▼─────────────────────────────────────┐
│ Database bersama (SQLite di prototipe; portabel ke MySQL/PgSQL)  │
└─────────────────────────────────────────────────────────────────┘
```

## 2. Pemisahan tanggung jawab

| Lapisan | Berisi | Tidak boleh berisi |
| --- | --- | --- |
| Blade / Livewire | tampilan, validasi bentuk input, status langkah form | aturan bisnis, perhitungan total |
| Controller | resolusi rute, pengambilan data untuk tampilan | mutasi data |
| Service | aturan bisnis, transaksi, audit, otorisasi tingkat record | HTML, `request()` |
| Model | relasi, scope kueri, turunan per-record | mutasi lintas entitas |
| Support (`app/Support`) | konstanta, status, label, navigasi | kueri database |

**Aturan penting:** komponen Livewire **tidak pernah** menulis baris produksi
sendiri. `ProductionReportForm` memanggil `ProductionReportService::submit()`.
Konsekuensinya, aturan yang sama berlaku lewat antarmuka mana pun, dan tes dapat
menguji aturan tanpa melalui UI.

## 3. Alur penulisan laporan produksi

```
Operator mengisi form
   │
   ├─ Livewire validate()          ← pesan ramah, nilai lama dipertahankan
   │
   ├─ step = confirm               ← ringkasan; BELUM ada yang disimpan
   │
   └─ ProductionReportService::submit()
        1. Gate::allows('report-on-assignment')   ← penugasan milik sendiri & terbuka
        2. cek idempotency_key                     ← kiriman ulang → baris asli
        3. DB::transaction
             a. lockForUpdate penugasan            ← cegah balapan sisa target
             b. cek acceptsReports()
             c. cek sisa target (aturan demo)      ← tolak, jangan potong diam-diam
             d. create ProductionReport (status = menunggu_pemeriksaan)
             e. naikkan status penugasan bila perlu
             f. AuditLogger::logModel()            ← satu transaksi dengan datanya
        4. commit
   │
   └─ step = success               ← hanya setelah commit
```

## 4. Angka produksi: diturunkan, bukan diakumulasi

Tidak ada kolom `reported_qty` atau `verified_qty` di database. Keduanya
dihitung dari baris `production_reports` berdasarkan statusnya:

| Status | Masuk "dilaporkan" | Masuk "terverifikasi" |
| --- | --- | --- |
| `menunggu_pemeriksaan` | ✅ | ❌ |
| `terverifikasi` | ✅ | ✅ |
| `perlu_perbaikan` | ❌ | ❌ |
| `dibatalkan` | ❌ | ❌ |

Aturan ini tinggal di satu tempat: `App\Support\ReportStatus`. Karena total
dihitung saat dibaca, memverifikasi sebuah laporan **memindahkan** jumlahnya dari
"menunggu" ke "terverifikasi" tanpa mengubah "dilaporkan" — perhitungan ganda
secara struktural tidak mungkin terjadi.

`ProductionAssignment::scopeWithProgress()` memuat ketiga jumlah itu sebagai
subquery agar daftar penugasan tidak memicu kueri per baris.

## 5. Satu sumber kebenaran untuk KPI

`App\Services\OperationsMetrics` adalah satu-satunya tempat angka operasional
dihitung. Dashboard pimpinan, halaman produksi, antrean supervisor, dan
tool Beesha AI semuanya memanggil kelas ini.

Konsekuensinya: angka yang disebut Beesha tidak mungkin berbeda dari angka di
dashboard. Hal ini diuji secara eksplisit
(`MetricsConsistencyTest::test_the_dashboard_and_the_assistant_report_the_same_figures`).

## 6. Otorisasi

Tiga lapis, semuanya di server:

1. **Middleware rute** — `auth`, `active`, `role:…`, `permission:…` di
   `routes/web.php`. Mengetik URL langsung tidak menembus batas ini.
2. **Gate per izin** — setiap kode izin menjadi ability Gate di
   `AuthorizationServiceProvider`, dipakai oleh `@can` dan service.
3. **Gate tingkat record** — `report-on-assignment`, `view-production-report`,
   `review-production-report`, `update-shipment-status`.

Antarmuka hanya menyembunyikan apa yang sudah ditolak. `App\Support\Navigation`
bahkan menyaring entri drawer agar tidak pernah menawarkan tautan yang akan
menghasilkan 403 — diuji oleh
`AuthorizationMatrixTest::test_drawer_navigation_only_offers_reachable_routes`.

## 7. Pembaruan lintas sesi

Dicapai dengan **polling terotorisasi** (`wire:poll`), bukan WebSocket.

```
Operator submit  →  transaksi commit  →  database
                                            ▲
Dashboard pimpinan ── wire:poll.5s ─────────┘  (kueri terotorisasi diulang)
```

Alasan pemilihan:

- Tidak ada infrastruktur broadcast (Reverb/Pusher) yang diotorisasi untuk prototipe.
- `php artisan serve` dan hosting PHP bersama tidak menyediakan proses WebSocket.
- Setiap polling menjalankan ulang kueri yang sama lengkap dengan pemeriksaan izin,
  sehingga tidak ada jalur data yang melewati otorisasi.

Batasannya jujur: ada jeda hingga `PHM_POLL_SECONDS` detik (bawaan 5). Ini
**bukan** push real-time, dan tidak disebut demikian di antarmuka — halaman
menampilkan waktu "Diperbarui" dan interval pembaruannya.

Bila nanti broadcast disetujui, titik pasangnya jelas: `ProductionReportService`
sudah menjadi satu-satunya tempat laporan dibuat/diverifikasi, jadi event
broadcast ditambahkan di sana tanpa menyentuh UI.

## 8. Beesha AI

Lihat [`BEESHA_AI_ARCHITECTURE.md`](BEESHA_AI_ARCHITECTURE.md). Ringkasnya:

```
Pertanyaan pengguna
   │
   ├─ BeeshaService  ← cek izin beesha.use, simpan pesan, audit
   │
   └─ AssistantProvider (dapat ditukar)
        ├─ DemoAssistantProvider      ← pencocokan intent → 1 tool → format
        └─ AnthropicAssistantProvider ← model memilih tool; opt-in
              │
              └─ BeeshaToolRegistry   ← SATU-SATUNYA pintu ke data
                     • setiap tool mendeklarasikan izin yang dibutuhkan
                     • izin diperiksa ulang pada setiap pemanggilan
                     • hasil selalu tercakup (scoped) pada pengguna
                     • tidak ada SQL bebas, tidak ada tool yang menulis
```

## 9. Penyimpangan dari spesifikasi

Spesifikasi master menyarankan Next.js + PostgreSQL + Prisma. Pengguna meminta
Laravel, sehingga keputusan berikut diambil dan dicatat di sini.

| Spesifikasi | Implementasi | Alasan |
| --- | --- | --- |
| Next.js App Router + React | Laravel 13 + Blade + Livewire 4 | Diminta pengguna. Livewire memberi reaktivitas (form bertahap, drawer chat, dashboard yang memperbarui diri) tanpa SPA terpisah. |
| PostgreSQL + Prisma | SQLite + Eloquent | PostgreSQL tidak tersedia di lingkungan; MariaDB XAMPP tidak listening dan MySQL 8 tidak diketahui kata sandinya. Skema dibuat portabel: tanpa tipe khusus SQLite. |
| Zod + React Hook Form | Validasi Laravel + aturan Livewire | Padanan native; validasi tetap berjalan di server. |
| Auth.js | Guard sesi Laravel | Padanan native. Batas OIDC disiapkan di `config/phm.php`. |
| Recharts | Batang progres SVG/CSS buatan sendiri | Satu-satunya visualisasi yang dibutuhkan adalah batang dilaporkan-vs-terverifikasi. Menambah pustaka grafik untuk itu tidak sepadan (lihat §39 spesifikasi: tanpa dependensi yang tidak perlu). |
| Vitest + Playwright | PHPUnit 12 + Livewire test helpers | Padanan native. Tes berinteraksi dengan komponen Livewire nyata dan menegaskan markup yang dirender. Tidak ada tes browser visual — lihat batasan di `QA_REPORT.md`. |
| Entitas `User` dan `Employee` terpisah | Satu tabel `users` | Setiap identitas aplikasi adalah karyawan PHM; pemisahan 1:1 menambah join tanpa menambah makna. Kolom mempertahankan nama domain (`employee_number`, `full_name`, `department_id`, `status`) agar pemisahan dapat dikembalikan tanpa rename. |
| Entitas `ActivityLog` dan `AuditEvent` terpisah | Satu tabel `audit_events` | Keduanya mencatat "siapa melakukan apa pada record mana, kapan". Feed "Aktivitas Terbaru" adalah baca tersaring (`is_visible_feed`). |
| Entitas `Notification` | Belum ada | Peringatan diturunkan dari record hidup oleh `OperationsMetrics::alerts()`, sehingga tidak bisa basi. Tabel notifikasi tanpa pengirim/penerima yang disetujui hanya akan menjadi placeholder kosong. |

## 10. Struktur direktori

```
app/
├── Exceptions/ProductionReportException.php   pesan penolakan siap-pakai
├── Http/
│   ├── Controllers/        9 controller tipis
│   └── Middleware/         EnsureUserHasRole, …HasPermission, …AccountIsActive
├── Livewire/
│   ├── Auth/LoginForm
│   ├── Operator/{TaskList, ProductionReportForm, ReportHistory}
│   ├── Supervisor/ReviewQueue
│   ├── Orders/OrderForm
│   ├── Planning/{WorkOrderManager, AssignmentManager}
│   ├── Shipping/ShipmentForm
│   ├── Driver/DeliveryList
│   ├── Management/CompanyOverview
│   └── BeeshaAssistant
├── Models/                 17 model
├── Providers/              AuthorizationServiceProvider, BeeshaServiceProvider
├── Services/
│   ├── Beesha/             registry tool, penyedia, service
│   └── *.php               service domain
└── Support/                Roles, Permissions, ReportStatus, OrderStatus,
                            ShipmentStatus, IssueCategory, Navigation, ChatMarkup

resources/views/
├── components/             layout + komponen UI Blade
├── livewire/               tampilan komponen Livewire
└── <peran>/                halaman per ruang kerja

database/
├── migrations/             6 migrasi
└── seeders/                RolePermission, Catalog, DemoUser, DemoOperations
```

## 11. Titik integrasi untuk layanan perusahaan

Semua batas sengaja dibuat eksplisit agar penggantian nanti bersifat lokal:

| Kebutuhan | Titik pasang |
| --- | --- |
| SSO OIDC | `config/phm.php` → `phm.oidc`; `LoginForm::authenticate()` diganti alur Authorization Code + PKCE |
| Peran dari direktori perusahaan | `DemoUserSeeder` diganti sinkronisasi; `User::roles()` tidak berubah |
| ERP / INDIGO sebagai sumber pesanan | `OrderService::create()` menjadi adapter; skema `customer_orders` tetap |
| Beesha produksi | `AssistantProvider` baru; `BeeshaToolRegistry` tidak berubah |
| Broadcast real-time | event di `ProductionReportService`; `wire:poll` diganti `wire:stream`/Echo |
