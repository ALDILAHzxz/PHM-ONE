'use client';

import React from 'react';
import { AppLayout } from '../../../components/layout/AppLayout';
import { PageHeader } from '../../../components/ui/PageHeader';
import { StatusBadge } from '../../../components/ui/StatusBadge';
import { useAuth } from '../../../context/AuthContext';
import { useOperations } from '../../../context/OperationsContext';
import Link from 'next/link';
import { History, PlusCircle, CheckCircle2, Clock, AlertTriangle } from 'lucide-react';

export default function OperatorHistoryPage() {
  const { user } = useAuth();
  const { reports } = useOperations();

  // Filter reports submitted by this operator (or all for admin)
  const myReports = reports.filter(
    (r) => r.reported_by_id === user?.id || user?.roles.includes('sysadmin') || user?.roles.includes('supervisor')
  );

  return (
    <AppLayout>
      <PageHeader
        title="Riwayat Laporan Produksi"
        description="Daftar laporan hasil pengerjaan mesin yang telah dikirim beserta status verifikasinya."
        action={
          <Link href="/operator/catat-hasil" className="phm-btn-primary text-xs">
            <PlusCircle className="w-3.5 h-3.5" />
            <span>Buat Laporan Baru</span>
          </Link>
        }
      />

      <div className="phm-card overflow-hidden p-0">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 font-bold uppercase tracking-wider text-[0.6875rem]">
              <tr>
                <th className="py-3 px-4">ID Laporan</th>
                <th className="py-3 px-4">Work Order & Komponen</th>
                <th className="py-3 px-4 text-center">Jumlah (pcs)</th>
                <th className="py-3 px-4">Status Verifikasi</th>
                <th className="py-3 px-4">Catatan Operator</th>
                <th className="py-3 px-4">Hasil Review Supervisor</th>
                <th className="py-3 px-4 text-right">Waktu Lapor</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {myReports.length === 0 ? (
                <tr>
                  <td colSpan={7} className="py-8 text-center text-slate-400">
                    Belum ada laporan produksi yang tercatat.
                  </td>
                </tr>
              ) : (
                myReports.map((rep) => (
                  <tr key={rep.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                    <td className="py-3.5 px-4 font-mono font-bold text-slate-700 dark:text-slate-300">
                      #{rep.id}
                    </td>
                    <td className="py-3.5 px-4">
                      <div className="font-bold text-slate-900 dark:text-white">{rep.product_name}</div>
                      <div className="text-[0.7rem] text-[#0077C8] font-mono">{rep.work_order_number}</div>
                    </td>
                    <td className="py-3.5 px-4 text-center">
                      <span className="font-extrabold text-sm text-slate-800 dark:text-slate-100">
                        +{rep.quantity}
                      </span>
                    </td>
                    <td className="py-3.5 px-4">
                      <StatusBadge status={rep.status} />
                    </td>
                    <td className="py-3.5 px-4 max-w-xs text-slate-600 dark:text-slate-300 truncate">
                      {rep.notes || '—'}
                      {rep.issue_category && (
                        <div className="text-[0.6875rem] text-rose-600 font-semibold mt-0.5">
                          Kendala: {rep.issue_category}
                        </div>
                      )}
                    </td>
                    <td className="py-3.5 px-4 max-w-xs">
                      {rep.reviewed_by_name ? (
                        <div className="text-[0.7rem]">
                          <span className="font-semibold text-slate-800 dark:text-slate-200">
                            {rep.reviewed_by_name}
                          </span>
                          <p className="text-slate-500 italic mt-0.5">{rep.review_reason}</p>
                        </div>
                      ) : (
                        <span className="text-slate-400 italic">Menunggu giliran</span>
                      )}
                    </td>
                    <td className="py-3.5 px-4 text-right text-slate-400 text-[0.7rem]">
                      {rep.created_at}
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
