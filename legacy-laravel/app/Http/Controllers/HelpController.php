<?php

namespace App\Http\Controllers;

use App\Support\Roles;
use Illuminate\View\View;

class HelpController extends Controller
{
    public function __invoke(): View
    {
        $role = request()->user()->activeRole()?->code;

        // Role-specific "how do I do my job" steps, in plain Indonesian.
        $guides = match ($role) {
            Roles::OPERATOR => [
                ['title' => 'Mencatat hasil produksi', 'steps' => [
                    'Buka halaman Tugas Saya.',
                    'Tekan tombol Catat Hasil Kerja pada pekerjaan yang Anda kerjakan.',
                    'Isi jumlah unit yang baru selesai pada pencatatan ini.',
                    'Pilih "Ada kendala" hanya jika memang ada masalah, lalu jelaskan.',
                    'Tekan Periksa & Simpan Hasil, periksa ringkasannya, lalu tekan Ya, Simpan Hasil.',
                ]],
                ['title' => 'Kalau jumlahnya salah input', 'steps' => [
                    'Laporan yang sudah tersimpan tidak bisa Anda hapus sendiri.',
                    'Beri tahu atasan agar laporan ditandai Perlu Perbaikan.',
                    'Setelah itu catat ulang hasil yang benar dari halaman Riwayat Saya.',
                ]],
                ['title' => 'Arti status laporan', 'steps' => [
                    'Menunggu Pemeriksaan — sudah tersimpan, belum diperiksa atasan.',
                    'Terverifikasi — sudah diperiksa dan dihitung sebagai hasil resmi.',
                    'Perlu Perbaikan — ada yang harus dicatat ulang.',
                ]],
            ],
            Roles::SUPERVISOR => [
                ['title' => 'Memeriksa laporan tim', 'steps' => [
                    'Buka halaman Pemeriksaan.',
                    'Baca jumlah, operator, dan kendala pada setiap kartu laporan.',
                    'Tekan Verifikasi bila sudah sesuai.',
                    'Tekan Perlu Perbaikan dan tulis alasannya bila belum sesuai.',
                ]],
                ['title' => 'Catatan penting', 'steps' => [
                    'Jumlah terverifikasi hanya bertambah setelah Anda menekan Verifikasi.',
                    'Anda tidak dapat memeriksa laporan yang Anda buat sendiri.',
                    'Laporan Perlu Perbaikan tidak dihitung ke total dilaporkan, tetapi tetap tersimpan.',
                ]],
            ],
            Roles::ADMIN_ORDER => [
                ['title' => 'Menambah pesanan mitra', 'steps' => [
                    'Buka halaman Pesanan Mitra, lalu tekan Tambah Pesanan.',
                    'Pilih mitra dan barang dari daftar yang tersedia.',
                    'Isi nomor PO, jumlah pesanan, dan target pengiriman.',
                    'Periksa ringkasan, lalu simpan.',
                ]],
            ],
            Roles::PPIC => [
                ['title' => 'Merencanakan produksi', 'steps' => [
                    'Buka Rencana Produksi untuk melihat pesanan yang perlu dikerjakan.',
                    'Buat Work Order dari pesanan tersebut.',
                    'Bagi work order menjadi penugasan per operator di halaman Penugasan.',
                ]],
            ],
            Roles::WAREHOUSE => [
                ['title' => 'Mencatat pengiriman', 'steps' => [
                    'Buka Catat Pengiriman.',
                    'Pilih pesanan yang barangnya sudah siap (sudah terverifikasi).',
                    'Isi jumlah yang dikirim, nomor surat jalan, dan driver.',
                    'Simpan, lalu pantau statusnya di Pengiriman Hari Ini.',
                ]],
            ],
            Roles::DRIVER => [
                ['title' => 'Memperbarui status perjalanan', 'steps' => [
                    'Buka Pengiriman Saya.',
                    'Tekan Mulai Pengiriman saat Anda berangkat.',
                    'Tekan Sudah Tiba saat sampai di tujuan.',
                    'Tekan Barang Diterima dan isi nama penerima.',
                    'Gunakan Laporkan Kendala bila ada masalah di jalan.',
                ]],
            ],
            Roles::MANAGEMENT => [
                ['title' => 'Membaca angka di dashboard', 'steps' => [
                    'Produksi Dilaporkan — jumlah yang dicatat operator, termasuk yang belum diperiksa.',
                    'Produksi Terverifikasi — jumlah yang sudah dikonfirmasi supervisor.',
                    'Selalu perhatikan waktu "Diperbarui" di bagian atas halaman.',
                ]],
            ],
            Roles::SYSADMIN => [
                ['title' => 'Mengelola akses', 'steps' => [
                    'Kelola Akun untuk melihat karyawan dan peran mereka.',
                    'Kelola Peran untuk melihat izin yang dimiliki setiap peran.',
                    'Audit Aktivitas untuk melacak siapa melakukan apa.',
                ]],
            ],
            default => [],
        };

        return view('help', ['guides' => $guides]);
    }
}
