'use client';

import React from 'react';
import { AppLayout } from '../../components/layout/AppLayout';
import { PageHeader } from '../../components/ui/PageHeader';
import { StatCard } from '../../components/ui/StatCard';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { useOperations } from '../../context/OperationsContext';
import Link from 'next/link';
import {
  Package,
  CheckCircle2,
  Clock,
  Truck,
  ArrowRight,
  ClipboardList,
  AlertCircle,
  PlusCircle,
} from 'lucide-react';

export default function DashboardPage() {
  const { orders, reports, shipments, auditLogs, getOrderProductionProgress } = useOperations();

  const totalReported = reports.reduce((s, r) => s + r.quantity, 0);
  const totalVerified = reports.filter((r) => r.status === 'disetujui').reduce((s, r) => s + r.quantity, 0);
  const pendingReviews = reports.filter((r) => r.status === 'menunggu_pemeriksaan').length;
  const activeDeliveries = shipments.filter((s) => s.status === 'in_transit').length;

  return (
    <AppLayout>
      <PageHeader
        title="Ringkasan Operasional Pabrik"
        description="Pantau alur kerja manufaktur, hasil verifikasi produksi, dan pengiriman barang secara terpadu."
        action={
          <div className="flex items-center gap-2">
            <Link href="/operator/catat-hasil" className="phm-btn-secondary text-xs">
              <PlusCircle className="w-3.5 h-3.5" />
              <span>Input Hasil</span>
            </Link>
            <Link href="/pesanan/tambah" className="phm-btn-primary text-xs">
              <PlusCircle className="w-3.5 h-3.5" />
              <span>Pesanan Baru</span>
            </Link>
          </div>
        }
      />

      {/* KPI Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <StatCard
          label="Total Pesanan Aktif"
          value={orders.length}
          subtext="Pesanan mitra industri"
          icon={Package}
          variant="blue"
        />
        <StatCard
          label="Produksi Terverifikasi"
          value={`${totalVerified} pcs`}
          subtext="Lolos inspeksi supervisor & QC"
          icon={CheckCircle2}
          variant="emerald"
        />
        <StatCard
          label="Menunggu Verifikasi"
          value={`${pendingReviews} batch`}
          subtext="Laporan belum diperiksa"
          icon={Clock}
          variant="amber"
        />
        <StatCard
          label="Pengiriman Berjalan"
          value={`${activeDeliveries} armada`}
          subtext="Truk logistik dalam rute"
          icon={Truck}
          variant="indigo"
        />
      </div>

      {/* Main Grid: Orders & Progress + Audit Activities */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left 2 Cols: Orders & Production Progress */}
        <div className="lg:col-span-2 space-y-6">
          <div className="phm-card">
            <div className="flex items-center justify-between mb-4">
              <div>
                <h2 className="text-base font-bold text-slate-900 dark:text-white">
                  Progres Produksi Pesanan Berjalan
                </h2>
                <p className="text-xs text-slate-500">
                  Perbandingan target terhadap unit yang telah terverifikasi QC.
                </p>
              </div>
              <Link href="/pesanan" className="text-xs font-semibold text-[#0077C8] hover:underline flex items-center gap-1">
                <span>Lihat Semua</span>
                <ArrowRight className="w-3.5 h-3.5" />
              </Link>
            </div>

            <div className="space-y-4">
              {orders.slice(0, 4).map((order) => {
                const prog = getOrderProductionProgress(order.id);
                return (
                  <div key={order.id} className="p-4 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                      <div>
                        <span className="font-mono text-xs font-bold text-[#0077C8]">{order.order_number}</span>
                        <span className="text-xs text-slate-400 mx-2">·</span>
                        <span className="text-xs font-semibold text-slate-800 dark:text-slate-200">{order.partner_name}</span>
                      </div>
                      <StatusBadge status={order.status} />
                    </div>

                    <div className="text-sm font-bold text-slate-900 dark:text-white mb-2">
                      {order.product_name}
                    </div>

                    {/* Progress Bar */}
                    <div className="space-y-1">
                      <div className="flex justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span>
                          Terverifikasi: <strong>{prog.verified}</strong> / {prog.target} pcs
                        </span>
                        <span className="font-bold text-slate-700 dark:text-slate-200">{prog.percentage}%</span>
                      </div>
                      <div className="w-full h-2.5 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                        <div
                          className="h-full bg-gradient-to-r from-blue-500 to-emerald-500 rounded-full transition-all duration-300"
                          style={{ width: `${prog.percentage}%` }}
                        />
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        </div>

        {/* Right 1 Col: Audit & Activity Trail */}
        <div className="space-y-6">
          <div className="phm-card">
            <h2 className="text-base font-bold text-slate-900 dark:text-white mb-1">
              Catatan Aktivitas Sistem
            </h2>
            <p className="text-xs text-slate-500 mb-4">
              Jejak audit operasional terbaru di lantai produksi.
            </p>

            <div className="space-y-3">
              {auditLogs.slice(0, 5).map((log) => (
                <div key={log.id} className="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 text-xs">
                  <div className="flex items-center justify-between text-slate-400 text-[0.6875rem] mb-1">
                    <span className="font-semibold text-slate-700 dark:text-slate-300">{log.user_name}</span>
                    <span>{log.timestamp}</span>
                  </div>
                  <p className="text-slate-600 dark:text-slate-300 font-medium leading-relaxed">
                    {log.details}
                  </p>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
