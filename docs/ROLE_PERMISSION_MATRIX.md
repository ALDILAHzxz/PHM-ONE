# Matriks Peran dan Izin

Sumber kebenaran: `database/seeders/RolePermissionSeeder.php` dan konstanta di
`app/Support/{Roles,Permissions}.php`. Tabel di bawah dihasilkan dari database
hasil seeding, dan diverifikasi oleh `tests/Feature/AuthorizationMatrixTest.php`
(30 tes, setiap rute diuji terhadap kedelapan peran).

## 1. Peran

| Kode | Nama tampilan | Mendarat di | Jumlah izin |
| --- | --- | --- | --- |
| `operator` | Operator Produksi | `operator.tasks` | 3 |
| `supervisor` | Supervisor Produksi / QC | `supervisor.reviews` | 5 |
| `admin_order` | Admin Pesanan | `orders.index` | 4 |
| `ppic` | PPIC | `planning.index` | 7 |
| `warehouse` | Petugas Gudang / Pengiriman | `shipments.index` | 5 |
| `driver` | Driver | `driver.shipments` | 3 |
| `management` | Pimpinan / Management | `dashboard` | 8 |
| `sysadmin` | Administrator Sistem | `system.users` | 5 |

Karyawan dapat memegang lebih dari satu peran. `role_user.is_primary`
menentukan ruang kerja awal; perpindahan dilakukan dari menu Profil dan ditolak
server bila peran tidak dimiliki.

## 2. Matriks izin

| Izin | operator | supervisor | admin_order | ppic | warehouse | driver | management | sysadmin |
| --- | :-: | :-: | :-: | :-: | :-: | :-: | :-: | :-: |
| **AI** | | | | | | | | |
| `beesha.use` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **PRODUKSI** | | | | | | | | |
| `production.report.create` | ✅ | — | — | — | — | — | — | — |
| `production.report.view_own` | ✅ | ✅ | — | — | — | — | — | — |
| `production.report.view_team` | — | ✅ | — | ✅ | — | — | ✅ | — |
| `production.report.review` | — | ✅ | — | — | — | — | — | — |
| `production.report.exception` | — | — | — | — | — | — | — | — |
| **PESANAN** | | | | | | | | |
| `order.view` | — | — | ✅ | ✅ | ✅ | — | ✅ | — |
| `order.create` | — | — | ✅ | — | — | — | — | — |
| `order.update` | — | — | ✅ | — | — | — | — | — |
| **PERENCANAAN** | | | | | | | | |
| `planning.view` | — | ✅ | — | ✅ | — | — | ✅ | — |
| `work_order.manage` | — | — | — | ✅ | — | — | — | — |
| `assignment.manage` | — | — | — | ✅ | — | — | — | — |
| **PENGIRIMAN** | | | | | | | | |
| `shipment.view` | — | — | — | ✅ | ✅ | — | ✅ | — |
| `shipment.view_own` | — | — | — | — | — | ✅ | — | — |
| `shipment.create` | — | — | — | — | ✅ | — | — | — |
| `shipment.update_status` | — | — | — | — | ✅ | ✅ | — | — |
| **MANAJEMEN** | | | | | | | | |
| `dashboard.view` | — | — | — | — | — | — | ✅ | — |
| `kpi.view` | — | — | — | — | — | — | ✅ | — |
| **SISTEM** | | | | | | | | |
| `user.manage` | — | — | — | — | — | — | — | ✅ |
| `role.manage` | — | — | — | — | — | — | — | ✅ |
| `audit.view` | — | — | — | — | — | — | ✅ | ✅ |
| `system.config` | — | — | — | — | — | — | — | ✅ |

### Catatan atas beberapa keputusan

- **`production.report.exception` tidak dimiliki peran mana pun.** Izin ini ada
  untuk kebijakan overproduksi (lihat `DATABASE_SCHEMA.md` §7) tetapi sengaja
  tidak diberikan, karena PHM belum menyetujui siapa yang boleh memakainya.
- **`production.report.create` hanya untuk operator.** Supervisor dan PPIC tidak
  dapat mencatat hasil atas nama orang lain.
- **`production.report.view_own` juga dimiliki supervisor.** Halaman "Riwayat
  Saya" tercakup pada `auth()->id()`, sehingga supervisor yang membukanya hanya
  melihat laporannya sendiri.
- **`audit.view` dimiliki pimpinan dan sysadmin.** Pimpinan memerlukannya untuk
  feed "Aktivitas Terbaru".
