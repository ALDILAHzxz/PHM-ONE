# Ikhtisar Proyek PHM One

## 1. Konteks

PT Putra Hatim Mandiri (PHM Group) memproduksi komponen sepeda motor, termasuk
knalpot dan komponen terkait. Perusahaan telah mengembangkan atau menjajaki
beberapa sistem digital untuk pelaporan manufaktur, data operasional, informasi
terkait ERP, dan asisten Beesha AI.

## 2. Masalah yang diangkat

Dari diskusi dengan pemangku kepentingan, sebagian karyawan mengalami kesulitan
beradaptasi dengan sistem input digital yang kompleks.

Faktor yang mungkin berkontribusi — **diperlakukan sebagai hipotesis kebergunaan
dan organisasi yang perlu diuji bersama pengguna, bukan sebagai kesimpulan:**

- terlalu banyak item menu;
- terlalu banyak kartu di dashboard;
- formulir entri data yang panjang;
- memasukkan ulang informasi yang sudah diketahui perusahaan;
- terminologi yang tidak dikenal;
- navigasi yang rumit;
- kontrol yang kecil;
- umpan balik yang kurang setelah pengiriman data;
- kebiasaan pencatatan di atas kertas;
- perbedaan pengalaman dengan teknologi.

**Yang secara eksplisit tidak diasumsikan:** bahwa masalah ini disebabkan usia
atau kemalasan karyawan. Antarmuka dirancang untuk rentang pengalaman digital
yang beragam tanpa membuat stereotip pengguna berdasarkan usia.

## 3. Sasaran produk

Mengurangi usaha yang tidak perlu saat memasukkan informasi operasional.

- Seorang operator tidak perlu memahami seluruh ekosistem ERP hanya untuk
  melaporkan produksi yang sudah selesai.
- Seorang manajer tidak perlu mengumpulkan ulang informasi yang sama secara manual
  ketika catatan operasional yang berwenang sudah tersedia.
- Sistem harus meningkatkan keterjangkauan data dan transparansi pelaporan
  **tanpa** menyajikan catatan yang belum diverifikasi sebagai hasil yang sudah
  dikonfirmasi.

### Bagaimana sasaran itu diterjemahkan menjadi keputusan

| Sasaran | Keputusan konkret di aplikasi |
| --- | --- |
| Kurangi pengetikan | Form produksi punya **tepat satu** input angka. Nama produk, nomor pekerjaan, nama petugas, target, sudah tercatat, dan sisa target semuanya diambil dari identitas dan penugasan — diuji oleh `test_the_production_form_shows_known_values_as_read_only_context`. |
| Kurangi menu | Drawer hanya memuat rute milik peran aktif: operator melihat 4 entri, bukan 20. |
| Kurangi kartu dashboard | Operator tidak melihat grafik manajemen sama sekali. Pimpinan melihat tepat empat KPI utama di bagian pembuka. |
| Hilangkan istilah asing | Seluruh antarmuka Bahasa Indonesia sehari-hari: "Catat Hasil Produksi", bukan "Submit Production Actual". |
| Kontrol besar | Input 56 px, tombol 52 px, tombol ikon 44–48 px. |
| Umpan balik jelas | Langkah konfirmasi sebelum simpan, lalu halaman sukses yang menyebut jumlah, nomor laporan, dan status — hanya ditampilkan **setelah** data benar-benar tersimpan. |
| Jangan menyesatkan | "Dilaporkan" dan "Terverifikasi" selalu disajikan sebagai dua angka terpisah, di setiap layar dan di setiap jawaban Beesha. |

## 4. Janji produk

Untuk pekerja lini depan:

> Masuk sekali. Lihat tugas. Catat hasil. Selesai.

Untuk supervisor dan manajemen:

> Pantau pekerjaan. Periksa laporan. Ketahui perkembangan. Ambil keputusan.

## 5. Alur utama

```
Login → Identifikasi Peran → Tugas Harian → Pelaporan Produksi
      → Verifikasi Supervisor → Dashboard Operasional → Beesha AI
```

Alur pendukung yang juga diimplementasikan:

```
Pesanan Mitra → Perencanaan Produksi → Produksi → Quality Control
              → Pengiriman → Pemantauan Pengantaran
```

## 6. Batasan proyek yang dipatuhi

