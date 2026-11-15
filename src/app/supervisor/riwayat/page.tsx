'use client';

import React from 'react';
import { AppLayout } from '../../../components/layout/AppLayout';
import { PageHeader } from '../../../components/ui/PageHeader';
import { StatusBadge } from '../../../components/ui/StatusBadge';
import { useOperations } from '../../../context/OperationsContext';

export default function SupervisorHistoryPage() {
  const { reports } = useOperations();
  const reviewedReports = reports.filter((r) => r.status !== 'menunggu_pemeriksaan');

  return (
    <AppLayout>
      <PageHeader
        title="Riwayat Verifikasi Supervisor"
        description="Arsip seluruh keputusan inspeksi kualitas yang telah disetujui maupun ditolak oleh supervisor."
      />

      <div className="phm-card overflow-hidden p-0">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 font-bold uppercase tracking-wider text-[0.6875rem]">
              <tr>
                <th className="py-3 px-4">ID</th>
                <th className="py-3 px-4">Komponen & WO</th>
                <th className="py-3 px-4">Operator</th>
                <th className="py-3 px-4 text-center">Jumlah</th>
                <th className="py-3 px-4">Keputusan</th>
                <th className="py-3 px-4">Catatan Verifikasi</th>
                <th className="py-3 px-4">Pemeriksa</th>
                <th className="py-3 px-4 text-right">Waktu Review</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {reviewedReports.length === 0 ? (
                <tr>
                  <td colSpan={8} className="py-8 text-center text-slate-400">
                    Belum ada riwayat verifikasi.
                  </td>
                </tr>
              ) : (
                reviewedReports.map((rep) => (
                  <tr key={rep.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                    <td className="py-3.5 px-4 font-mono font-bold text-slate-700 dark:text-slate-300">
                      #{rep.id}
                    </td>
                    <td className="py-3.5 px-4">
                      <div className="font-bold text-slate-900 dark:text-white">{rep.product_name}</div>
                      <div className="text-[0.7rem] text-[#0077C8] font-mono">{rep.work_order_number}</div>
                    </td>
                    <td className="py-3.5 px-4 font-semibold text-slate-700 dark:text-slate-300">
                      {rep.reported_by_name}
                    </td>
                    <td className="py-3.5 px-4 text-center font-extrabold text-sm">
                      {rep.quantity} pcs
                    </td>
                    <td className="py-3.5 px-4">
                      <StatusBadge status={rep.status} />
                    </td>
                    <td className="py-3.5 px-4 max-w-xs text-slate-600 dark:text-slate-300 truncate">
                      {rep.review_reason || '—'}
                    </td>
                    <td className="py-3.5 px-4 font-semibold text-slate-800 dark:text-slate-200">
                      {rep.reviewed_by_name || '—'}
                    </td>
                    <td className="py-3.5 px-4 text-right text-slate-400 text-[0.7rem]">
                      {rep.reviewed_at || '—'}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </AppLayout>
  );
}
