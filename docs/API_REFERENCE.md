# Referensi Antarmuka Internal

PHM One **tidak** mengekspos HTTP API publik. Antarmuka internalnya terdiri dari
rute web, komponen Livewire, dan service domain. Dokumen ini adalah kontrak yang
perlu diketahui saat mengubah atau memperluas aplikasi.

## 1. Rute web

Semua rute di `routes/web.php`. Kolom penjaga menyebut middleware; detail izin di
[`ROLE_PERMISSION_MATRIX.md`](ROLE_PERMISSION_MATRIX.md) §3.

### Tamu

| Metode | URL | Nama | Controller |
| --- | --- | --- | --- |
| GET | `/` | — | `Auth\LoginController@show` |
| GET | `/masuk` | `login` | `Auth\LoginController@show` |
| GET | `/bantuan-masuk` | `login.help` | `Auth\LoginController@help` |

### Terautentikasi (`auth` + `active`)

| Metode | URL | Nama | Controller |
| --- | --- | --- | --- |
| POST | `/keluar` | `logout` | `Auth\LoginController@logout` |
| GET | `/beranda` | `home` | `HomeController` |
| GET | `/bantuan` | `help` | `HelpController` |
| GET | `/profil` | `profile` | `ProfileController@show` |
| POST | `/profil/peran` | `profile.switch-role` | `ProfileController@switchRole` |
| PUT | `/profil/kata-sandi` | `profile.password` | `ProfileController@updatePassword` |
| GET | `/operator/tugas` | `operator.tasks` | `OperatorController@tasks` |
| GET | `/operator/catat-hasil/{assignment?}` | `operator.report.create` | `OperatorController@createReport` |
| GET | `/operator/riwayat` | `operator.history` | `OperatorController@history` |
| GET | `/supervisor/pemeriksaan` | `supervisor.reviews` | `SupervisorController@reviews` |
| GET | `/supervisor/kendala` | `supervisor.issues` | `SupervisorController@issues` |
| GET | `/supervisor/riwayat` | `supervisor.history` | `SupervisorController@history` |
| GET | `/pesanan` | `orders.index` | `OrderController@index` |
| GET | `/pesanan/tambah` | `orders.create` | `OrderController@create` |
| GET | `/pesanan/{order}` | `orders.show` | `OrderController@show` |
| GET | `/perencanaan` | `planning.index` | `PlanningController@index` |
| GET | `/perencanaan/work-order` | `planning.work-orders` | `PlanningController@workOrders` |
| GET | `/perencanaan/penugasan` | `planning.assignments` | `PlanningController@assignments` |
| GET | `/pengiriman` | `shipments.index` | `ShipmentController@index` |
| GET | `/pengiriman/catat` | `shipments.create` | `ShipmentController@create` |
| GET | `/pengiriman/riwayat` | `shipments.history` | `ShipmentController@history` |
| GET | `/driver/pengiriman` | `driver.shipments` | `DriverController@shipments` |
| GET | `/driver/riwayat` | `driver.history` | `DriverController@history` |
| GET | `/dashboard` | `dashboard` | `DashboardController@index` |
| GET | `/dashboard/produksi` | `dashboard.production` | `DashboardController@production` |
| GET | `/dashboard/pengiriman` | `dashboard.shipping` | `DashboardController@shipping` |
| GET | `/dashboard/peringatan` | `dashboard.alerts` | `DashboardController@alerts` |
| GET | `/sistem/pengguna` | `system.users` | `SystemController@users` |
| GET | `/sistem/peran` | `system.roles` | `SystemController@roles` |
| GET | `/sistem/audit` | `system.audit` | `SystemController@audit` |
| GET | `/sistem/status` | `system.status` | `SystemController@status` |

Parameter kueri yang divalidasi:

| Rute | Parameter | Aturan |
| --- | --- | --- |
| `orders.index` | `cari` | `nullable string max:100` |
| `orders.index` | `status` | `nullable in:` daftar `OrderStatus::OPTIONS` |
| `system.users` | `cari` | `nullable string max:100` |
| `system.audit` | `aksi` | `nullable string max:64` |

### Rute internal Livewire

`POST /livewire-<hash>/update` menangani seluruh interaksi komponen. Dilindungi
CSRF dan middleware web, jadi setiap aksi komponen tetap melewati pemeriksaan
`auth` dan `active`.

