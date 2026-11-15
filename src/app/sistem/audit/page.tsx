'use client';

import React from 'react';
import { AppLayout } from '../../../components/layout/AppLayout';
import { PageHeader } from '../../../components/ui/PageHeader';
import { DataTable } from '../../../components/ui/DataTable';
import { useOperations } from '../../../context/OperationsContext';
import { FileText } from 'lucide-react';

export default function SystemAuditPage() {
  const { auditLogs } = useOperations();

  return (
    <AppLayout>
      <PageHeader
        title="Jejak Audit Operasional (Audit Trail)"
        description="Perekaman kronologis setiap transaksi, mutasi laporan, verifikasi supervisor, dan pengiriman barang."
      />

      <DataTable
        columns={[
          {
            key: 'time',
            header: 'Waktu',
            width: '170px',
            cell: (log) => <span className="phm-num text-redup">{log.timestamp}</span>,
          },
          {
            key: 'actor',
            header: 'Pelaku',
            width: '18%',
            primary: true,
            hideLabelOnMobile: true,
            cell: (log) => (
              <div>
                <span className="block font-semibold text-teks">{log.user_name}</span>
                <span className="phm-num mt-0.5 block text-label text-brand-aksen">
                  {log.action}
                </span>
              </div>
            ),
          },
          {
            key: 'details',
            header: 'Aktivitas',
            cell: (log) => <span className="text-teks">{log.details}</span>,
          },
        ]}
        rows={auditLogs}
        getKey={(log) => log.id}
        emptyIcon={FileText}
        emptyTitle="Belum ada kejadian tercatat"
      />
    </AppLayout>
  );
}
