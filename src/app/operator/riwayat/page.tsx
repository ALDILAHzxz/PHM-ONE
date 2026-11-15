'use client';

import React from 'react';
import Link from 'next/link';
import { AppLayout } from '../../../components/layout/AppLayout';
import { PageHeader } from '../../../components/ui/PageHeader';
import { StatusBadge } from '../../../components/ui/StatusBadge';
import { DataTable, DataColumn } from '../../../components/ui/DataTable';
import { useAuth } from '../../../context/AuthContext';
import { useOperations } from '../../../context/OperationsContext';
import { ProductionReport } from '../../../types';
import { PlusCircle, History } from 'lucide-react';

export default function OperatorHistoryPage() {
  const { user } = useAuth();
  const { reports } = useOperations();

  const myReports = reports.filter(
    (r) =>
      r.reported_by_id === user?.id ||
      user?.roles.includes('sysadmin') ||
      user?.roles.includes('supervisor')
  );

  /**
   * Tujuh kolom sempit sebelumnya diringkas menjadi lima kolom yang punya
   * lebar tetap. Nama komponen tidak lagi pecah jadi empat baris, dan catatan
   * operator digabung dengan hasil review supervisor karena keduanya adalah
   * satu percakapan tentang laporan yang sama.
   */
  const columns: DataColumn<ProductionReport>[] = [
    {
      key: 'id',
      header: 'Laporan',
      width: '150px',
      cell: (r) => (
        <div>
          <span className="phm-num font-semibold text-teks">#{r.id}</span>
          <span className="mt-0.5 block text-label text-redup">{r.created_at}</span>
        </div>
      ),
    },
    {
      key: 'job',
      header: 'Pekerjaan',
      width: '26%',
      primary: true,
      hideLabelOnMobile: true,
      cell: (r) => (
        <div>
          <span className="block font-semibold text-teks">{r.product_name}</span>
          <span className="phm-num mt-0.5 block text-label text-brand-aksen">
            {r.work_order_number}
          </span>
        </div>
      ),
    },
    {
      key: 'qty',
      header: 'Jumlah',
      width: '120px',
      align: 'right',
      cell: (r) => (
        <span className="phm-num text-[1.0625rem] font-bold text-teks">
          +{r.quantity}
          <span className="ml-1 text-label font-normal text-redup">pcs</span>
        </span>
      ),
    },
    {
      key: 'status',
      header: 'Status',
      width: '200px',
      hideLabelOnMobile: true,
      cell: (r) => <StatusBadge status={r.status} />,
    },
    {
      key: 'notes',
      header: 'Catatan & Hasil Pemeriksaan',
      cell: (r) => (
        <div className="space-y-1.5">
          {r.notes && <p className="text-teks">{r.notes}</p>}

          {r.issue_category && (
            <p className="text-label font-semibold text-kritis-teks">
              Kendala: {r.issue_category}
            </p>
          )}

          {r.reviewed_by_name ? (
            <p className="text-label text-redup">
              <span className="font-semibold text-teks">{r.reviewed_by_name}</span>
              {r.review_reason ? ` — ${r.review_reason}` : ''}
            </p>
          ) : (
            <p className="text-label italic text-redup">Menunggu pemeriksaan supervisor</p>
          )}
        </div>
      ),
    },
  ];

  return (
    <AppLayout>
      <PageHeader
        title="Riwayat Laporan"
        description="Hasil pengerjaan yang pernah Anda kirim beserta status pemeriksaannya."
        action={
          <Link href="/operator/catat-hasil" className="phm-btn phm-btn-primary">
            <PlusCircle className="h-[1.125rem] w-[1.125rem]" aria-hidden="true" />
            <span>Buat Laporan</span>
          </Link>
        }
      />

      <DataTable
        columns={columns}
        rows={myReports}
        getKey={(r) => r.id}
        emptyIcon={History}
        emptyTitle="Belum ada laporan"
        emptyMessage="Hasil pekerjaan yang Anda catat akan muncul di sini beserta status pemeriksaannya."
      />
    </AppLayout>
  );
}