## 2. Komponen Livewire

### `Auth\LoginForm`

| Properti | Tipe | Keterangan |
| --- | --- | --- |
| `step` | string | `identify` \| `authenticate` |
| `employee_number` | string | Nomor Induk Karyawan |
| `password` | string | hanya dipakai pada langkah 2 |
| `remember` | bool | |
| `identifiedName` / `identifiedPosition` / `identifiedInitials` | ?string | tampilan langkah 2 |

| Aksi | Efek |
| --- | --- |
| `identify()` | validasi NIK, batasi percobaan (12/menit), pindah ke langkah 2 |
| `backToIdentify()` | kembali ke langkah 1, bersihkan kata sandi |
| `authenticate()` | `Auth::attempt` + `status=active`, batasi percobaan (5/menit) |
| `loginAsDemo(string $employeeNumber)` | hanya bila `phm.demo_mode`, hanya akun `is_demo` |

### `Operator\TaskList`

Computed: `assignments()`, `upcoming()`, `latestReports()`, `needsCorrection()`,
`todayTotals()`. Tanpa aksi mutasi. Polling `phm.poll_seconds`.

### `Operator\ProductionReportForm`

| Properti | Tipe | Keterangan |
| --- | --- | --- |
| `assignmentId` | ?int | diverifikasi milik pengguna |
| `step` | string | `form` \| `confirm` \| `success` |
| `quantity` | string | satu-satunya angka yang diketik operator |
| `issueState` | string | `tidak` \| `ada` |
| `issueCategory` | string | kunci `IssueCategory::OPTIONS` |
| `issueDescription` | string | wajib bila `issueState === 'ada'` |
| `additionalNotes` | string | opsional, maks 1000 |
| `idempotencyKey` | string | UUID, dibuat ulang hanya saat entri baru dimulai |
| `savedReportId` | ?int | diisi setelah commit |

| Aksi | Efek |
| --- | --- |
| `selectAssignment(int $id)` | `abort_unless` penugasan ada di daftar milik pengguna |
| `updatedIssueState(string)` | mengosongkan field kendala saat dipilih "tidak" |
| `review()` | validasi, pindah ke `confirm`; **tidak menyimpan apa pun** |
| `backToForm()` | kembali ke `form` |
| `save()` | memanggil `ProductionReportService::submit()`; `success` hanya setelah berhasil |
| `recordAnother()` | reset field + idempotency key baru |

Mount menerima `?ProductionAssignment $assignment` dan `abort(403)` bila
penugasan bukan milik pengguna.

### `Supervisor\ReviewQueue`

Computed: `pending()`, `needsCorrection()`, `recentlyVerified()`, `teamTotals()`.

| Aksi | Efek |
| --- | --- |
| `verify(int $reportId)` | `ProductionReportService::review(..., VERIFIED)` |
| `startCorrection(int $reportId)` | membuka kotak catatan untuk laporan itu |
| `cancelCorrection()` | menutup kotak catatan |
| `requestCorrection()` | validasi catatan (min 5), lalu `review(..., NEEDS_CORRECTION)` |

### `Orders\OrderForm`

Properti: `step`, `partnerId`, `poNumber`, `productId`, `quantity`,
`deliveryTargetDate`, `notes`, `savedOrderId`.
Aksi: `review()`, `backToForm()`, `save()`, `addAnother()`.

### `Planning\WorkOrderManager`

Properti: `showForm`, `customerOrderId`, `productId`, `targetQuantity`,
`plannedDate`, `notes`.
`updatedCustomerOrderId()` mengisi produk dan sisa jumlah yang belum
direncanakan. Aksi: `save()`.

### `Planning\AssignmentManager`

Properti: `showForm`, `workOrderId`, `operatorId`, `assignedQuantity`,
`plannedDate`, `workShiftId`, `stageId`, `workStationId`, `notes`.
`updatedWorkOrderId()` mengisi sisa yang belum ditugaskan dan tanggalnya.
Aksi: `save()`.

### `Shipping\ShipmentForm`

Properti: `step`, `orderId`, `quantity`, `deliveryNote`, `driverId`, `notes`,
`idempotencyKey`, `savedShipmentId`.
Aksi: `review()`, `backToForm()`, `save()`, `recordAnother()`.

### `Driver\DeliveryList`

