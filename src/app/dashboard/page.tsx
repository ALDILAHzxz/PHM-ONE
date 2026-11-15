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
            <Link href="/operator/catat-hasil" className="phm-btn-secondary">
              <PlusCircle className="h-[1.125rem] w-[1.125rem] shrink-0" />
              <span>Input Hasil</span>
            </Link>
            <Link href="/pesanan/tambah" className="phm-btn-primary">
              <PlusCircle className="h-[1.125rem] w-[1.125rem] shrink-0" />
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
          tone="brand"
        />
        <StatCard
          label="Produksi Terverifikasi"
          value={`${totalVerified} pcs`}
          subtext="Lolos inspeksi supervisor & QC"
          icon={CheckCircle2}
          tone="aman"
        />
        <StatCard
          label="Menunggu Verifikasi"
          value={`${pendingReviews} batch`}
          subtext="Laporan belum diperiksa"
          icon={Clock}
          tone="waspada"
        />
        <StatCard
          label="Pengiriman Berjalan"
          value={`${activeDeliveries} armada`}
          subtext="Truk logistik dalam rute"
          icon={Truck}
          tone="brand"
        />
      </div>

      {/* Main Grid: Orders & Progress + Audit Activities */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left 2 Cols: Orders & Production Progress */}
        <div className="lg:col-span-2 space-y-6">
          <div className="phm-card">
            <div className="flex items-center justify-between mb-4">
              <div>
                <h2 className="phm-section">
                  Progres Produksi Pesanan Berjalan
                </h2>
                <p className="text-label text-redup">
                  Perbandingan target terhadap unit yang telah terverifikasi QC.
                </p>
              </div>
              <Link href="/pesanan" className="text-label font-semibold text-brand-aksen hover:underline flex items-center gap-1">
                <span>Lihat Semua</span>
                <ArrowRight className="h-[1.125rem] w-[1.125rem] shrink-0" />
              </Link>
            </div>

            <div className="space-y-4">
              {orders.slice(0, 4).map((order) => {
                const prog = getOrderProductionProgress(order.id);
                return (
                  <div key={order.id} className="p-4 rounded-kontrol border border-garis bg-kartu-dalam">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                      <div>
                        <span className="font-mono text-label font-bold text-brand-aksen">{order.order_number}</span>
                        <span className="text-label text-redup mx-2">·</span>
                        <span className="text-label font-semibold text-teks">{order.partner_name}</span>
                      </div>
                      <StatusBadge status={order.status} />
                    </div>

                    <div className="text-isi font-bold text-teks mb-2">
                      {order.product_name}
                    </div>

                    {/* Progress Bar */}
                    <div className="space-y-1">
                      <div className="flex justify-between text-label text-redup">
                        <span>
                          Terverifikasi: <strong>{prog.verified}</strong> / {prog.target} pcs
                        </span>
                        <span className="font-bold text-teks">{prog.percentage}%</span>
                      </div>
                      <div className="w-full h-2.5 rounded-full bg-netral-latar overflow-hidden">
                        <div
                          className="h-full bg-brand to-emerald-500 rounded-full transition-all duration-300"
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
            <h2 className="text-base font-bold text-teks mb-1">
              Catatan Aktivitas Sistem
            </h2>
            <p className="text-label text-redup mb-4">
              Jejak audit operasional terbaru di lantai produksi.
            </p>

            <div className="space-y-3">
              {auditLogs.slice(0, 5).map((log) => (
                <div key={log.id} className="p-3 rounded-kontrol bg-kartu-dalam border border-garis text-label">
                  <div className="flex items-center justify-between text-redup text-mini mb-1">
                    <span className="font-semibold text-teks">{log.user_name}</span>
                    <span>{log.timestamp}</span>
                  </div>
                  <p className="text-teks font-medium leading-relaxed">
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
