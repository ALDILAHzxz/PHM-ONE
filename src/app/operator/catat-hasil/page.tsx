'use client';

import React, { useState, useEffect, Suspense } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { AppLayout } from '../../../components/layout/AppLayout';
import { PageHeader } from '../../../components/ui/PageHeader';
import { useAuth } from '../../../context/AuthContext';
import { useOperations } from '../../../context/OperationsContext';
import { CheckCircle2, AlertCircle, ArrowLeft, Send } from 'lucide-react';
import Link from 'next/link';

function CatatHasilForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const initialAssignmentId = searchParams.get('assignmentId');

  const { user } = useAuth();
  const { assignments, submitProductionReport, getAssignmentReportedQty } = useOperations();

  const [assignmentId, setAssignmentId] = useState<number>(
    initialAssignmentId ? Number(initialAssignmentId) : assignments[0]?.id || 0
  );
  const [quantity, setQuantity] = useState<number | ''>('');
  const [notes, setNotes] = useState('');
  const [hasIssue, setHasIssue] = useState(false);
  const [issueCategory, setIssueCategory] = useState('MATERIAL_DEFECT');
  const [error, setError] = useState<string | null>(null);
  const [isSuccess, setIsSuccess] = useState(false);

  const selectedAssignment = assignments.find((a) => a.id === assignmentId);
  const currentReported = selectedAssignment ? getAssignmentReportedQty(selectedAssignment.id) : 0;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    if (!assignmentId) {
      setError('Silakan pilih tugas produksi.');
      return;
    }

    const numQty = Number(quantity);
    if (!numQty || numQty <= 0) {
      setError('Jumlah hasil produksi harus lebih dari 0.');
      return;
    }

    if (selectedAssignment) {
      const maxAllowed = Math.round(selectedAssignment.target_quantity * 1.05);
      if (currentReported + numQty > maxAllowed) {
        setError(
          `Jumlah melebihi toleransi maksimal overproduksi (Target: ${selectedAssignment.target_quantity} pcs, Maksimal: ${maxAllowed} pcs).`
        );
        return;
      }
    }

    submitProductionReport({
      assignment_id: assignmentId,
      quantity: numQty,
      notes: notes.trim(),
      issue_category: hasIssue ? issueCategory : undefined,
      reporter_id: user?.id || 1,
      reporter_name: user?.name || 'Ahmad Fauzi',
    });

    setIsSuccess(true);
    setTimeout(() => {
      router.push('/operator/riwayat');
    }, 1200);
  };

  return (
    <div className="max-w-2xl mx-auto">
      <Link
        href="/operator/tugas"
        className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 mb-4 transition-colors"
      >
        <ArrowLeft className="w-3.5 h-3.5" />
        <span>Kembali ke Daftar Tugas</span>
      </Link>

      <div className="phm-card">
        <h2 className="text-lg font-bold text-slate-900 dark:text-white mb-1">
          Form Laporan Hasil Produksi
        </h2>
        <p className="text-xs text-slate-500 mb-6">
          Setiap laporan yang dikirim akan berstatus <strong className="text-amber-600">Menunggu Pemeriksaan</strong> hingga diverifikasi oleh supervisor lini.
        </p>

        {isSuccess && (
          <div className="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 flex items-center gap-3 text-emerald-800 dark:text-emerald-200 text-xs font-semibold animate-in fade-in">
            <CheckCircle2 className="w-5 h-5 text-emerald-600 shrink-0" />
            <span>Laporan produksi berhasil dicatat! Mengalihkan ke riwayat…</span>
          </div>
        )}

        {error && (
          <div className="mb-6 p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 flex items-start gap-3 text-rose-800 dark:text-rose-200 text-xs font-semibold animate-in fade-in">
            <AlertCircle className="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
            <span>{error}</span>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="phm-label">Pilih Tugas / Work Order</label>
            <select
              value={assignmentId}
              onChange={(e) => setAssignmentId(Number(e.target.value))}
              className="phm-input"
              required
            >
              {assignments.map((a) => (
                <option key={a.id} value={a.id}>
                  {a.work_order_number} — {a.product_name} ({a.stage_name})
                </option>
              ))}
            </select>
          </div>

          {selectedAssignment && (
            <div className="p-3 bg-blue-50 dark:bg-blue-950/30 rounded-xl border border-blue-200 dark:border-blue-900 text-xs space-y-1">
              <div className="text-slate-500">Stasiun: <strong className="text-slate-800 dark:text-slate-200">{selectedAssignment.workstation_name}</strong></div>
              <div className="text-slate-500">Target Shift: <strong className="text-slate-800 dark:text-slate-200">{selectedAssignment.target_quantity} pcs</strong> | Sudah Dilaporkan: <strong className="text-amber-700 dark:text-amber-400">{currentReported} pcs</strong></div>
            </div>
          )}

          <div>
            <label className="phm-label">Jumlah Unit Selesai (pcs)</label>
            <input
              type="number"
              value={quantity}
              onChange={(e) => setQuantity(e.target.value === '' ? '' : Number(e.target.value))}
              placeholder="Contoh: 50"
              className="phm-input font-bold text-base"
              min={1}
              required
            />
            <p className="phm-helper mt-1">Masukkan hanya unit yang telah selesai proses fisik pada stasiun ini.</p>
          </div>

          <div>
            <label className="phm-label">Catatan Pengerjaan (Opsional)</label>
            <textarea
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="Contoh: Penggantian pahat bubut di unit ke-30, dimensi presisi sesuai gambar teknik."
              rows={3}
              className="phm-input text-xs"
            />
          </div>

          {/* Issue Flagging */}
          <div className="pt-2 border-t border-slate-100 dark:border-slate-800">
            <label className="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700 dark:text-slate-300">
              <input
                type="checkbox"
                checked={hasIssue}
                onChange={(e) => setHasIssue(e.target.checked)}
                className="w-4 h-4 rounded text-[#0077C8] border-slate-300 focus:ring-[#0077C8]"
              />
              <span>Terdapat kendala mesin / cacat material selama proses</span>
            </label>

            {hasIssue && (
              <div className="mt-3 p-3 bg-amber-50 dark:bg-amber-950/40 rounded-xl border border-amber-200 dark:border-amber-800 space-y-2 animate-in fade-in">
                <label className="text-xs font-bold text-amber-900 dark:text-amber-200">
                  Kategori Kendala:
                </label>
                <select
                  value={issueCategory}
                  onChange={(e) => setIssueCategory(e.target.value)}
                  className="phm-input text-xs"
                >
                  <option value="MATERIAL_DEFECT">Cacat Material / Bahan Baku Rusak</option>
                  <option value="MACHINE_BREAKDOWN">Mesin Mogok / Kerusakan Mekanikal</option>
                  <option value="QUALITY_ISSUE">Penyimpangan Dimensi / Cacat Visual</option>
                  <option value="TOOL_WEAR">Pahat Aus / Tool Patah</option>
                  <option value="OTHER">Lainnya</option>
                </select>
              </div>
            )}
          </div>

          <div className="pt-4">
            <button
              type="submit"
              disabled={isSuccess}
              className="w-full h-11 bg-[#0077C8] hover:bg-[#0062A3] text-white font-semibold text-xs rounded-xl flex items-center justify-center gap-2 transition-colors cursor-pointer"
            >
              <Send className="w-4 h-4" />
              <span>Kirim Laporan ke Supervisor</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export default function OperatorReportCreatePage() {
  return (
    <AppLayout>
      <PageHeader
        title="Catat Hasil Produksi"
        description="Laporkan jumlah unit yang selesai diproses pada lini manufaktur."
      />
      <Suspense fallback={<div className="text-xs text-slate-400">Memuat formulir…</div>}>
        <CatatHasilForm />
      </Suspense>
    </AppLayout>
  );
}
