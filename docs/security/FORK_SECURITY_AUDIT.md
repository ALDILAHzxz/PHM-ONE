# Laporan Audit Keamanan Fork Publik (Public Fork Security Audit)
## Proyek: PHM One — PT Putra Hatim Mandiri (PHM Group)
**Dokumen ID:** PHM-SEC-AUD-001  
**Klasifikasi:** Internal Confidential / Restreint  
**Tanggal Pelaksanaan:** 17 November 2026  
**Auditor:** DevSecOps & Security Engineering Team  
**Repositori Target:** `ALDILAHzxz/Prototipe-phm` & `ALDILAHzxz/PHM-ONE`  

---

## 1. Ringkasan Eksekutif (Executive Summary)

Sebelumnya, repositori prototipe sistem operasional manufaktur **PHM One** (`ALDILAHzxz/Prototipe-phm`) berstatus **Publik** di GitHub dan sempat difork oleh pengguna lain sebelum visibilitas repositori utama diubah menjadi **Privat (Private)**.

Berdasarkan arsitektur dan kebijakan keamanan GitHub (*GitHub Platform Policy & Fork Behavior*), **mengubah visibilitas repositori utama menjadi Privat TIDAK SECARA OTOMATIS menghapus, memprivat, atau membatasi akses pada fork publik yang sudah terlanjur dibuat.** Fork publik tersebut mengalami proses pelepasan (*fork detachment*) dan tetap eksis sebagai repositori independen yang dapat diakses oleh publik secara terbuka.

Audit ini dilakukan secara mendalam untuk:
1. Mengidentifikasi seluruh inventaris fork publik yang masih aktif.
2. Memeriksa riwayat komit (*commit history*) dan konten yang tersimpan pada masing-masing fork.
3. Menilai risiko paparan data rahasia (*exposure risk classification*).
4. Menyediakan prosedur mitigasi dan langkah tindak lanjut bagi pemilik repositori (*repository owner*).

---

## 2. Inventaris Fork Publik (Fork Inventory)

Audit terhadap jaringan fork repositori `ALDILAHzxz/Prototipe-phm` menemukan **2 (dua) fork publik** aktif:

| No | URL Repositori Fork | Pemilik (Owner) | Visibilitas | Komit Terakhir (HEAD) | Tanggal Komit | Deskripsi / Metadata |
|:---|:---|:---|:---|:---|:---|:---|
| 1 | `https://github.com/Aaihya/Prototipe-phm1` | `Aaihya` | **PUBLIC** | `17461be` | 6 Nov 2026 | *Tanpa deskripsi* |
| 2 | `https://github.com/mhmadrabbani/Prototipe-phm` | `mhmadrabbani` | **PUBLIC** | `8bbd237` | 6 Nov 2026 | *"PT PHM Project, UI/UX for Gen X interest use"* |

---

## 3. Analisis Riwayat Komit & Paparan Konten (Content Exposure Analysis)

### A. Analisis Fork 1: `Aaihya/Prototipe-phm1`
- **Basis Komit:** Komit awal (`17461be42fbe44a6043d657e7e0ca3a900688e23` - *"feat: initial commit BEESHA PHM Group operational prototype"*).
- **Cakupan Berkas:**
  - Kode sumber Next.js (App Router, modul `components/`, utilitas operasi).
  - Mock data operasional awal (`data/mockData.ts`).
  - Berkas konfigurasi (`package.json`, `tailwind.config.ts`, `tsconfig.json`).
- **Pemeriksaan Direktori Sensitif:**
  - Direktori `references/` **TIDAK ADA** pada komit ini.
  - Berkas PDF dokumen kerja mahasiswa / playbook **TIDAK ADA**.
  - Frame rekaman audit video **TIDAK ADA**.
- **Pemeriksaan Kredensial:**
  - Tidak ditemukan token otentikasi aktif, kredensial basis data, maupun API key hardcoded.
  - Variabel `GEMINI_API_KEY` menggunakan pemanggilan standar `process.env.GEMINI_API_KEY`.
- **Klasifikasi Risiko:** **LOW (Rendah)** — Hanya memuat kode antarmuka prototipe dan data sintetis demo awal.

---

### B. Analisis Fork 2: `mhmadrabbani/Prototipe-phm`
- **Basis Komit:** Komit ke-7 (`8bbd237` - *"feat(ui): remove sample data card, add animated dark mode toggle and revamp system walkthrough guide"*).
- **Cakupan Berkas Tambahan:**
  - Komit ini telah mencakup perubahan dari komit `d2f26d4` (*"feat(prototype): implement full Gen-X UX, operational workflow & comprehensive documentation suite"*).
  - Terdapat direktori `references/` yang berisi 3 berkas PDF dan 25 berkas frame video:
    1. `references/CATATAN TUGAS PHM GROUP.pdf` (Blob: `5953e11d3b4728598d59ece9dbeea6a01a35207f`)
    2. `references/CATATAN TUGAS PHM GROUP (1).pdf` (Blob identik: `5953e11d3b4728598d59ece9dbeea6a01a35207f`)
    3. `references/PHM_Group_Project_Execution_Playbook.pdf` (Blob: `e3b703d04a387b5acfeac725180bbfb42b5fb657`)
    4. `references/video_frames/frame_0000s.jpg` s/d `frame_0351s.jpg` (25 gambar tangkapan audit antarmuka).