Aplikasi ini adalah prototipe dan usulan pengembangan yang dipimpin mahasiswa,
dengan arah proyek yang ditegaskan pada perbaikan UI/UX.

| Batasan | Cara dipatuhi |
| --- | --- |
| Jangan mengakses layanan rahasia perusahaan tanpa otorisasi | Tidak ada koneksi keluar ke sistem PHM. Satu-satunya integrasi eksternal opsional (penyedia model) dinonaktifkan secara bawaan. |
| Jangan memakai record perusahaan nyata sebagai data demo | Seluruh data sintetis dan dibuat untuk prototipe. Akun demo bernama fiktif, email `@phm.example`. |
| Jangan mengasumsikan integrasi ERP | Tidak ada klaim integrasi. Halaman Status Sistem menyatakannya terbuka. |
| Jangan mengarang aturan bisnis | Kebijakan overproduksi ditandai sebagai **aturan demo** di `config/phm.php` dan di dokumentasi. Tahap produksi dibuat sebagai data yang dapat dikonfigurasi, bukan alur kerja PHM yang diasumsikan. |
| Jangan mengklaim data perusahaan langsung | Banner mode demo tampil di setiap halaman terautentikasi. |
| Jangan menerbitkan data karyawan nyata | Tidak ada. |
| Jangan deploy ke lingkungan perusahaan tanpa otorisasi | Belum ada deployment apa pun. |

## 7. Apa yang berhasil dibangun

Aplikasi berjalan menyeluruh (*end-to-end*) dengan database bersama dan data
sintetis:

- **8 peran** dengan ruang kerja berbeda, 24 izin, otorisasi di sisi server.
- **Alur inti lengkap:** login demo → tugas operator → input hasil produksi →
  verifikasi supervisor → dashboard pimpinan.
- **Modul pendukung:** pesanan mitra, perencanaan PPIC (work order + penugasan),
  pengiriman gudang, ruang kerja driver, administrasi sistem.
- **Beesha AI** dengan 10 tool terotorisasi, hadir di semua halaman terautentikasi.
- **199 tes otomatis** (910 assertion) yang semuanya lulus, termasuk reproduksi
  persis skenario demonstrasi spesifikasi §37.

Lihat [`QA_REPORT.md`](QA_REPORT.md) untuk hasil pengujian dan batasan aktual.

## 8. Apa yang sengaja belum dibangun

Daripada menampilkan halaman placeholder sebagai fitur selesai, hal-hal berikut
dinyatakan belum tersedia **di dalam antarmuka itu sendiri**:

- SSO perusahaan (batas integrasi disiapkan, tidak tersambung);
- pemulihan kata sandi dan passkey/biometrik;
- pembuatan/penghapusan akun (memerlukan integrasi identitas resmi);
- pengubahan dan pembatalan pesanan;
- leaderboard karyawan (memerlukan definisi penilaian yang disetujui lebih dulu);
- unggahan bukti pengiriman berupa foto/dokumen;
- push real-time berbasis WebSocket.

## 9. Pertanyaan yang masih perlu dijawab PHM

Keputusan berikut tidak dapat diambil oleh prototipe dan sengaja dibiarkan
terbuka:

1. **Kebijakan overproduksi.** Siapa yang berwenang mencatat melebihi sisa
   target, dan apa yang terjadi pada kelebihannya?
2. **Definisi tahap produksi resmi.** Urutan dan nama tahap yang benar untuk
   setiap lini produk.
3. **Penilaian kinerja karyawan.** Definisi yang disetujui sebelum peringkat
   atau pengakuan apa pun ditampilkan — terutama karena peran dan tugas
   manufaktur yang berbeda tidak selalu dapat dibandingkan langsung.
4. **Sumber identitas dan peran.** Penyedia OIDC mana yang dipakai, dan dari
   sistem mana peran karyawan diambil.
5. **Retensi dan akses log audit.** Berapa lama disimpan, siapa yang boleh membaca.
6. **Persetujuan pengiriman data ke penyedia model.** Diperlukan sebelum Beesha
   beralih dari asisten demo ke model sungguhan.
7. **Toleransi jeda pembaruan.** Apakah polling 5 detik memadai, atau
   infrastruktur broadcast perlu disetujui.
