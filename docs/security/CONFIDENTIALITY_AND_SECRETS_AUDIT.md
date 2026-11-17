# Laporan Audit Kerahasiaan Data & Pemindaian Rahasia (Secrets Audit)
## Proyek: PHM One — PT Putra Hatim Mandiri (PHM Group)
**Dokumen ID:** PHM-SEC-AUD-003  
**Klasifikasi:** Internal Confidential / Restreint  
**Tanggal Pelaksanaan:** 17 November 2026  
**Auditor:** Application Security & Code Audit Specialist  
**Cakupan Audit:** Seluruh Riwayat Git & Pohon Kerja `ALDILAHzxz/Prototipe-phm` & `ALDILAHzxz/PHM-ONE`  

---

## 1. Ringkasan Eksekutif (Executive Summary)

Audit kerahasiaan data dan pemindaian rahasia (*deep secret scanning*) dilakukan untuk memastikan bahwa repositori kode sumber **PHM One** tidak memuat:
1. Kredensial aktif, token API, kunci privat, atau kata sandi infrastruktur riil.
2. Informasi pribadi karyawan nyata (*Personally Identifiable Information* / PII) PT Putra Hatim Mandiri.
3. Rahasia dagang rahasia, formulasi teknik manufaktur rahasia, atau data finansial asli perusahaan.

Audit ini memeriksa seluruh riwayat komit dari komit pertama hingga komit terkini (*full commit tree traversal*) pada kedua repositori (`Prototipe-phm` dan `PHM-ONE`), serta memvalidasi berkas konfigurasi lokal dan bundle JavaScript sisi klien.

---

## 2. Metodologi & Pola Pemindaian Rahasia (Scanning Methodology)

Pemindaian dilakukan menggunakan skrip automasi berbasis Python dan Git CLI dengan ekspresi reguler tingkat tinggi (*high-entropy regex detection*) yang mencakup pola:

| Nama Pola | Ekspresi Reguler / Tanda Pengenal | Target Deteksi |
|:---|:---|:---|
| **GitHub Token** | `gh[pousr]_[A-Za-z0-9_]{36,255}` & `github_pat_[A-Za-z0-9_]{82}` | Personal Access Tokens, OAuth Tokens |
| **Google API Key** | `AIza[0-9A-Za-z\-_]{35}` | Google Cloud / Gemini API Keys |
| **AWS Credentials** | `AKIA[0-9A-Z]{16}` & Secret Keys | AWS IAM Access Keys |
| **Private Keys** | `-----BEGIN (?:RSA )?PRIVATE KEY-----` | Kunci Privat SSH / SSL / PGP |
| **Database URI** | `(?:postgres\|mysql\|mongodb\|redis)://` | String koneksi basis data produksi |
| **Vercel Token** | `(?:VERCEL_TOKEN\|vercel_token)\s*[:=]` | Token akses platform hosting Vercel |
| **Generic Secrets** | `(?:api_key\|secret\|password\|auth_token)` | Kunci rahasia generik bernilai tinggi |

---

## 3. Hasil Pemindaian Riwayat Git (Commit History Scan Results)

### A. Repositori `ALDILAHzxz/Prototipe-phm` (11 Komit)
- **Rentang Komit:** `17461be` (Komit Awal) s/d `8dca161` (HEAD)
- **Total Objek / Blob yang Dipindai:** 100% riwayat komit
- **Temuan Kredensial Aktif:** **0 (NIHIL)**
- **Catatan Khusus:**
  - Pemanggilan Gemini AI pada `app/api/gemini/route.ts` menggunakan variabel lingkungan aman: `const apiKey = process.env.GEMINI_API_KEY;`.
  - Berkas `README.md` hanya mencantumkan placeholder panduan: `GEMINI_API_KEY=your_gemini_api_key_here`.

### B. Repositori `ALDILAHzxz/PHM-ONE` (5 Komit)
- **Rentang Komit:** `f59752d` (Komit Awal) s/d `8e9ad37` (HEAD)
- **Total Objek / Blob yang Dipindai:** 100% riwayat komit
- **Temuan Kredensial Aktif:** **0 (NIHIL)**
- **Catatan Khusus:**
  - Kode Laravel lama yang diarsipkan ke `legacy-laravel/` tidak memuat berkas SQLite atau berkas sesi aktif.

---

## 4. Audit Berkas Konfigurasi Lingkungan (`.env*`)

Pemeriksaan status pelacakan berkas lingkungan pada pohon kerja (*working directory*) menghasilkan temuan berikut:

| Nama Berkas | Ukuran | Status Git | Isi & Evaluasi Keamanan |
|:---|:---|:---:|:---|
| `.gitignore` | 385 B | **Terekam (Tracked)** | Mengabaikan `.env*`, `.vercel`, `.next/`, `node_modules/`, `*.sqlite*` secara ketat. |
| `.env` | 2,035 B | **Diabaikan (.gitignore)** | Berkas konfigurasi Laravel lokal bawaan pengembangan; seluruh secret kosong (`BEESHA_API_KEY=`, `OIDC_CLIENT_SECRET=`). |
| `.env.local` | 1,260 B | **Diabaikan (.gitignore)** | Dibuat otomatis oleh Vercel CLI untuk linking proyek `phmoneproto`; tidak terlacak ke Git. |
| `.env.example` | 1,984 B | **Terekam (Tracked)** | Hanya memuat template tanpa nilai kredensial riil. Aman. |

---

## 5. Audit Kredensial Dummy & Identitas Pengguna

Untuk keperluan demonstrasi antarmuka dan evaluasi peran (*role-based evaluation*), sistem menyediakan kredensial dummy:

| Peran Karyawan | NIK Dummy | Password Dummy | Status Validitas Data |
|:---|:---:|:---:|:---:|
| Operator Produksi | `PHM-OP-001` | `operator123` | **100% Sintetis / Demo** |
| Supervisor Produksi | `PHM-SPV-001` | `supervisor123` | **100% Sintetis / Demo** |
| Admin Pesanan | `PHM-ADM-001` | `admin123` | **100% Sintetis / Demo** |
| Perencanaan (PPIC) | `PHM-PPIC-001` | `ppic123` | **100% Sintetis / Demo** |
| Petugas Gudang | `PHM-GDG-001` | `gudang123` | **100% Sintetis / Demo** |
| Driver Pengiriman | `PHM-DRV-001` | `driver123` | **100% Sintetis / Demo** |
| Pimpinan / Manajemen | `PHM-MGT-001` | `manajer123` | **100% Sintetis / Demo** |
| Administrator Sistem | `PHM-SYS-001` | `sysadmin123` | **100% Sintetis / Demo** |

> **Penegasan Kepatuhan:**
> - Seluruh nama (Ahmad Fauzi, Siti Rahayu, Dewi Kartika, dll.) adalah nama fiktif rekaan untuk pengujian kegunaan (*persona dummy*).
> - Seluruh NIK dan kata sandi di atas terisolasi di dalam memori statis prototipe dan **TIDAK TERHUBUNG** ke Active Directory, LDAP, atau server database internal PT Putra Hatim Mandiri.

---

## 6. Audit Data Operasional Manufaktur (Data Classification)

Berikut adalah klasifikasi data yang terdapat di dalam kode sumber aplikasi:

```text
[KLASIFIKASI DATA PROYEK PHM ONE]
├── DATA SINTETIS / DEMO (Bebas Risiko)
│   ├── Mitra Simulasi: PT Astra Honda Motor, PT Toyota Motor Manufacturing Indonesia, PT Komatsu, PT United Tractors
│   ├── Katalog Produk Dummy: Exhaust Muffler CB150R, Heat Protector Cover, Bracket Stay Exhaust
│   ├── Lini Produksi Fiktif: Mesin Bending CNC 01, Mesin Stamping Robotik, Lini Pengelasan TIG
│   └── Riwayat Mutasi Dummy: 12 laporan produksi sintetis, 6 surat jalan simulasi
│
└── DATA RAHASIA KORPORAT (Status: TIDAK DITEMUKAN / TIDAK TERPAPAR)
    ├── Laporan Keuangan Riil PHM: NIHIL
    ├── Gambar Teknik CAD / Blue-print Rahasia Produk: NIHIL
    ├── Data Gaji & Kontrak Karyawan Riil: NIHIL
    └── Kredensial Server Intranet: NIHIL
```

---

## 7. Audit Kebocoran Kode Sisi Klien (Client-Side Bundle Exposure)

Pemeriksaan terhadap berkas JavaScript hasil kompilasi Next.js (`out/` dan `.next/static/chunks/`) memastikan bahwa:
1. Tidak ada variabel lingkungan berawalan `NEXT_PUBLIC_` yang memuat rahasia server.
2. Logika asisten Beesha AI dijalankan dengan fallback aturan cerdas bawaan (*built-in intelligent heuristic engine*) di sisi klien, sehingga kueri pengguna tidak dikirimkan ke model pihak ketiga tanpa persetujuan eksplisit.
3. Bundle JavaScript bebas dari path sistem operasi lokal sensitif pengguna (`C:\xampp\htdocs\...` disanitasi saat proses kompilasi Webpack).

---

## 8. Kesimpulan & Rekomendasi Keamanan

- **Hasil Audit Keseluruhan:** **LULUS TANPA CATATAN KERENTANAN KRITIKAL (PASS / CLEAN)**.
- **Rekomendasi Sebelum Produksi Nyata:**
  1. Pertahankan `.gitignore` yang sudah terkonfigurasi dengan baik.
  2. Saat integrasi nyata dengan ERP pabrik dimulai di masa depan, gunakan alat manajemen rahasia terpusat seperti *HashiCorp Vault* atau *AWS Secrets Manager*.
  3. Terapkan rotasi kredensial berkala bagi seluruh akun administrator sistem.
