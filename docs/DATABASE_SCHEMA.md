# Skema Database PHM One

Database: SQLite pada prototipe. Skema sengaja portabel — tidak ada tipe kolom
khusus SQLite — sehingga `migrate:fresh --seed` berjalan sama di MySQL/MariaDB
dan PostgreSQL.

## 1. Diagram relasi

```
departments ──┐
work_shifts ──┼──< users >──┬──< role_user >── roles ──< permission_role >── permissions
              │             │
              │             ├──< production_assignments >── work_orders ──┐
              │             │          │                                  │
              │             │          └──< production_reports >──< production_reviews
              │             │
              │             └──< shipments (driver_id) >──< delivery_status_events
              │
partners ──< customer_orders >──┬──< work_orders
products ──┘                     └──< shipments

production_stages ──┐
work_stations ──────┴──> production_assignments (opsional)

audit_events   (actor_id → users)
beesha_messages (user_id → users)
```

## 2. Tabel

### `departments`
| Kolom | Tipe | Catatan |
| --- | --- | --- |
| id | PK | |
| code | string(32) UNIQUE | `PROD`, `QC`, `PPIC`, … |
| name | string | |

### `work_shifts`
| Kolom | Tipe | Catatan |
| --- | --- | --- |
| id | PK | |
| code | string(32) UNIQUE | `S1`, `S2`, `S3` |
| name | string | |
| start_time / end_time | time | |
| is_active | bool | |

