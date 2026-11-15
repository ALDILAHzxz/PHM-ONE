# Laporan QA — PHM One

Tanggal eksekusi: **15 November 2026**
Lingkungan: Windows 11, PHP 8.4.16, Laravel 13.17, SQLite (in-memory untuk tes), Node 25.2.1

## 1. Hasil pengujian otomatis

Perintah yang dijalankan:

```bash
php artisan test
```

Keluaran:

```
tests: 199   passed: 199   failed: 0   assertions: 910   duration: ~28.6s
```

### Rincian per kelas

| Kelas tes | Tes | Assertion | Hasil |
| --- | --: | --: | --- |
| `AuthenticationTest` | 18 | 95 | ✅ lulus |
| `AuthorizationMatrixTest` | 30 | 222 | ✅ lulus |
| `ProductionReportingTest` | 24 | 78 | ✅ lulus |
| `SupervisorVerificationTest` | 12 | 42 | ✅ lulus |
| `MetricsConsistencyTest` | 16 | 62 | ✅ lulus |
| `OrderManagementTest` | 14 | 48 | ✅ lulus |
| `PlanningTest` | 13 | 25 | ✅ lulus |
| `ShippingAndDeliveryTest` | 18 | 53 | ✅ lulus |
| `BeeshaAssistantTest` | 26 | 152 | ✅ lulus |
| `InterfaceContractTest` | 28 | 133 | ✅ lulus |
| **Total** | **199** | **910** | **✅ lulus** |

Perintah verifikasi lain yang dijalankan:

| Perintah | Hasil |
| --- | --- |
| `php artisan migrate:fresh --seed` | ✅ 6 migrasi, 4 seeder, tanpa galat |
| `npm run build` | ✅ build dalam 331 ms; CSS 81.19 kB (gzip 15.21 kB) |
| `php artisan route:list --except-vendor` | ✅ 34 rute terdaftar |
| `php artisan serve` + permintaan HTTP nyata | ✅ `/masuk` 200, rute terlindungi 302 ke `/masuk`, aset 200, `livewire.js` 200 (609 kB) |

## 2. Skenario spesifikasi §37 — hasil aktual

Diuji oleh
`SupervisorVerificationTest::test_full_specification_scenario_operator_submits_then_supervisor_verifies`.

Kondisi awal setelah seeding (diverifikasi
`ProductionReportingTest::test_demo_baseline_matches_the_specification_scenario`):

| Nilai | Diharapkan | Aktual |
| --- | --- | --- |
| Produk | Knalpot Standar Motor A | ✅ sama |
| Work order | DEMO-WO-004 | ✅ sama |
| Target | 500 pcs | ✅ 500 |
| Dilaporkan | 320 pcs | ✅ 320 |
| Terverifikasi | 320 pcs | ✅ 320 |
| Sisa | 180 pcs | ✅ 180 |

Setelah operator mencatat 35 pcs:

| Pemeriksaan | Diharapkan | Aktual |
| --- | --- | --- |
| Laporan produksi baru | tepat 1 | ✅ 1 |
| Total dilaporkan operator | 355 pcs | ✅ 355 |
| Sisa target | 145 pcs | ✅ 145 |
| Status verifikasi laporan baru | menunggu pemeriksaan | ✅ `menunggu_pemeriksaan` |
| Jumlah terverifikasi | tetap 320 pcs | ✅ 320 |
| Pimpinan melihat angka dilaporkan naik | ya | ✅ KPI `reported_today` naik 35 |
| Pimpinan melihat terverifikasi tidak naik | ya | ✅ `verified_today` tidak berubah |

Setelah supervisor memverifikasi laporan 35 pcs:

| Pemeriksaan | Diharapkan | Aktual |
| --- | --- | --- |
| Jumlah terverifikasi | 355 pcs | ✅ 355 |
| Jumlah dilaporkan | tetap 355 (bukan 390) | ✅ 355 — tidak ada penghitungan ganda |
| Sisa target | 145 pcs | ✅ 145 |
| Menunggu pemeriksaan | 0 pcs | ✅ 0 |
| Jumlah record pemeriksaan | 1 | ✅ 1 |

## 3. Status pembaruan lintas sesi — pernyataan jujur

Spesifikasi §24 dan §37 meminta demonstrasi dua sesi browser. **Yang terverifikasi
dan yang tidak:**

| Hal | Status |
| --- | --- |
| Operator submit → database commit → pimpinan melihat angka baru saat membaca ulang | ✅ **Terverifikasi** oleh `test_two_sessions_see_the_same_server_state` dan `test_a_new_report_raises_the_reported_kpi_but_not_the_verified_one` |
| Dashboard membaca ulang secara otomatis pada interval terkonfigurasi | ✅ **Terverifikasi** — `wire:poll.5s` ada di markup, diuji oleh `test_the_dashboard_polls_on_the_configured_interval` |
| Setiap pembaruan menjalankan ulang kueri terotorisasi | ✅ Secara desain — komponen Livewire merender ulang lewat kueri yang sama |
| Dua jendela browser sungguhan dibuka berdampingan dan diamati secara visual | ❌ **BELUM DILAKUKAN** — tidak ada alat otomasi browser di lingkungan ini |

**Pernyataan eksplisit:** mekanismenya adalah **polling terotorisasi, bukan push
real-time.** Ada jeda hingga `PHM_POLL_SECONDS` (bawaan 5) detik. Aplikasi tidak
menyebutnya real-time; dashboard menampilkan waktu "Diperbarui" dan menyatakan
intervalnya.

Yang masih perlu dilakukan manusia: menjalankan langkah di
[`DEMO_SCRIPT.md`](DEMO_SCRIPT.md) §4 di dua jendela browser untuk mengonfirmasi
pengalaman visualnya.

## 4. Area yang diuji

| Area spesifikasi | Cakupan |
| --- | --- |
| §36 Autentikasi | Pengalihan per peran (8 peran), penolakan peran tak sah, persistensi sesi, logout, akses profil, pembatasan percobaan, akun nonaktif |
| §36 Operator | Tampilan penugasan, input jumlah, validasi (required/min/integer/negatif/desimal), field kendala kondisional, konfirmasi, submit berhasil, pencegahan submit ganda, status laporan |
| §36 Supervisor | Daftar menunggu, verifikasi, permintaan perbaikan, riwayat audit, tidak boleh memeriksa laporan sendiri, tidak boleh memeriksa dua kali |
| §36 Pesanan | Buat, cari (mitra/PO), filter status, lihat detail, PO ganda ditolak |
| §36 Pengiriman | Pengiriman sebagian, validasi jumlah, batas stok terverifikasi, pembaruan status pesanan, idempotensi |
| §36 Dashboard | Progres dilaporkan, progres terverifikasi hanya setelah persetujuan, sinkronisasi lintas sesi, konsistensi data |
| §36 Beesha AI | Tombol mengapung bertahan di 19 halaman × 7 peran, chat buka/tutup, jawaban tercakup peran, pertanyaan cepat, tidak ada paparan data tak sah |
| §36 Antarmuka | Navbar sticky, pengalihan tema, kontrol navbar ikon-saja, penempatan profil, viewport mobile, aksesibilitas keyboard, kontras mode gelap |
| §17 Perhitungan | dilaporkan = menunggu + terverifikasi; perlu_perbaikan & dibatalkan dikecualikan; sisa tidak negatif; persentase hanya bila target > 0 |
| §32 Keamanan AI | Pemeriksaan izin per tool, katalog tersaring per peran, tidak ada tool tulis, audit pemanggilan tool, tidak ada nama karyawan lain dalam jawaban operator |

## 5. Area yang TIDAK diuji otomatis