Properti: `actionFor`, `pendingStatus`, `recipientName`, `actionNotes`.

| Aksi | Efek |
| --- | --- |
| `startAction(int $shipmentId, string $status)` | transisi langsung, atau buka field tambahan untuk `diterima`/`kendala` |
| `cancelAction()` | tutup field tambahan |
| `confirmAction()` | validasi nama penerima atau penjelasan kendala, lalu terapkan |

### `Management\CompanyOverview`

Computed: `kpis()`, `progress()`, `shipping()`, `alerts()`,
`approachingDeadline()`, `pendingReviews()`, `recentActivity()`.
Tanpa aksi mutasi. Polling `phm.poll_seconds`.

### `BeeshaAssistant`

| Properti | Tipe | Keterangan |
| --- | --- | --- |
| `open` | bool | panel terbuka |
| `question` | string | maks 500 karakter |
| `conversationId` | string | UUID per sesi browser |
| `showSuggestions` | bool | menutup sendiri setelah pertanyaan pertama |

Aksi: `toggle()`, `close()`, `send()`, `askSuggestion(string)`,
`clearConversation()`. Mount `abort_unless` pengguna punya `beesha.use`.

## 3. Service domain

### `ProductionReportService`

```php
submit(User $user, ProductionAssignment $assignment, array $data): ProductionReport
```

`$data`: `quantity` int, `has_issue` bool, `issue_category` ?string,
`issue_description` ?string, `additional_notes` ?string, `idempotency_key` string.

Melempar `ProductionReportException` untuk: tidak berwenang, penugasan tertutup,
melebihi sisa target tanpa izin pengecualian.

```php
review(User $reviewer, ProductionReport $report, string $decision, ?string $notes = null): ProductionReview
```

`$decision`: `ReportStatus::VERIFIED` atau `ReportStatus::NEEDS_CORRECTION`.
Melempar `ProductionReportException` untuk: keputusan tak dikenal, tidak
berwenang, memeriksa laporan sendiri, catatan kosong saat meminta perbaikan,
laporan sudah diperiksa.

```php
newIdempotencyKey(): string
```

### `OrderService`

```php
create(User $user, array $data): CustomerOrder
refreshFulfilmentStatus(CustomerOrder $order): CustomerOrder
```

`create()` melempar `ValidationException` pada `po_number` bila kombinasi
mitra + PO sudah ada.

### `PlanningService`

```php
createWorkOrder(User $user, array $data): WorkOrder
createAssignment(User $planner, array $data): ProductionAssignment
```

`createWorkOrder()` menolak bila total work order akan melebihi jumlah pesanan.
`createAssignment()` menolak bila melebihi sisa work order, atau bila karyawan
tujuan tidak memiliki `production.report.create`.

### `ShipmentService`

```php
record(User $user, array $data): Shipment
updateStatus(User $user, Shipment $shipment, string $newStatus,
             ?string $notes = null, ?string $recipientName = null): Shipment
newIdempotencyKey(): string
```

`record()` menolak bila melebihi sisa pesanan atau melebihi stok terverifikasi.
`updateStatus()` menolak transisi yang tidak ada di `ShipmentStatus::TRANSITIONS`,
dan mewajibkan catatan untuk status `kendala`.

### `OperationsMetrics`

```php
productionForDate(?Carbon $date = null): array
headlineKpis(): array
alerts(): Collection
productionProgress(int $limit = 10): Collection
shippingSummary(): array
orderSummary(): array
summaryForUser(User $user, ?Carbon $date = null): array
```

Satu-satunya tempat angka operasional dihitung. Dashboard, halaman produksi,
antrean supervisor, dan tool Beesha semuanya memanggil kelas ini.

### `DocumentNumbering`

```php
productionReport(?Carbon $date = null): string   // LP-YYYYMMDD-0001
customerOrder(?Carbon $date = null): string      // PO-YYYYMMDD-0001
workOrder(?Carbon $date = null): string          // WO-YYYYMMDD-0001
shipment(?Carbon $date = null): string           // SJ-YYYYMMDD-0001
deliveryNote(?Carbon $date = null): string       // SRT-YYYYMMDD-0001
```

Nomor dibaca di dalam transaksi pemanggil, dan kolomnya ber-`UNIQUE`, sehingga
tabrakan di bawah konkurensi muncul sebagai galat database — bukan dua record
yang diam-diam memakai nomor sama.

