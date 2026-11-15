'use client';

import React from 'react';
import { AppLayout } from '../../components/layout/AppLayout';
import { PageHeader } from '../../components/ui/PageHeader';
import { StatCard } from '../../components/ui/StatCard';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { useOperations } from '../../context/OperationsContext';
import Link from 'next/link';
import { CalendarCheck, Layers, Users, PlusCircle, ArrowRight } from 'lucide-react';

export default function PlanningOverviewPage() {
  const { workOrders, assignments, orders } = useOperations();

  const activeWOs = workOrders.filter((w) => w.status === 'in_progress').length;
  const totalAssignments = assignments.length;

  return (
    <AppLayout>
      <PageHeader
        title="Perencanaan & Kontrol Produksi (PPIC)"
        description="Alokasikan kapasitas lini mesin, rilis work order, dan delegasikan penugasan operator pabrik."
        action={
          <div className="flex items-center gap-2">
            <Link href="/perencanaan/work-order" className="phm-btn-secondary text-xs">
              <Layers className="w-3.5 h-3.5" />
              <span>Kelola WO</span>
            </Link>
            <Link href="/perencanaan/penugasan" className="phm-btn-primary text-xs">
              <PlusCircle className="w-3.5 h-3.5" />
              <span>Bagi Penugasan</span>
            </Link>
          </div>
        }
      />

      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <StatCard
          label="Total Work Order"
          value={workOrders.length}
          subtext={`${activeWOs} sedang berjalan`}
          icon={Layers}
          variant="blue"
        />
        <StatCard
          label="Total Penugasan Operator"
          value={totalAssignments}
          subtext="Tersebar di berbagai stasiun"
          icon={Users}
          variant="indigo"
        />
        <StatCard
          label="Pesanan Belum Masuk WO"
          value={orders.filter((o) => o.status === 'confirmed').length}
          subtext="Siap dijadwalkan ke lini"
          icon={CalendarCheck}
          variant="amber"
        />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Work Orders List */}
        <div className="phm-card">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-base font-bold text-slate-900 dark:text-white">
              Work Order (WO) Aktif
            </h2>
            <Link href="/perencanaan/work-order" className="text-xs font-semibold text-[#0077C8] hover:underline flex items-center gap-1">
              <span>Kelola Semua</span>
              <ArrowRight className="w-3.5 h-3.5" />
            </Link>
          </div>

          <div className="space-y-3">
            {workOrders.map((wo) => (
              <div key={wo.id} className="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                  <div className="flex items-center gap-2">
                    <span className="font-mono text-xs font-bold text-[#0077C8]">{wo.work_order_number}</span>
                    <span className="text-[0.6875rem] font-bold px-2 py-0.5 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 uppercase">
                      Prioritas: {wo.priority}
                    </span>
                  </div>
                  <div className="text-sm font-bold text-slate-800 dark:text-white mt-1">
                    {wo.product_name}
                  </div>
                  <div className="text-xs text-slate-500 mt-0.5">
                    Target: <strong>{wo.target_quantity} pcs</strong> | Deadline: {wo.deadline}
                  </div>
                </div>
                <StatusBadge status={wo.status} />
              </div>
            ))}
          </div>
        </div>

        {/* Current Assignments */}
        <div className="phm-card">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-base font-bold text-slate-900 dark:text-white">
              Penugasan Operator Terbaru
            </h2>
            <Link href="/perencanaan/penugasan" className="text-xs font-semibold text-[#0077C8] hover:underline flex items-center gap-1">
              <span>Kelola Semua</span>
              <ArrowRight className="w-3.5 h-3.5" />
            </Link>
          </div>

          <div className="space-y-3">
            {assignments.map((assign) => (
              <div key={assign.id} className="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                <div className="flex items-center justify-between mb-1">
                  <span className="text-xs font-bold text-slate-900 dark:text-white">{assign.operator_name}</span>
                  <StatusBadge status={assign.status} />
                </div>
                <div className="text-xs text-[#0077C8] font-semibold">{assign.product_name}</div>
                <div className="flex justify-between text-xs text-slate-500 mt-1">
                  <span>{assign.workstation_name}</span>
                  <span>Target: <strong>{assign.target_quantity} pcs</strong></span>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
