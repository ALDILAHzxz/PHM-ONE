# Panduan Deployment

## 0. Status saat ini

**Aplikasi ini belum di-deploy ke mana pun.** Tidak ada URL produksi, tidak ada
URL preview, dan tidak ada repository Git yang dibuat. Dokumen ini adalah
persiapan, bukan laporan deployment.

Dua hal yang perlu diluruskan dari diskusi sebelumnya:

| Hal yang pernah dibahas | Status sebenarnya |
| --- | --- |
| `ALDILAHzxz/Prototipe-phm` | Direktori kerja ini **bukan** repository Git dan tidak memiliki remote. Tidak ada commit atau push yang dilakukan. Konfirmasi kepemilikan repo sebelum menghubungkannya. |
| `https://phmprototype.vercel.app/` | Proyek ini **tidak** terhubung ke domain tersebut. Kepemilikan dan konfigurasinya belum dikonfirmasi, jadi tidak ada deployment ke sana. |
| Vercel sebagai target | Vercel ditujukan untuk Next.js. Aplikasi ini Laravel/PHP dengan proses PHP yang berjalan lama dan database relasional, sehingga Vercel bukan target yang tepat lagi. Opsi yang sesuai ada di §3. |

## 1. Menyiapkan repository Git

Belum ada repo. Bila PHM mengotorisasi:

```bash
cd c:/xampp/htdocs/PHM-ONE
git init
git branch -M main
git add .
git commit -m "PHM One MVP: operator, supervisor, pesanan, pengiriman, dashboard, Beesha AI"
```

`.gitignore` bawaan Laravel sudah mengecualikan `.env`, `/vendor`,
`/node_modules`, `/storage/*.key`, dan `/public/build`.

**Periksa sebelum push:**

```bash
# pastikan .env tidak akan ikut
git check-ignore -v .env

# pastikan database demo tidak ikut bila berisi apa pun yang tidak ingin dibagikan
git check-ignore -v database/database.sqlite
```

> `database/database.sqlite` **tidak** diabaikan `.gitignore` Laravel bawaan.
> Berkas ini hanya memuat data sintetis, tetapi pertimbangkan menambahkannya ke
> `.gitignore` agar setiap lingkungan melakukan seed sendiri.

Jangan force-push dan jangan melewati pemeriksaan yang diwajibkan.

## 2. Persyaratan runtime

| Komponen | Minimum | Catatan |
| --- | --- | --- |
| PHP | 8.3 | diuji pada 8.4.16 |
| Ekstensi PHP | `pdo_sqlite` + `sqlite3` (atau `pdo_mysql` / `pdo_pgsql`), `mbstring`, `openssl`, `fileinfo`, `ctype`, `json`, `tokenizer`, `xml` | |
| Composer | 2.x | |
| Node | 20+ | hanya untuk build aset; tidak diperlukan saat runtime |
| Penyimpanan | dapat ditulis: `storage/`, `bootstrap/cache/` | |
| Proses | satu proses PHP-FPM / php-cli biasa | **tidak** memerlukan proses WebSocket |

Tidak ada worker queue yang dibutuhkan: seluruh operasi berjalan sinkron di dalam
request. Tidak ada scheduler yang dibutuhkan.

## 3. Opsi hosting yang sesuai

| Opsi | Kecocokan | Catatan |
| --- | --- | --- |
| VPS + Nginx/Apache + PHP-FPM | ✅ terbaik | Kontrol penuh; SQLite atau MySQL/PostgreSQL |
| Laravel Forge / Ploi | ✅ | Otomatisasi penyediaan untuk VPS |
| Laravel Cloud | ✅ | Platform resmi Laravel |
| Shared hosting dengan PHP 8.3+ | 🟡 | Perlu document root diarahkan ke `public/`, dan ekstensi yang dibutuhkan aktif |
| XAMPP lokal (demo di tempat) | ✅ | Arahkan VirtualHost ke `public/`; lihat §7 |
| Vercel | ❌ | Ditujukan untuk Next.js, bukan Laravel |

Document root **wajib** diarahkan ke `public/`, bukan ke akar proyek.

## 4. Konfigurasi lingkungan produksi

```env
APP_NAME="PHM One"
APP_ENV=production
APP_KEY=                      # php artisan key:generate
APP_DEBUG=false
APP_URL=https://<domain-yang-diotorisasi>
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_FALLBACK_LOCALE=id

# Database — pilih salah satu
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=phm_one
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=sync

# PHM One
PHM_DEMO_MODE=false           # WAJIB false di lingkungan nyata
PHM_POLL_SECONDS=5

BEESHA_PROVIDER=demo          # ubah hanya setelah persetujuan tertulis
BEESHA_API_KEY=
BEESHA_MODEL=claude-haiku-5-5

OIDC_ENABLED=false
OIDC_ISSUER_URL=
OIDC_CLIENT_ID=
OIDC_CLIENT_SECRET=
OIDC_REDIRECT_URI=
```

**Jangan pernah commit nilai rahasia.** `APP_KEY`, kredensial database,
`BEESHA_API_KEY`, dan `OIDC_CLIENT_SECRET` diisi lewat variabel lingkungan di
server, bukan di dalam repo.

