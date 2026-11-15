'use client';

import React from 'react';
import { AppLayout } from '../../../components/layout/AppLayout';
import { PageHeader } from '../../../components/ui/PageHeader';
import { StatusBadge } from '../../../components/ui/StatusBadge';
import { DataTable, DataColumn } from '../../../components/ui/DataTable';
import { useOperations } from '../../../context/OperationsContext';
import { ProductionReport } from '../../../types';
import { History } from 'lucide-react';

export default function SupervisorHistoryPage() {
  const { reports } = useOperations();

  const reviewedReports = reports.filter(
    (r) => r.status === 'disetujui' || r.status === 'ditolak'
  );

  const columns: DataColumn<ProductionReport>[] = [
    {
      key: 'job',
      header: 'Laporan & Komponen',
      width: '28%',
      primary: true,
      hideLabelOnMobile: true,
      cell: (r) => (
        <div>
          <span className="block font-semibold text-teks">{r.product_name}</span>
          <span className="phm-num mt-0.5 block text-label text-redup">
            #{r.id} · <span className="text-brand-aksen">{r.work_order_number}</span>
          </span>
        </div>
      ),
    },
    {
      key: 'operator',
      header: 'Operator',
      width: '18%',
      cell: (r) => <span className="text-teks">{r.reported_by_name}</span>,
    },
    {
      key: 'qty',
      header: 'Jumlah',
      width: '110px',
      align: 'right',
      cell: (r) => (
        <span className="phm-num font-semibold text-teks">
          {r.quantity}
          <span className="ml-1 text-label font-normal text-redup">pcs</span>
        </span>
      ),
    },
    {
      key: 'decision',
      header: 'Keputusan',
      width: '180px',
      hideLabelOnMobile: true,
      cell: (r) => <StatusBadge status={r.status} />,
    },
    {
      key: 'review',
      header: 'Catatan & Pemeriksa',
      cell: (r) => (
        <div className="space-y-1">
          <p className="text-teks">{r.review_reason || '—'}</p>
          <p className="text-label text-redup">
            {r.reviewed_by_name || '—'}
            {r.reviewed_at ? ` · ${r.reviewed_at}` : ''}
          </p>
        </div>
      ),
    },
  ];

  return (
    <AppLayout>
      <PageHeader
        title="Riwayat Verifikasi"
        description="Laporan produksi yang sudah Anda periksa, beserta keputusan dan alasannya."
      />

      <DataTable
        columns={columns}
        rows={reviewedReports}
        getKey={(r) => r.id}
        emptyIcon={History}
        emptyTitle="Belum ada riwayat verifikasi"
        emptyMessage="Laporan yang sudah Anda setujui atau kembalikan untuk diperbaiki akan muncul di sini."
      />
    </AppLayout>
  );
}