Dinyatakan terbuka sesuai spesifikasi §36 ("Jangan laporkan tes lulus bila belum
benar-benar dijalankan"):

| Area | Alasan | Mitigasi |
| --- | --- | --- |
| Rendering visual di browser sungguhan | Tidak ada alat otomasi browser (Playwright/Selenium) di lingkungan ini | `InterfaceContractTest` menegaskan markup dan CSS yang dirender: navbar sticky, ikon-saja, urutan kontrol, kelas z-index, padding, ukuran target, token tema |
| Breakpoint responsif pada perangkat fisik | Sama seperti di atas | Kelas responsif Tailwind diperiksa di markup; tidak ada tabel bergulir samping yang dipakai |
| Kontras warna terukur alat otomatis | Tidak ada pemeriksa kontras di lingkungan | Rasio dihitung manual dan didokumentasikan di `DESIGN_SYSTEM.md` §1; nilai token diuji ada di CSS |
| Perilaku papan tombol mobile | Butuh perangkat nyata | `inputmode="numeric"`, `env(safe-area-inset-bottom)`, dan posisi input di bawah panel diverifikasi di markup |
| Panggilan API Anthropic sungguhan | Tidak ada API key; pengiriman data perusahaan ke layanan luar belum diotorisasi | Jalur fallback diuji; jalur sukses **belum pernah dijalankan** |
| Pembacaan pembaca layar (NVDA/JAWS) | Tidak tersedia | Atribut ARIA, struktur heading, dan `role` diuji ada di markup |
| Beban konkurensi sungguhan | Di luar lingkup prototipe | Idempotensi diuji di level service dan dijamin constraint UNIQUE database |

## 6. Bug yang ditemukan dan diperbaiki selama pengujian

Ditemukan oleh tes, bukan oleh pembacaan kode:

| Temuan | Perbaikan |
| --- | --- |
| `LoginForm::redirectIntended()` dideklarasikan `private`, menabrak metode publik `Livewire\Component` → error 500 di halaman login | Metode duplikat dihapus; memakai `redirectIntended()` milik Livewire |
| Beesha tidak memahami "Berapa produksi yang dilaporkan **dan diverifikasi** hari ini?" — padahal itu salah satu pertanyaan cepat yang ditawarkan sendiri | Kata kunci intent diperluas (`dilaporkan`, `diverifikasi`, `terverifikasi`, `pesanan aktif`). Tes `test_every_suggested_question_produces_a_usable_answer` sekarang memastikan **setiap** chip saran untuk 6 peran benar-benar terjawab |

Tiga kegagalan tes lain yang muncul ternyata **ekspektasi tes yang salah, bukan
cacat aplikasi**, dan tes diperbaiki setelah perilaku aplikasi diverifikasi benar:

1. Supervisor dapat membuka `operator.history` — benar, karena peran itu memiliki
   `production.report.view_own` dan halaman tercakup pada `auth()->id()`.
2. Form work order menyarankan 1300, bukan 2000, untuk DEMO-PO-004 — benar,
   karena pesanan itu sudah memiliki work order 700 unit.
3. Pengecualian laporan `perlu_perbaikan` tidak terdeteksi karena laporan
   bawaan bertanggal di luar rentang hari ini; tes dibuat deterministik dengan
   membuat laporan berstatus dikecualikan pada tanggal hari ini.

## 7. Batasan yang diketahui

### 7.1 Integrasi perusahaan

| Batasan | Keterangan |
| --- | --- |
| Tidak ada koneksi ERP / INDIGO / DPR / MPR | Tidak ada integrasi dan tidak diasumsikan ada. Titik pasang didokumentasikan di `ARCHITECTURE.md` §11. |
| Tidak ada Beesha produksi | Asisten di aplikasi adalah asisten demo deterministik, dilabeli demikian di antarmuka. |
| Seluruh data sintetis | Tidak ada record karyawan, mitra, atau produksi PHM yang nyata. Banner mode demo tampil di setiap halaman terautentikasi. |

### 7.2 Autentikasi dan keamanan

| Batasan | Keterangan |
| --- | --- |
| Bukan SSO perusahaan | Autentikasi kata sandi lokal. Batas OIDC (Authorization Code + PKCE) disiapkan, belum tersambung. |
| Langkah 1 login mengungkap keberadaan nomor karyawan | Pertukaran UX yang disengaja, dimitigasi pembatasan percobaan. Dibahas di `AUTHENTICATION_DESIGN.md` §4.2. |
| Tidak ada MFA | Menjadi tanggung jawab penyedia identitas saat SSO aktif. |
| Pemulihan kata sandi belum tersedia | Dinyatakan di UI, tidak disimulasikan. |
| Passkey/biometrik belum tersedia | Dinyatakan di UI. Tidak ada sidik jari yang direkam, tidak ada biometrik palsu. |

### 7.3 Fungsional

| Batasan | Keterangan |
| --- | --- |
| Pembaruan memakai polling, bukan WebSocket | Jeda hingga 5 detik. Dinyatakan di UI dan di halaman Status Sistem. |
| Kebijakan produksi melebihi target adalah aturan demo | `reject_with_exception` di `config/phm.php`. Bukan kebijakan resmi PHM. Jumlah tidak pernah dipotong diam-diam. |
| Izin `production.report.exception` tidak dimiliki peran mana pun | Sengaja, karena PHM belum menyetujui siapa yang berhak. |
| Leaderboard karyawan belum diaktifkan | Spesifikasi §31 mewajibkan definisi penilaian yang disetujui lebih dulu. Modul progres pribadi ada (Riwayat Saya, hasil hari ini); peringkat publik tidak. |
| Belum ada tool AI untuk rentang tanggal bebas | Jawaban terbatas pada hari ini dan 5 laporan terakhir. |
| Pengelolaan akun hanya baca | Pembuatan/penghapusan akun memerlukan integrasi identitas resmi; dinyatakan di halaman Kelola Akun. |
| Belum ada unggahan bukti pengiriman berupa foto/dokumen | Hanya nama penerima yang dicatat, sesuai §22 (jangan kumpulkan data pribadi yang tidak perlu). |
| Modul Pesanan hanya buat + lihat | Pengubahan dan pembatalan pesanan belum diimplementasikan; izin `order.update` sudah ada tetapi belum punya antarmuka. |

### 7.4 Deployment

| Batasan | Keterangan |
| --- | --- |
| Belum di-deploy | Tidak ada URL produksi atau preview. Tidak ada klaim deployment. |
| Repository Git belum dibuat | Direktori kerja bukan repo Git; tidak ada commit atau push yang dilakukan. |
| Domain `phmprototype.vercel.app` tidak disentuh | Kepemilikan dan konfigurasinya belum dikonfirmasi; tidak ada deployment ke sana. |
| Vercel bukan target yang cocok lagi | Aplikasi ini Laravel/PHP, bukan Next.js. Lihat `DEPLOYMENT_GUIDE.md` untuk opsi yang sesuai. |

## 8. Tidak ada bug pemblokir yang diketahui

Pada saat laporan ini dibuat:

- Seluruh 199 tes lulus.
- 34 rute terdaftar dan dapat dijangkau peran yang berhak (diuji 8 peran × 23 rute ruang kerja).
- Build frontend berhasil tanpa peringatan selain catatan opsional `fontaine`.
- Tidak ada rute rusak, tidak ada tombol tanpa fungsi, dan tidak ada halaman
  placeholder yang disajikan sebagai fitur selesai.
- Fitur yang belum tersedia dinyatakan sebagai belum tersedia di dalam antarmuka
  itu sendiri, bukan hanya di dokumentasi (halaman Profil, Bantuan Masuk, Kelola
  Akun, dan Status Sistem).
