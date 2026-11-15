'use client';

import React from 'react';
import { AppLayout } from '../../../components/layout/AppLayout';
import { PageHeader } from '../../../components/ui/PageHeader';
import { useOperations } from '../../../context/OperationsContext';

export default function SystemAuditPage() {
  const { auditLogs } = useOperations();

  return (
    <AppLayout>
      <PageHeader
        title="Jejak Audit Operasional (Audit Trail)"
        description="Perekaman kronologis setiap transaksi, mutasi laporan, verifikasi supervisor, dan pengiriman barang."
      />

      <div className="phm-card overflow-hidden p-0">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 font-bold uppercase tracking-wider text-[0.6875rem]">
              <tr>
                <th className="py-3 px-4">Waktu</th>
                <th className="py-3 px-4">Pelaku (User)</th>
                <th className="py-3 px-4">Aksi / Event</th>
                <th className="py-3 px-4">Deskripsi Aktivitas</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {auditLogs.map((log) => (
                <tr key={log.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                  <td className="py-3.5 px-4 font-mono text-slate-500 whitespace-nowrap">
                    {log.timestamp}
                  </td>
                  <td className="py-3.5 px-4 font-bold text-slate-900 dark:text-white whitespace-nowrap">
                    {log.user_name}
                  </td>
                  <td className="py-3.5 px-4 font-mono font-semibold text-xs text-[#0077C8] whitespace-nowrap">
                    {log.action}
                  </td>
                  <td className="py-3.5 px-4 text-slate-700 dark:text-slate-300">
                    {log.details}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </AppLayout>
  );
}