### `users`
Identitas aplikasi **dan** record karyawan (lihat
[ARCHITECTURE §9](ARCHITECTURE.md#9-penyimpangan-dari-spesifikasi)).

| Kolom | Tipe | Catatan |
| --- | --- | --- |
| id | PK | |
| employee_number | string(32) UNIQUE | **Nomor Induk Karyawan.** Identifier internal perusahaan, bukan NIK Dukcapil. |
| full_name | string | |
| email | string NULL UNIQUE | opsional; banyak operator tidak punya email kerja |
| phone | string(32) NULL | |
| password | string | hash bcrypt (cast `hashed`) |
| department_id | FK NULL | `nullOnDelete` |
| work_shift_id | FK NULL | `nullOnDelete` |
| position | string NULL | Jabatan |
| work_location | string NULL | Lokasi kerja |
| status | string(24), index | `active` \| `suspended` \| `inactive`. Hanya `active` boleh login. |
| is_demo | bool | Menandai identitas demo. Login demo **hanya** menyentuh baris ini. |
| last_login_at | timestamp NULL | |

### `roles`
| Kolom | Tipe | Catatan |
| --- | --- | --- |
| code | string(48) UNIQUE | `operator`, `supervisor`, … |
| name | string | nama tampilan Bahasa Indonesia |
| description | string NULL | |
| default_route | string(64) | nama rute tujuan setelah login |
| sort_order | smallint | |

### `permissions`
| Kolom | Tipe | Catatan |
| --- | --- | --- |
| code | string(96) UNIQUE | `production.report.create`, … |
| name | string | |
| group | string(48) | `produksi`, `pesanan`, `pengiriman`, `manajemen`, `sistem`, `ai` |

### `permission_role`, `role_user`
Tabel pivot. `role_user.is_primary` menentukan ruang kerja awal karyawan
multi-peran. Keduanya memakai UNIQUE gabungan agar tidak ada duplikasi.

### `partners`
`code` UNIQUE, `name`, `contact_person`, `phone`, `address`, `is_active`.

### `products`
`sku` UNIQUE, `name`, `unit` (default `pcs`), `description`, `is_active`.

### `production_stages`
`code` UNIQUE, `name`, `sequence`, `is_active`.

**Tahap produksi adalah data, bukan logika.** PHM dapat menambah, mengurutkan
ulang, atau menonaktifkan tahap tanpa perubahan kode. Diuji oleh
`PlanningTest::test_production_stages_are_configurable_data_not_hardcoded`.

### `work_stations`
`code` UNIQUE, `name`, `is_active`.

### `customer_orders`
| Kolom | Tipe | Catatan |
| --- | --- | --- |
| order_number | string(48) UNIQUE | dibuat `DocumentNumbering` (`PO-YYYYMMDD-0001`) |
| partner_id | FK | `restrictOnDelete` |
| po_number | string(64) | nomor PO dari mitra |
| product_id | FK | `restrictOnDelete` |
| quantity | uint | |
| delivery_target_date | date | |
| status | string(32) | `baru` \| `diproses` \| `siap_kirim` \| `sebagian_terkirim` \| `selesai` \| `dibatalkan` |
| notes | text NULL | |
| created_by | FK NULL users | |

Index: UNIQUE `(partner_id, po_number)` — satu nomor PO tidak boleh dicatat dua
kali untuk mitra yang sama, tetapi mitra berbeda boleh memakai nomor yang sama.
Index gabungan `(status, delivery_target_date)` untuk kueri tenggat.

### `work_orders`
`wo_number` UNIQUE, `customer_order_id` FK NULL, `product_id` FK,
`target_quantity` uint, `planned_date` date,
`status` ∈ `direncanakan` \| `dirilis` \| `berjalan` \| `selesai` \| `ditutup`,
`created_by` FK NULL. Index `(status, planned_date)`.

### `production_assignments`
| Kolom | Tipe | Catatan |
| --- | --- | --- |
| work_order_id | FK | `cascadeOnDelete` |
| user_id | FK | `restrictOnDelete` — operator |
| work_shift_id / production_stage_id / work_station_id | FK NULL | |
| assigned_quantity | uint | target untuk operator ini |
| planned_date | date | |
| status | string(32) | `ditugaskan` \| `berjalan` \| `selesai` \| `ditutup` \| `dibatalkan` |
| created_by | FK NULL | PPIC yang menugaskan |

Index: `(user_id, planned_date, status)` untuk "Tugas Saya";
`(work_order_id, status)` untuk agregasi work order.

### `production_reports`
| Kolom | Tipe | Catatan |
| --- | --- | --- |
| report_number | string(48) UNIQUE | `LP-YYYYMMDD-0001` |
| production_assignment_id | FK | `restrictOnDelete` — laporan tidak hilang bila penugasan diubah |
| user_id | FK | `restrictOnDelete` |
| quantity_completed | uint | **tambahan** unit pada pencatatan ini, bukan total |
| has_issue | bool | |
| issue_category | string(32) NULL | `mesin` \| `material` \| `kualitas` \| `lainnya` |
| issue_description | text NULL | wajib bila `has_issue` |
| additional_notes | text NULL | |
| status | string(32) | lihat §3 |
| is_exception | bool | true bila diterima melebihi sisa target lewat izin pengecualian |
| submitted_at | timestamp | |
| idempotency_key | string(64) **UNIQUE** | mencegah kiriman ganda |

Index: `(production_assignment_id, status)`, `(status, submitted_at)`,
`(user_id, submitted_at)`.

### `production_reviews`
`production_report_id` FK cascade, `reviewed_by` FK users restrict,
`status` ∈ `terverifikasi` \| `perlu_perbaikan`, `review_notes` text NULL,
`reviewed_at` timestamp.

Pemeriksaan adalah **record terpisah**. Laporan asli tidak pernah diubah isinya
atau dihapus, sehingga riwayat pengiriman data tetap utuh.

### `shipments`
| Kolom | Tipe | Catatan |
| --- | --- | --- |
| shipment_number | string(48) UNIQUE | `SJ-YYYYMMDD-0001` |
| customer_order_id | FK | `restrictOnDelete` |
| quantity_shipped | uint | |
| delivery_note_number | string(64) NULL | nomor surat jalan |
| driver_id | FK NULL users | |
| destination | string NULL | disalin dari alamat mitra saat pencatatan |
| status | string(32) | lihat §4 |
| notes | text NULL | |
| shipped_at / delivered_at | timestamp NULL | |
| recorded_by | FK NULL users | |
| idempotency_key | string(64) NULL UNIQUE | |

### `delivery_status_events`
`shipment_id` FK cascade, `status`, `actor_id` FK NULL,
`recipient_name` string NULL (bukti penerimaan minimal), `notes` text NULL,
`occurred_at` timestamp.

Riwayat perjalanan bersifat append-only. Tidak ada pelacakan lokasi kontinu dan
tidak ada data pribadi di luar nama penerima yang diisi driver.

### `audit_events`
`actor_id` FK NULL, `action` string(64), `entity_type` string(64),
`entity_id` ubigint NULL, `summary` string NULL (Bahasa Indonesia),
`metadata` json NULL, `ip_address` string(45) NULL,
`is_visible_feed` bool.

`is_visible_feed = false` untuk kejadian yang tidak perlu muncul di feed
"Aktivitas Terbaru" (login, logout, pemanggilan tool AI) tetapi tetap terekam
untuk audit.

### `beesha_messages`
`user_id` FK cascade, `conversation_id` string(64) index, `author` ∈ `user` \|
`assistant`, `content` text, `tool_used` string(64) NULL,
`provider` string(32) NULL, `data_context` json NULL.

`tool_used` dan `provider` disimpan agar pengguna selalu dapat melihat apakah
sebuah jawaban bersandar pada record resmi atau tidak.

## 3. Aturan penghitungan produksi (otoritatif)

Didefinisikan sekali di `App\Support\ReportStatus`:

```php
COUNTS_AS_REPORTED = ['menunggu_pemeriksaan', 'terverifikasi'];
COUNTS_AS_VERIFIED = ['terverifikasi'];
```

| Status | Arti | Dilaporkan | Terverifikasi |
| --- | --- | --- | --- |
| `menunggu_pemeriksaan` | tersimpan, belum diperiksa | ✅ | ❌ |
| `terverifikasi` | disetujui supervisor | ✅ | ✅ |
| `perlu_perbaikan` | harus dicatat ulang | ❌ | ❌ |
| `dibatalkan` | dibatalkan dengan otorisasi | ❌ | ❌ |

Rumus:

```
reportedQty  = Σ quantity_completed  WHERE status ∈ COUNTS_AS_REPORTED
verifiedQty  = Σ quantity_completed  WHERE status ∈ COUNTS_AS_VERIFIED
pendingQty   = Σ quantity_completed  WHERE status = 'menunggu_pemeriksaan'
remainingQty = max(0, targetQty − reportedQty)

reportedProgress = targetQty > 0 ? reportedQty / targetQty × 100 : 0
verifiedProgress = targetQty > 0 ? verifiedQty / targetQty × 100 : 0
```

**Tidak ada kolom total yang disimpan.** Semuanya diturunkan saat dibaca, jadi:

- memverifikasi laporan memindahkan jumlah dari "menunggu" ke "terverifikasi"
  tanpa mengubah "dilaporkan" — perhitungan ganda tidak mungkin;
- laporan `perlu_perbaikan` keluar dari total tetapi barisnya tetap ada dan
  tetap terlihat di daftar perbaikan — tidak ada yang hilang diam-diam;
- persentase hanya dihitung bila target > 0.

Contoh (skenario spesifikasi §37):

| Kejadian | target | dilaporkan | terverifikasi | sisa | menunggu |
| --- | --- | --- | --- | --- | --- |
| Awal | 500 | 320 | 320 | 180 | 0 |
| Operator catat 35 | 500 | **355** | 320 | **145** | **35** |
| Supervisor verifikasi | 500 | 355 | **355** | 145 | **0** |

## 4. Transisi status pengiriman

Didefinisikan di `App\Support\ShipmentStatus::TRANSISIONS`; transisi di luar
daftar ini ditolak server.

```
disiapkan ──┬──> dikirim ──> tiba ──> diterima (akhir)
            ├──> kendala
            └──> dibatalkan (akhir)

kendala ──┬──> dikirim
          ├──> tiba
          └──> dibatalkan

diterima  → (tidak ada transisi)
```

`COUNTS_AS_SHIPPED = ['dikirim', 'tiba', 'diterima']` — hanya status ini yang
dihitung sebagai barang yang sudah keluar dari pabrik.

## 5. Aturan "siap kirim"

```
verifiedProduced = Σ laporan terverifikasi pada work order milik pesanan ini
shipped          = Σ quantity_shipped pengiriman berstatus COUNTS_AS_SHIPPED
readyToShip      = max(0, min(orderQuantity, verifiedProduced) − shipped)
```

Hanya produksi **terverifikasi** yang dianggap barang yang ada. Laporan yang
masih menunggu pemeriksaan tidak dapat dikirim — diuji oleh
`ShippingAndDeliveryTest::test_shipping_more_than_the_verified_stock_is_refused`.

## 6. Integritas dan konkurensi

- Foreign key di seluruh relasi; `restrictOnDelete` pada record historis
  (laporan, pengiriman, pesanan) agar riwayat tidak bisa terhapus beruntun.
- `UNIQUE` pada `idempotency_key` di `production_reports` dan `shipments` —
  kiriman ganda ditolak di level database, bukan hanya di aplikasi.
- `UNIQUE (partner_id, po_number)` mencegah pesanan ganda.
- Mutasi berjalan dalam `DB::transaction` bersama audit event-nya.
- `lockForUpdate()` dipakai saat membaca ulang penugasan/pesanan/pengiriman di
  dalam transaksi sebelum memeriksa batas kuantitas.

> **Catatan SQLite:** SQLite menserialkan transaksi tulis, sehingga
> `lockForUpdate()` menjadi no-op di sana namun tetap aman. Pada MySQL/PostgreSQL
> klausa tersebut memberi penguncian baris yang sesungguhnya. Batas kuantitas
> karenanya tetap benar di kedua lingkungan.

## 7. Aturan demo yang terdokumentasi: produksi melebihi target

PHM **belum** mengonfirmasi kebijakan overproduksi. Prototipe memakai aturan demo
di `config/phm.php`:

```php
'overproduction' => ['policy' => 'reject_with_exception', 'tolerance_units' => 0],
```

- Jumlah yang melebihi sisa target **ditolak** dengan pesan yang menyebut angka
  sisa dan angka yang dimasukkan.
- Jumlah yang dikirim **tidak pernah dipotong diam-diam**.
- Pengguna yang memiliki izin `production.report.exception` dapat mencatat
  melebihi sisa; laporan ditandai `is_exception = true` dan ditandai khusus untuk
  supervisor.

Tidak ada peran bawaan yang memiliki izin tersebut. Ini aturan prototipe, bukan
kebijakan resmi PHM.
