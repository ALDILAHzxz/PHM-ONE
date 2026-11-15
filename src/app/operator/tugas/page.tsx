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
          <Link href="/operator/catat-hasil" className="phm-btn-primary">
            <PlusCircle className="h-[1.125rem] w-[1.125rem] shrink-0" />
            <span>Catat Hasil Pengerjaan</span>
          </Link>
        }
      />

      {myAssignments.length === 0 ? (
        <div className="phm-card text-center py-12">
          <ClipboardList className="w-12 h-12 text-slate-300 mx-auto mb-3" />
          <h3 className="text-base font-bold text-teks dark:text-white">Tidak ada tugas aktif</h3>
          <p className="text-label text-redup mt-1">Saat ini belum ada penugasan baru dari tim PPIC.</p>
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
                    <span className="font-mono text-label font-bold text-brand-aksen">{task.work_order_number}</span>
                    <StatusBadge status={task.status} />
                  </div>

                  <h3 className="text-base font-bold text-teks mb-1">
                    {task.product_name}
                  </h3>

                  <div className="space-y-1.5 text-label text-teks my-3 bg-kartu-dalam p-3 rounded-kontrol">
                    <div className="flex justify-between">
                      <span className="text-redup">Tahap Produksi:</span>
                      <span className="font-semibold">{task.stage_name}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-redup">Stasiun Mesin:</span>
                      <span className="font-semibold">{task.workstation_name}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-redup">Shift Kerja:</span>
                      <span className="font-semibold">{task.shift_name}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-redup">Operator:</span>
                      <span className="font-semibold text-teks">{task.operator_name}</span>
                    </div>
                  </div>

                  {/* Quantity Breakdown (Strict Rule: Reported != Verified) */}
                  <div className="grid grid-cols-3 gap-2 text-center p-2.5 rounded-kontrol border border-garis mb-4 text-label">
                    <div>
                      <div className="text-mini text-redup font-semibold uppercase">Target</div>
                      <div className="text-isi font-extrabold text-teks dark:text-white mt-0.5">{task.target_quantity}</div>
                    </div>
                    <div className="border-x border-garis">
                      <div className="text-mini text-amber-600 dark:text-amber-400 font-semibold uppercase">Dilaporkan</div>
                      <div className="text-isi font-extrabold text-amber-600 dark:text-amber-400 mt-0.5">{reported}</div>
                    </div>
                    <div>
                      <div className="text-mini text-emerald-600 dark:text-emerald-400 font-semibold uppercase">Terverifikasi</div>
                      <div className="text-isi font-extrabold text-emerald-600 dark:text-emerald-400 mt-0.5">{verified}</div>
                    </div>
                  </div>

                  {/* Progress bar */}
                  <div className="space-y-1 mb-4">
                    <div className="flex justify-between text-mini text-redup">
                      <span>Progres Verifikasi</span>
                      <span className="font-bold text-teks">{percentage}%</span>
                    </div>
                    <div className="w-full h-2 rounded-full bg-netral-latar overflow-hidden">
                      <div
                        className="h-full bg-emerald-500 rounded-full transition-all duration-300"
                        style={{ width: `${percentage}%` }}
                      />
                    </div>
                  </div>
                </div>

                <Link
                  href={`/operator/catat-hasil?assignmentId=${task.id}`}
                  className="w-full h-10 bg-brand hover:bg-brand-pekat text-white font-semibold text-label rounded-kontrol flex items-center justify-center gap-1.5 transition-colors cursor-pointer"
                >
                  <PlusCircle className="h-[1.125rem] w-[1.125rem] shrink-0" />
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
