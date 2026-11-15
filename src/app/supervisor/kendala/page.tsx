'use client';

import React from 'react';
import { AppLayout } from '../../../components/layout/AppLayout';
import { PageHeader } from '../../../components/ui/PageHeader';
import { StatusBadge } from '../../../components/ui/StatusBadge';
import { useOperations } from '../../../context/OperationsContext';
import { AlertTriangle, Wrench, ShieldAlert } from 'lucide-react';

export default function SupervisorIssuesPage() {
  const { reports } = useOperations();
  const issueReports = reports.filter((r) => !!r.issue_category);

  return (
    <AppLayout>
      <PageHeader
        title="Catatan Kendala & Cacat Lini Produksi"
        description="Daftar kendala teknis, downtime mesin, dan ketidaksesuaian material yang dilaporkan operator."
      />

      {issueReports.length === 0 ? (
        <div className="phm-card text-center py-12">
          <Wrench className="w-12 h-12 text-slate-300 mx-auto mb-3" />
          <h3 className="text-base font-bold text-slate-800 dark:text-white">Tidak ada kendala aktif</h3>
          <p className="text-xs text-slate-500 mt-1">Lini produksi berjalan lancar tanpa laporan downtime mesin.</p>
        </div>
      ) : (
        <div className="space-y-4">
          {issueReports.map((item) => (
            <div key={item.id} className="phm-card border-l-4 border-l-rose-500">
              <div className="flex items-center justify-between mb-2">
                <span className="font-bold text-xs text-rose-600 bg-rose-50 dark:bg-rose-950/40 px-2.5 py-1 rounded-full border border-rose-200">
                  {item.issue_category}
                </span>
                <span className="text-xs text-slate-400">{item.created_at}</span>
              </div>

              <h3 className="text-base font-bold text-slate-900 dark:text-white mb-1">
                {item.product_name} ({item.work_order_number})
              </h3>

              <p className="text-xs text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl border border-slate-100 dark:border-slate-800 my-2">
                {item.notes || 'Operator mencatat adanya kendala saat siklus pengerjaan.'}
              </p>

              <div className="flex items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-100 dark:border-slate-800">
                <span>Pelapor: <strong>{item.reported_by_name}</strong></span>
                <span>Unit Terdampak: <strong>{item.quantity} pcs</strong></span>
              </div>
            </div>
          ))}
        </div>
      )}
    </AppLayout>
  );
}