### `AuditLogger`

```php
log(?User $actor, string $action, string $entityType, ?int $entityId,
    string $summary, array $metadata = [], bool $visibleInFeed = true): AuditEvent

logModel(?User $actor, string $action, Model $model,
         string $summary, array $metadata = [], bool $visibleInFeed = true): AuditEvent
```

Dipanggil **di dalam** transaksi mutasi yang dijelaskannya, sehingga data dan
jejak auditnya commit atau gagal bersama.

### `RoleRouting`

```php
homeRouteFor(User $user): string
homeUrlFor(User $user): string
```

### `Beesha\BeeshaService`

```php
providerName(): string
isDemoProvider(): bool
suggestionsFor(User $user): array
history(User $user, string $conversationId): Collection
ask(User $user, string $conversationId, string $question): BeeshaMessage
clearHistory(User $user, string $conversationId): void
```

### `Beesha\BeeshaToolRegistry`

```php
catalogue(): array                      // nama tool => [permission, description]
availableTo(User $user): array          // nama tool yang boleh dipanggil
userCanCall(User $user, string $tool): bool
call(User $user, string $tool, array $arguments = []): array
```

`call()` melempar `BeeshaAuthorizationException` bila izin tidak dimiliki atau
nama tool tidak dikenal. Daftar tool ada di
[`BEESHA_AI_ARCHITECTURE.md`](BEESHA_AI_ARCHITECTURE.md) §3.

## 4. Kontrak nilai (`app/Support`)

| Kelas | Isi |
| --- | --- |
| `Roles` | 8 konstanta kode peran + `ALL` |
| `Permissions` | 24 konstanta kode izin + `ALL` (kode => [nama, grup]) |
| `ReportStatus` | 4 status, `COUNTS_AS_REPORTED`, `COUNTS_AS_VERIFIED`, `label()`, `badgeClasses()` |
| `OrderStatus` | 6 status, `ACTIVE`, `OPTIONS`, `label()`, `badgeClasses()` |
| `ShipmentStatus` | 6 status, `TRANSITIONS`, `COUNTS_AS_SHIPPED`, `canTransition()`, `nextStatuses()`, `label()`, `actionLabel()`, `badgeClasses()` |
| `IssueCategory` | 4 kategori, `OPTIONS`, `label()` |
| `Navigation` | `for(User $user): array` — entri drawer tersaring izin |
| `ChatMarkup` | `render(string $text): HtmlString` — escape dulu, lalu izinkan subset kecil |

Mengubah salah satu nilai status berarti mengubahnya di satu tempat; seluruh
label, lencana, dan aturan penghitungan mengikutinya.

## 5. Gate

Didefinisikan di `AuthorizationServiceProvider`.

| Gate | Argumen | Lulus bila |
| --- | --- | --- |
| *setiap kode izin* | — | pengguna memiliki izin tersebut |
| `report-on-assignment` | `ProductionAssignment` | punya `production.report.create`, penugasan miliknya, dan masih terbuka |
| `view-production-report` | `ProductionReport` | penulis dengan `view_own`, atau punya `view_team` |
| `review-production-report` | `ProductionReport` | punya `production.report.review` dan bukan laporan sendiri |
| `update-shipment-status` | `Shipment` | driver pemilik, atau punya `shipment.update_status` + `shipment.view` |

## 6. Komponen Blade

| Komponen | Props |
| --- | --- |
| `<x-app-layout>` | `title`, `pageTitle` |
| `<x-guest-layout>` | `title` |
| `<x-page-header>` | `title`, `subtitle`, `updatedAt`, slot `actions` |
| `<x-stat-card>` | `label`, `value`, `unit`, `hint`, `icon`, `tone`, `href` |
| `<x-progress-bar>` | `reported`, `verified`, `target`, `unit`, `showLegend` |
| `<x-info-row>` | `label`, `value` atau slot, `strong` |
| `<x-empty-state>` | `icon`, `title`, `message`, slot |
| `<x-theme-toggle>` | — |
| `<x-demo-banner>` | — |
| `<x-flash-messages>` | — |

Ikon memakai `<x-lucide-*>` dari `mallardduck/blade-lucide-icons`, atau
`<x-dynamic-component :component="'lucide-' . $icon">` bila nama ikon dinamis.
