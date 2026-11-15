'use client';

import React from 'react';

interface StatusBadgeProps {
  status: string;
}

export const StatusBadge: React.FC<StatusBadgeProps> = ({ status }) => {
  const getBadgeStyle = (val: string) => {
    switch (val.toLowerCase()) {
      case 'disetujui':
      case 'completed':
      case 'delivered':
        return 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800';
      case 'menunggu_pemeriksaan':
      case 'pending':
      case 'draft':
        return 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800';
      case 'ditolak':
      case 'cancelled':
      case 'failed':
        return 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800';
      case 'in_progress':
      case 'in_production':
      case 'in_transit':
      case 'loaded':
      case 'released':
        return 'bg-blue-50 dark:bg-blue-950/40 text-[#0077C8] dark:text-blue-300 border-blue-200 dark:border-blue-800';
      case 'ready_to_ship':
      case 'partially_shipped':
        return 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800';
      default:
        return 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700';
    }
  };

  const formatLabel = (val: string) => {
    switch (val.toLowerCase()) {
      case 'menunggu_pemeriksaan':
        return 'Menunggu Pemeriksaan';
      case 'disetujui':
        return 'Disetujui';
      case 'ditolak':
        return 'Ditolak';
      case 'in_progress':
      case 'in_production':
        return 'Sedang Dikerjakan';
      case 'ready_to_ship':
        return 'Siap Dikirim';
      case 'in_transit':
        return 'Dalam Perjalanan';
      case 'delivered':
        return 'Terkirim';
      case 'completed':
        return 'Selesai';
      case 'pending':
        return 'Tertunda';
      case 'confirmed':
        return 'Terkonfirmasi';
      default:
        return val.replace(/_/g, ' ').toUpperCase();
    }
  };

  return (
    <span
      className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border ${getBadgeStyle(
        status
      )}`}
    >
      {formatLabel(status)}
    </span>
  );
};
