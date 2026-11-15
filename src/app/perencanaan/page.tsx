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
            <Link href="/perencanaan/work-order" className="phm-btn-secondary">
              <Layers className="h-[1.125rem] w-[1.125rem] shrink-0" />
              <span>Kelola WO</span>
            </Link>
            <Link href="/perencanaan/penugasan" className="phm-btn-primary">
              <PlusCircle className="h-[1.125rem] w-[1.125rem] shrink-0" />
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
          tone="brand"
        />
        <StatCard
          label="Total Penugasan Operator"
          value={totalAssignments}
          subtext="Tersebar di berbagai stasiun"
          icon={Users}
          tone="brand"
        />
        <StatCard
          label="Pesanan Belum Masuk WO"
          value={orders.filter((o) => o.status === 'confirmed').length}
          subtext="Siap dijadwalkan ke lini"
          icon={CalendarCheck}
          tone="waspada"
        />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Work Orders List */}
        <div className="phm-card">
          <div className="flex items-center justify-between mb-4">
            <h2 className="phm-section">
              Work Order (WO) Aktif
            </h2>
            <Link href="/perencanaan/work-order" className="text-label font-semibold text-brand-aksen hover:underline flex items-center gap-1">
              <span>Kelola Semua</span>
              <ArrowRight className="h-[1.125rem] w-[1.125rem] shrink-0" />
            </Link>
          </div>

          <div className="space-y-3">
            {workOrders.map((wo) => (
              <div key={wo.id} className="p-3.5 rounded-kontrol bg-kartu-dalam border border-garis flex items-center justify-between">
                <div>
                  <div className="flex items-center gap-2">
                    <span className="font-mono text-label font-bold text-brand-aksen">{wo.work_order_number}</span>
                    <span className="text-mini font-bold px-2 py-0.5 rounded-full bg-netral-latar text-teks uppercase">
                      Prioritas: {wo.priority}
                    </span>
                  </div>
                  <div className="text-isi font-bold text-teks dark:text-white mt-1">
                    {wo.product_name}
                  </div>
                  <div className="text-label text-redup mt-0.5">
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
            <h2 className="phm-section">
              Penugasan Operator Terbaru
            </h2>
            <Link href="/perencanaan/penugasan" className="text-label font-semibold text-brand-aksen hover:underline flex items-center gap-1">
              <span>Kelola Semua</span>
              <ArrowRight className="h-[1.125rem] w-[1.125rem] shrink-0" />
            </Link>
          </div>

          <div className="space-y-3">
            {assignments.map((assign) => (
              <div key={assign.id} className="p-3.5 rounded-kontrol bg-kartu-dalam border border-garis">
                <div className="flex items-center justify-between mb-1">
                  <span className="text-label font-bold text-teks">{assign.operator_name}</span>
                  <StatusBadge status={assign.status} />
                </div>
                <div className="text-label text-brand-aksen font-semibold">{assign.product_name}</div>
                <div className="flex justify-between text-label text-redup mt-1">
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
