'use client';

import React from 'react';
import { AppLayout } from '../../../components/layout/AppLayout';
import { PageHeader } from '../../../components/ui/PageHeader';
import { StatusBadge } from '../../../components/ui/StatusBadge';
import { useAuth } from '../../../context/AuthContext';
import { useOperations } from '../../../context/OperationsContext';
import Link from 'next/link';
import { ClipboardList, PlusCircle, Factory, Clock, ArrowRight } from 'lucide-react';

export default function OperatorTasksPage() {
  const { user } = useAuth();
  const { assignments, getAssignmentReportedQty, getAssignmentVerifiedQty } = useOperations();

  // Show tasks assigned to this operator or all if admin/supervisor
  const myAssignments = assignments.filter(
    (a) => a.operator_id === user?.id || user?.roles.includes('sysadmin') || user?.roles.includes('supervisor')
  );

  return (
    <AppLayout>
      <PageHeader
        title="Daftar Tugas Produksi"
        description="Pekerjaan dan lini mesin yang ditugaskan kepada Anda pada shift kerja saat ini."
        action={
          <Link href="/operator/catat-hasil" className="phm-btn-primary text-xs">
            <PlusCircle className="w-3.5 h-3.5" />
            <span>Catat Hasil Pengerjaan</span>
          </Link>
        }
      />

      {myAssignments.length === 0 ? (
        <div className="phm-card text-center py-12">
          <ClipboardList className="w-12 h-12 text-slate-300 mx-auto mb-3" />
          <h3 className="text-base font-bold text-slate-800 dark:text-white">Tidak ada tugas aktif</h3>
          <p className="text-xs text-slate-500 mt-1">Saat ini belum ada penugasan baru dari tim PPIC.</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {myAssignments.map((task) => {
            const reported = getAssignmentReportedQty(task.id);
            const verified = getAssignmentVerifiedQty(task.id);
            const percentage = task.target_quantity > 0 ? Math.min(100, Math.round((verified / task.target_quantity) * 100)) : 0;

            return (
              <div key={task.id} className="phm-card flex flex-col justify-between">
                <div>
                  <div className="flex items-center justify-between gap-2 mb-3">
                    <span className="font-mono text-xs font-bold text-[#0077C8]">{task.work_order_number}</span>
                    <StatusBadge status={task.status} />
                  </div>

                  <h3 className="text-base font-bold text-slate-900 dark:text-white mb-1">
                    {task.product_name}
                  </h3>

                  <div className="space-y-1.5 text-xs text-slate-600 dark:text-slate-300 my-3 bg-slate-50 dark:bg-slate-800/50 p-3 rounded-xl">
                    <div className="flex justify-between">
                      <span className="text-slate-400">Tahap Produksi:</span>
                      <span className="font-semibold">{task.stage_name}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-slate-400">Stasiun Mesin:</span>
                      <span className="font-semibold">{task.workstation_name}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-slate-400">Shift Kerja:</span>
                      <span className="font-semibold">{task.shift_name}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-slate-400">Operator:</span>
                      <span className="font-semibold text-slate-800 dark:text-slate-100">{task.operator_name}</span>
                    </div>
                  </div>

                  {/* Quantity Breakdown (Strict Rule: Reported != Verified) */}
                  <div className="grid grid-cols-3 gap-2 text-center p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 mb-4 text-xs">
                    <div>
                      <div className="text-[0.6875rem] text-slate-400 font-semibold uppercase">Target</div>
                      <div className="text-sm font-extrabold text-slate-800 dark:text-white mt-0.5">{task.target_quantity}</div>
                    </div>
                    <div className="border-x border-slate-100 dark:border-slate-800">
                      <div className="text-[0.6875rem] text-amber-600 dark:text-amber-400 font-semibold uppercase">Dilaporkan</div>
                      <div className="text-sm font-extrabold text-amber-600 dark:text-amber-400 mt-0.5">{reported}</div>
                    </div>
                    <div>
                      <div className="text-[0.6875rem] text-emerald-600 dark:text-emerald-400 font-semibold uppercase">Terverifikasi</div>
                      <div className="text-sm font-extrabold text-emerald-600 dark:text-emerald-400 mt-0.5">{verified}</div>
                    </div>
                  </div>

                  {/* Progress bar */}
                  <div className="space-y-1 mb-4">
                    <div className="flex justify-between text-[0.7rem] text-slate-500">
                      <span>Progres Verifikasi</span>
                      <span className="font-bold text-slate-700 dark:text-slate-200">{percentage}%</span>
                    </div>
                    <div className="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-700 overflow-hidden">
                      <div
                        className="h-full bg-emerald-500 rounded-full transition-all duration-300"
                        style={{ width: `${percentage}%` }}
                      />
                    </div>
                  </div>
                </div>

                <Link
                  href={`/operator/catat-hasil?assignmentId=${task.id}`}
                  className="w-full h-10 bg-[#0077C8] hover:bg-[#0062A3] text-white font-semibold text-xs rounded-xl flex items-center justify-center gap-1.5 transition-colors cursor-pointer"
                >
                  <PlusCircle className="w-3.5 h-3.5" />
                  <span>Catat Hasil Mesin</span>
                </Link>
              </div>
            );
          })}
        </div>
      )}
    </AppLayout>
  );
}
