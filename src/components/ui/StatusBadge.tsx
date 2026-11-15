'use client';

import React from 'react';

interface StatusBadgeProps {
  status: string;
}

/**
 * Lencana status.
 *
 * Aturan yang ditegakkan di sini: warna merek TIDAK dipakai untuk status.
 * Sebelumnya status "sedang dikerjakan" memakai biru merek dan "siap kirim"
 * memakai indigo, sehingga warna merek dan warna keadaan tercampur dan
 * pengguna tidak bisa lagi membaca warna sebagai informasi.
 *
 * Sekarang hanya ada empat nada: aman, waspada, kritis, netral. Status yang
 * sedang berjalan memakai netral — netral berarti "belum selesai, belum
 * bermasalah", yang memang keadaan sebenarnya.
 */
export const StatusBadge: React.FC<StatusBadgeProps> = ({ status }) => {
  const key = status.toLowerCase();

  const toneClass = (() => {
    switch (key) {
      case 'disetujui':
      case 'completed':
      case 'delivered':
      case 'selesai':
        return 'phm-badge-aman';

      case 'menunggu_pemeriksaan':
      case 'pending':
      case 'draft':
        return 'phm-badge-waspada';

      case 'ditolak':
      case 'cancelled':
      case 'failed':
        return 'phm-badge-kritis';

      default:
        return 'phm-badge-netral';
    }
  })();

  const label = (() => {
    switch (key) {
      case 'menunggu_pemeriksaan':
        return 'Menunggu Pemeriksaan';
      case 'disetujui':
        return 'Disetujui';
      case 'ditolak':
        return 'Perlu Perbaikan';
      case 'in_progress':
      case 'in_production':
        return 'Sedang Dikerjakan';
      case 'ready_to_ship':
        return 'Siap Dikirim';
      case 'partially_shipped':
        return 'Sebagian Terkirim';
      case 'in_transit':
        return 'Dalam Perjalanan';
      case 'loaded':
        return 'Barang Dimuat';
      case 'delivered':
        return 'Terkirim';
      case 'completed':
        return 'Selesai';
      case 'pending':
        return 'Menunggu';
      case 'confirmed':
        return 'Terkonfirmasi';
      case 'released':
        return 'Dirilis';
      case 'cancelled':
        return 'Dibatalkan';
      case 'draft':
        return 'Draf';
      case 'shipped':
        return 'Dikirim';
      default:
        return status.replace(/_/g, ' ');
    }
  })();

  return <span className={`phm-badge ${toneClass}`}>{label}</span>;
};