## 5. Langkah deployment

```bash
# 1. Ambil kode
git clone <repo> /path/ke/phm-one && cd /path/ke/phm-one

# 2. Dependensi produksi
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# 3. Lingkungan
cp .env.example .env     # lalu isi nilai nyata
php artisan key:generate

# 4. Database
php artisan migrate --force

# 5. Data awal — HANYA peran dan izin, BUKAN akun demo
php artisan db:seed --class=RolePermissionSeeder --force

# 6. Optimalkan
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Izin penyimpanan (Linux)
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### Perhatian soal seeder

| Seeder | Produksi? | Alasan |
| --- | --- | --- |
| `RolePermissionSeeder` | ✅ jalankan | Peran dan izin adalah konfigurasi aplikasi |
| `CatalogSeeder` | 🟡 pertimbangkan | Departemen dan shift mungkin dipakai, tetapi mitra dan produk adalah **data sintetis** |
| `DemoUserSeeder` | ❌ **jangan** | Akun fiktif dengan kata sandi yang diketahui publik |
| `DemoOperationsSeeder` | ❌ **jangan** | Pesanan, laporan, dan pengiriman sintetis |

`DatabaseSeeder` memanggil keempatnya, jadi **jangan** jalankan `db:seed` tanpa
`--class` di lingkungan nyata.

## 6. Migrasi database yang aman

- `php artisan migrate --force` bersifat aditif; tidak ada migrasi yang
  menghapus kolom.
- **Jangan pernah** menjalankan `migrate:fresh` di lingkungan dengan data nyata —
  perintah itu menghapus seluruh tabel.
- Ambil backup sebelum migrasi apa pun di lingkungan dengan data nyata.
- Skema tidak memakai tipe khusus SQLite, sehingga migrasi berjalan di
  MySQL/MariaDB dan PostgreSQL tanpa perubahan.

## 7. Menjalankan di XAMPP (demo lokal)

Tambahkan VirtualHost di `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName phm-one.local
    DocumentRoot "C:/xampp/htdocs/PHM-ONE/public"
    <Directory "C:/xampp/htdocs/PHM-ONE/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Tambahkan ke `C:\Windows\System32\drivers\etc\hosts`:

```
127.0.0.1  phm-one.local
```

Pastikan `pdo_sqlite` dan `sqlite3` aktif di `C:\xampp\php\php.ini`, lalu
restart Apache.

Alternatif tanpa konfigurasi Apache: `php artisan serve` pada port 8000.

## 8. Daftar periksa sebelum go-live

### Keamanan
- [ ] `APP_DEBUG=false` dan `APP_ENV=production`
- [ ] `APP_KEY` dibuat baru di server dan tidak pernah di-commit
- [ ] `PHM_DEMO_MODE=false`
- [ ] Seluruh akun `is_demo = true` dihapus atau dinonaktifkan
- [ ] HTTPS dipaksa; `SESSION_SECURE_COOKIE=true`
- [ ] Kredensial database bukan `root` tanpa kata sandi
- [ ] `.env` tidak dapat diakses lewat web (document root di `public/`)
- [ ] `BEESHA_API_KEY` kosong, kecuali pengiriman data ke penyedia model sudah
      disetujui secara tertulis
- [ ] Kebijakan retensi log audit disetujui

### Fungsional
- [ ] `php artisan migrate --force` berhasil
- [ ] `php artisan route:list` menampilkan 34 rute
- [ ] `npm run build` menghasilkan `public/build/manifest.json`
- [ ] Halaman login dapat dibuka dan asetnya dimuat
- [ ] Minimal satu akun nyata per peran sudah dibuat dengan peran yang benar
- [ ] Halaman Status Sistem mencerminkan konfigurasi yang benar

### Verifikasi lingkungan
- [ ] `php artisan test` dijalankan di staging dan lulus
- [ ] Pengujian dua sesi browser (lihat `DEMO_SCRIPT.md` §4) dilakukan manual
- [ ] Zona waktu server `Asia/Jakarta` agar tanggal laporan benar

### Sebelum menyebut sesuatu "selesai"
- [ ] URL deployment diverifikasi benar-benar dapat dibuka
- [ ] Tidak ada klaim integrasi ERP/INDIGO/Beesha produksi di materi apa pun
- [ ] Batasan di `QA_REPORT.md` §7 disampaikan ke pemangku kepentingan

## 9. Pemeliharaan

```bash
# setelah mengubah kode di server
php artisan config:clear && php artisan config:cache
php artisan route:clear && php artisan route:cache
php artisan view:clear && php artisan view:cache

# memeriksa kesehatan
curl -s -o /dev/null -w "%{http_code}" https://<domain>/up    # harus 200

# log aplikasi
tail -f storage/logs/laravel.log
```

Endpoint `/up` disediakan Laravel untuk health check dan tidak memerlukan
autentikasi.

## 10. Rollback

Karena tidak ada migrasi yang destruktif, rollback kode biasanya cukup:

```bash
git checkout <commit-sebelumnya>
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Bila sebuah migrasi perlu dibatalkan, setiap migrasi memiliki `down()` yang
benar. Tetap ambil backup database lebih dulu.
