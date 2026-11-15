'use client';

import React, { useState } from 'react';
import { AppLayout } from '../../../components/layout/AppLayout';
import { PageHeader } from '../../../components/ui/PageHeader';
import { StatusBadge } from '../../../components/ui/StatusBadge';
import { useAuth } from '../../../context/AuthContext';
import { useOperations } from '../../../context/OperationsContext';
import { CheckCircle2, XCircle, Clock, AlertTriangle, ShieldAlert } from 'lucide-react';

export default function SupervisorQueuePage() {
  const { user } = useAuth();
  const { reports, reviewProductionReport } = useOperations();

  const [rejectingId, setRejectingId] = useState<number | null>(null);
  const [rejectReason, setRejectReason] = useState('');

  const pendingReports = reports.filter((r) => r.status === 'menunggu_pemeriksaan');

  const handleApprove = (id: number, reporterId: number) => {
    // Self-review protection (Architecture Rule 5)
    if (user?.id === reporterId && !user?.roles.includes('sysadmin')) {
      alert('Anda tidak diperkenankan memverifikasi laporan produksi yang Anda buat sendiri.');
      return;
    }
    reviewProductionReport(id, 'disetujui', user?.name || 'Siti Rahayu', 'Dimensi & visual lolos toleransi standar PHM.');
  };

  const handleOpenReject = (id: number) => {
    setRejectingId(id);
    setRejectReason('');
  };

  const handleConfirmReject = (id: number) => {
    if (!rejectReason.trim()) {
      alert('Harap masukkan alasan penolakan.');
      return;
    }
    reviewProductionReport(id, 'ditolak', user?.name || 'Siti Rahayu', rejectReason.trim());
    setRejectingId(null);
  };

  return (
    <AppLayout>
      <PageHeader
        title="Antrean Pemeriksaan Hasil Produksi"
        description="Verifikasi kesesuaian fisik dan dimensi hasil pengerjaan operator sebelum masuk ke stok terverifikasi."
      />

      {pendingReports.length === 0 ? (
        <div className="phm-card text-center py-12">
          <CheckCircle2 className="w-12 h-12 text-emerald-400 mx-auto mb-3" />
          <h3 className="text-base font-bold text-slate-800 dark:text-white">
            Semua laporan telah diperiksa
          </h3>
          <p className="text-xs text-slate-500 mt-1">
            Tidak ada laporan hasil produksi yang menunggu verifikasi saat ini.
          </p>
        </div>
      ) : (
        <div className="space-y-4">
          {pendingReports.map((rep) => (
            <div
              key={rep.id}
              className="phm-card flex flex-col md:flex-row md:items-center justify-between gap-4 border-l-4 border-l-amber-500"
            >
              <div className="space-y-1.5 flex-1">
                <div className="flex items-center gap-2">
                  <span className="font-mono text-xs font-bold text-[#0077C8]">{rep.work_order_number}</span>
                  <span className="text-xs text-slate-400">·</span>
                  <StatusBadge status={rep.status} />
                  {rep.issue_category && (
                    <span className="text-[0.6875rem] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700">
                      Kendala: {rep.issue_category}
                    </span>
                  )}
                </div>

                <div className="text-base font-bold text-slate-900 dark:text-white">
                  {rep.product_name}
                </div>

                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                  <span>
                    Dilaporkan oleh: <strong className="text-slate-800 dark:text-slate-200">{rep.reported_by_name}</strong>
                  </span>
                  <span>
                    Waktu: <strong>{rep.created_at}</strong>
                  </span>
                </div>

                {rep.notes && (
                  <p className="text-xs text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 mt-2">
                    &ldquo;{rep.notes}&rdquo;
                  </p>
                )}
              </div>

              {/* Quantity and Action Buttons */}
              <div className="flex items-center gap-4 shrink-0 justify-between md:justify-end border-t md:border-t-0 pt-3 md:pt-0 border-slate-100 dark:border-slate-800">
                <div className="text-center px-4">
                  <div className="text-[0.6875rem] text-slate-400 uppercase font-semibold">Unit Dilaporkan</div>
                  <div className="text-2xl font-black text-amber-600">+{rep.quantity} pcs</div>
                </div>

                {rejectingId === rep.id ? (
                  <div className="flex flex-col gap-2 w-64 animate-in fade-in">
                    <input
                      type="text"
                      value={rejectReason}
                      onChange={(e) => setRejectReason(e.target.value)}
                      placeholder="Alasan penolakan QC…"
                      className="phm-input text-xs"
                      autoFocus
                    />
                    <div className="flex gap-2">
                      <button
                        onClick={() => handleConfirmReject(rep.id)}
                        className="flex-1 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold cursor-pointer"
                      >
                        Konfirmasi Tolak
                      </button>
                      <button
                        onClick={() => setRejectingId(null)}
                        className="px-3 py-1.5 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs cursor-pointer"
                      >
                        Batal
                      </button>
                    </div>
                  </div>
                ) : (
                  <div className="flex items-center gap-2">
                    <button
                      onClick={() => handleApprove(rep.id, rep.reported_by_id)}
                      className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl flex items-center gap-1.5 shadow-xs transition-colors cursor-pointer"
                    >
                      <CheckCircle2 className="w-4 h-4" />
                      <span>Verifikasi / Setujui</span>
                    </button>
                    <button
                      onClick={() => handleOpenReject(rep.id)}
                      className="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 font-semibold text-xs rounded-xl flex items-center gap-1 border border-rose-200 dark:border-rose-800 transition-colors cursor-pointer"
                    >
                      <XCircle className="w-4 h-4" />
                      <span>Tolak</span>
                    </button>
                  </div>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </AppLayout>
  );
}