- **Analisis Kedalaman Konten PDF:**
  - Berdasarkan audit metadata PDF (ReportLab Library), dokumen tersebut berjudul:
    *"PHM Group | Project Execution Playbook - UI/UX Website"*  
    *Penulis: PHM Group | Rencana Kerja Mahasiswa*  
    *Subjek: Roadmap, Double Diamond, Human-Centered Design, RACI, timeline dan QA*
  - Dokumen berisi metodologi perancangan antarmuka pengguna untuk pekerja manufaktur usia senior/Gen-X, matriks RACI proyek, dan catatan tugas studi kasus mahasiswa.
  - **TIDAK DITEMUKAN** data keuangan riil, data rahasia dagang rahasia mesin internal, formula material knalpot rahasia, atau kredensial server produksi PHM.
- **Klasifikasi Risiko:** **MEDIUM (Sedang)** — Memuat dokumen rancangan kerja proyek dan identitas mitra studi kasus (PT Astra Honda Motor, PT Toyota Motor Manufacturing Indonesia, dll.), meskipun bersifat riset akademis/prototipe.

---

## 4. Matriks Evaluasi Risiko (Risk Classification Matrix)

| Kategori Risiko | Kriteria Penilaian | Temuan pada Fork 1 | Temuan pada Fork 2 |
|:---|:---|:---:|:---:|
| **CRITICAL** | Kredensial aktif, token API, kunci privat, password basis data produksi | **TIDAK ADA** | **TIDAK ADA** |
| **HIGH** | Data keuangan perusahaan, data PII karyawan nyata, kontrak dagang resmi | **TIDAK ADA** | **TIDAK ADA** |
| **MEDIUM** | Dokumen internal proyek, metodologi kerja, playbook UI/UX, frame rekaman | **TIDAK ADA** | **TERDETEKSI** (`references/`) |
| **LOW** | Kode prototipe antarmuka, data dummy/sintetis demo manufaktur | **TERDETEKSI** | **TERDETEKSI** |

---

## 5. Batasan Kewenangan & Tindakan yang Telah Dilakukan (Actions Taken)

### Tindakan yang Berhasil Dijalankan Secara Otonom:
1. **Verifikasi Repositori Utama:** Repositori `ALDILAHzxz/Prototipe-phm` telah berhasil diverifikasi berstatus **PRIVAT (Private)** di GitHub.
2. **Audit Kloning Git:** Audit riwayat Git mendalam (*deep commit audit*) membuktikan bahwa riwayat komit tidak pernah memuat token otentikasi aktif atau rahasia sensitif.
3. **Penyusunan Laporan:** Dokumentasi lengkap atas jejak fork publik telah dikompilasi ke dalam laporan ini.

### Batasan Sistem GitHub (GitHub Security Boundaries):
Sesuai arsitektur keamanan GitHub, pengguna `ALDILAHzxz` **TIDAK DAPAT** secara sepihak:
- Menghapus (*delete*) repositori fork milik akun `Aaihya` atau `mhmadrabbani`.
- Mengubah status repositori fork milik orang lain menjadi *Private*.
- Menjalankan *force-push* ke repositori akun lain.

---

## 6. Prosedur Remediasi & Rekomendasi Tindak Lanjut (Recommended Follow-Up)

Untuk memitigasi paparan konten pada kedua fork publik tersebut, disarankan mengambil langkah-langkah berikut:

### Langkah 1: Komunikasi Kooperatif ke Pemilik Fork (Pendekatan Utama)
Kirimkan permohonan penghapusan fork secara profesional kepada pemilik akun `Aaihya` dan `mhmadrabbani` (misalnya melalui Issue GitHub atau kanal komunikasi internal tim mahasiswa).

**Draf Pesan Permohonan:**
```text
Subjek / Judul: Permohonan Penghapusan / Penyesuaian Repositori Fork Prototipe PHM

Halo [Nama Pemilik Akun],

Sehubungan dengan pembaruan tata kelola privasi proyek PT Putra Hatim Mandiri (PHM Group), 
repositori utama ALDILAHzxz/Prototipe-phm telah dialihkan menjadi Privat (Private).

Untuk menjaga kerapian repositori dan mematuhi kepatuhan keamanan data proyek, 
kami memohon bantuan Anda untuk menghapus repositori fork publik berikut dari akun GitHub Anda:
- https://github.com/[Username]/[Nama-Fork]

Apabila Anda masih memerlukan kode sumber untuk portofolio pribadi, disarankan untuk 
mengunduh arsip ZIP lokal atau mengubah visibilitas repositori fork menjadi Privat melalui 
menu Settings > General > Danger Zone > Change repository visibility > Make private.

Terima kasih banyak atas kerja sama dan dukungannya.
Salam,
Tim Proyek PHM One
```

### Langkah 2: Opsi DMCA / Intellectual Property Takedown (Jika Tidak Responsif)
Jika pemilik fork tidak merespons dalam jangka waktu tertentu dan manajemen PT Putra Hatim Mandiri menghendaki berkas `references/` segera dihilangkan dari publik, pemilik repositori utama dapat mengajukan **GitHub DMCA / Takedown Notice** resmi melalui portal GitHub Support (`https://support.github.com/contact/dmca-takedown`).

---

## 7. Status Akhir Audit

- **Status Repositori Asli:** Terlindungi (**PRIVATE**)
- **Tingkat Bahaya Keamanan Langsung:** **AMAN** (Tidak ada eksploitasi kredensial yang mungkin terjadi)
- **Tindak Lanjut Diperlukan:** Mengirimkan permintaan penghapusan kooperatif kepada `Aaihya` dan `mhmadrabbani`.
