# PHM One — Sistem Operasi Manufaktur PT Putra Hatim Mandiri (Next.js Edition)

Aplikasi operasional manufaktur terpadu untuk PT Putra Hatim Mandiri, dibangun menggunakan **Next.js 15 (App Router)**, **React 19**, **TypeScript**, dan **Tailwind CSS**.

---

## 🚀 Panduan Kredensial Akun Demo (Login)

Tampilan login **tidak lagi menggunakan tombol masuk cepat satu-klik** sesuai permintaan. Pengguna masuk menggunakan **Nomor Induk Karyawan (NIK)** dan **Kata Sandi (Password)**.

Berikut adalah daftar akun dummy yang dapat digunakan untuk evaluasi & demonstrasi:

| Peran (Role) | Nama Karyawan | Nomor Induk (NIK) | Kata Sandi | Halaman Utama |
| :--- | :--- | :--- | :--- | :--- |
| **Operator Produksi** | Ahmad Fauzi | `PHM-OP-001` | `operator123` | `/operator/tugas` |
| **Supervisor Produksi** | Siti Rahayu | `PHM-SPV-001` | `supervisor123` | `/supervisor/pemeriksaan` |
| **Admin Pesanan** | Dewi Kartika | `PHM-ADM-001` | `admin123` | `/pesanan` |
| **PPIC (Perencanaan)** | Agus Wijaya | `PHM-PPIC-001` | `ppic123` | `/perencanaan` |
| **Petugas Gudang** | Rudi Hartono | `PHM-GDG-001` | `gudang123` | `/pengiriman` |
| **Driver Pengiriman** | Joko Prasetyo | `PHM-DRV-001` | `driver123` | `/driver/pengiriman` |
| **Pimpinan / Manajemen** | Hartono Putra | `PHM-MGT-001` | `manajer123` | `/dashboard` |
| **Administrator Sistem** | Rizki Permana | `PHM-SYS-001` | `sysadmin123` | `/sistem/pengguna` |

> **Catatan Demo:** Tersedia juga kata sandi universal `demo1234` untuk semua akun di atas.

---

## 🛠️ Fitur Utama Aplikasi

1. **Otentikasi & Otorisasi Berbasis Peran (RBAC):**
   - Mendukung 8 peran manufaktur berbeda dengan akses menu dan halaman yang disesuaikan.
   - Fitur penggantian peran aktif (*role switcher*) untuk akun multi-role.

2. **Lini Operator Produksi:**
   - Melihat daftar tugas aktif yang dialokasikan PPIC.
   - Formulir pelaporan hasil pengerjaan mesin (`/operator/catat-hasil`) dengan pengecekan batas overproduksi dan kategori kendala teknis.
   - Riwayat pelaporan dengan status verifikasi *real-time*.

3. **Verifikasi Supervisor (QC):**
   - Antrean verifikasi laporan (`/supervisor/pemeriksaan`). Supervisor dapat menyetujui atau menolak laporan operator beserta alasannya.
   - Pencegahan *self-review* (supervisor tidak dapat memverifikasi laporannya sendiri).
   - *Rule* ketat PHM: **Dilaporkan ≠ Terverifikasi**.

4. **Pesanan & Perencanaan (PPIC):**
   - Perekaman Purchase Order mitra (`/pesanan`).
   - Penerbitan Work Order (WO) dari pesanan (`/perencanaan/work-order`).
   - Alokasi penugasan stasiun mesin dan shift operator (`/perencanaan/penugasan`).

5. **Gudang & Ekspedisi Logistik:**
   - Penerbitan Surat Jalan resmi hanya dari stok produk yang terverifikasi QC (`/pengiriman/catat`).
   - Pelacakan status pengiriman supir: *Muat Barang* → *Dalam Perjalanan* → *Terkirim* (`/driver/pengiriman`).

6. **Asisten AI Beesha (Built-in):**
   - Chatbot interaktif berbahasa Indonesia untuk menanyakan status tugas, ringkasan KPI pabrik, antrean verifikasi, dan jadwal armada pengiriman.

---

## 📦 Menjalankan di Komputer Lokal

```bash
# Instal dependensi
npm install

# Jalankan server pengembangan (port 3000)
npm run dev

# Kompilasi build produksi
npm run build

# Jalankan server produksi
npm start
```

Buka [http://localhost:3000](http://localhost:3000) di browser Anda.

---

## ☁️ Panduan Deploy ke Vercel

Proyek ini telah dikonversi secara penuh ke Next.js sehingga dapat di-deploy ke Vercel dalam 1 langkah mudah:

1. Buka [https://vercel.com/new](https://vercel.com/new)
2. Hubungkan repository GitHub Anda: `https://github.com/ALDILAHzxz/PHM-ONE`
3. Pilih framework **Next.js** (terdeteksi otomatis).
4. Klik **Deploy**.
5. Aplikasi akan aktif secara online dalam ~1 menit tanpa memerlukan setup server PHP, Apache, atau database eksternal!

---

## 🏛️ Arsip Versi Laravel

Seluruh kode sumber versi awal (Laravel 13 + Livewire 4) tetap tersimpan rapi di dalam direktori `legacy-laravel/` dan di commit history Git.