- **Tidak ada izin tulis untuk AI.** `beesha.use` hanya membuka asisten; seluruh
  tool bersifat baca saja (diuji oleh
  `BeeshaAssistantTest::test_no_tool_in_the_catalogue_performs_a_write`).

## 3. Izin per rute

Setiap rute dijaga di `routes/web.php`. Pengguna tanpa izin menerima **403**,
bukan pengalihan diam-diam.

| Rute | URL | Penjaga |
| --- | --- | --- |
| `login` | `/masuk` | `guest` |
| `login.help` | `/bantuan-masuk` | `guest` |
| `logout` | `POST /keluar` | `auth` |
| `home` | `/beranda` | `auth`, `active` |
| `help` | `/bantuan` | `auth`, `active` |
| `profile` | `/profil` | `auth`, `active` |
| `profile.switch-role` | `POST /profil/peran` | `auth`, `active` + cek peran dimiliki |
| `profile.password` | `PUT /profil/kata-sandi` | `auth`, `active` + cek kata sandi lama |
| `operator.tasks` | `/operator/tugas` | `production.report.create` |
| `operator.report.create` | `/operator/catat-hasil/{assignment?}` | `production.report.create` + gate `report-on-assignment` |
| `operator.history` | `/operator/riwayat` | `production.report.view_own` |
| `supervisor.reviews` | `/supervisor/pemeriksaan` | `production.report.review` |
| `supervisor.issues` | `/supervisor/kendala` | `production.report.review` |
| `supervisor.history` | `/supervisor/riwayat` | `production.report.review` |
| `orders.index` | `/pesanan` | `order.view` |
| `orders.create` | `/pesanan/tambah` | `order.create` |
| `orders.show` | `/pesanan/{order}` | `order.view` |
| `planning.index` | `/perencanaan` | `planning.view` |
| `planning.work-orders` | `/perencanaan/work-order` | `work_order.manage` |
| `planning.assignments` | `/perencanaan/penugasan` | `assignment.manage` |
| `shipments.index` | `/pengiriman` | `shipment.view` |
| `shipments.create` | `/pengiriman/catat` | `shipment.create` |
| `shipments.history` | `/pengiriman/riwayat` | `shipment.view` |
| `driver.shipments` | `/driver/pengiriman` | `role:driver` + `shipment.view_own` |
| `driver.history` | `/driver/riwayat` | `role:driver` + `shipment.view_own` |
| `dashboard` | `/dashboard` | `dashboard.view` |
| `dashboard.production` | `/dashboard/produksi` | `dashboard.view` |
| `dashboard.shipping` | `/dashboard/pengiriman` | `dashboard.view` |
| `dashboard.alerts` | `/dashboard/peringatan` | `dashboard.view` |
| `system.users` | `/sistem/pengguna` | `user.manage` |
| `system.roles` | `/sistem/peran` | `role.manage` |
| `system.audit` | `/sistem/audit` | `audit.view` |
| `system.status` | `/sistem/status` | `system.config` |

## 4. Gate tingkat record

Izin saja tidak cukup untuk keputusan yang bergantung pada record tertentu.
Didefinisikan di `app/Providers/AuthorizationServiceProvider.php`.

| Gate | Syarat |
| --- | --- |
| `report-on-assignment` | punya `production.report.create` **dan** `assignment.user_id === user.id` **dan** penugasan masih terbuka |
| `view-production-report` | penulis laporan (dengan `view_own`) **atau** punya `view_team` |
| `review-production-report` | punya `production.report.review` **dan** `report.user_id !== user.id` (tidak boleh memeriksa laporan sendiri) |
| `update-shipment-status` | driver pemilik pengiriman, **atau** punya `shipment.update_status` + `shipment.view` |

## 5. Penyaringan navigasi

`App\Support\Navigation::for($user)` membangun isi drawer berdasarkan peran
aktif, lalu **membuang** entri yang izinnya tidak dimiliki. Drawer karenanya
tidak pernah menawarkan tautan yang akan menghasilkan 403.

Ini adalah penyaringan presentasi, bukan pengaman. Pengamannya ada di middleware
dan Gate; penyaringan ini hanya mencegah pengalaman yang membingungkan.

## 6. Cara mengubah matriks

Perubahan dilakukan di `RolePermissionSeeder`, bukan lewat antarmuka, agar
tercatat dalam kontrol versi dan dapat ditinjau:

```bash
# sunting database/seeders/RolePermissionSeeder.php
php artisan db:seed --class=RolePermissionSeeder
php artisan test --filter=AuthorizationMatrixTest
```

Seeder memakai `updateOrCreate` + `sync`, sehingga aman dijalankan berulang pada
database yang sudah berisi data.
