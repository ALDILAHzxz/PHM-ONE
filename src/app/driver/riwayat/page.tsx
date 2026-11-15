'use client';

import React from 'react';
import { AppLayout } from '../../../components/layout/AppLayout';
import { PageHeader } from '../../../components/ui/PageHeader';
import { StatusBadge } from '../../../components/ui/StatusBadge';
import { DataTable, DataColumn } from '../../../components/ui/DataTable';
import { useAuth } from '../../../context/AuthContext';
import { useOperations } from '../../../context/OperationsContext';
import { Shipment } from '../../../types';
import { History } from 'lucide-react';

export default function DriverHistoryPage() {
  const { user } = useAuth();
  const { shipments } = useOperations();

  const deliveredShipments = shipments.filter(
    (s) =>
      (s.driver_id === user?.id || user?.roles.includes('sysadmin')) && s.status === 'delivered'
  );

  const columns: DataColumn<Shipment>[] = [
    {
      key: 'partner',
      header: 'Tujuan & Surat Jalan',
      width: '30%',
      primary: true,
      hideLabelOnMobile: true,
      cell: (s) => (
        <div>
          <span className="block font-semibold text-teks">{s.customer_name}</span>
          <span className="phm-num mt-0.5 block text-label text-brand-aksen">
            {s.shipment_number}
          </span>
        </div>
      ),
    },
    {
      key: 'product',
      header: 'Komponen',
      cell: (s) => <span className="text-teks">{s.product_name}</span>,
    },
    {
      key: 'qty',
      header: 'Jumlah',
      width: '110px',
      align: 'right',
      cell: (s) => (
        <span className="phm-num font-semibold text-teks">
          {s.quantity}
          <span className="ml-1 text-label font-normal text-redup">pcs</span>
        </span>
      ),
    },
    {
      key: 'vehicle',
      header: 'Kendaraan',
      width: '150px',
      cell: (s) => <span className="phm-num text-teks">{s.vehicle_plate || '—'}</span>,
    },
    {
      key: 'delivered',
      header: 'Serah Terima',
      width: '170px',
      cell: (s) => <span className="phm-num text-teks">{s.delivered_at || '—'}</span>,
    },
    {
      key: 'status',
      header: 'Status',
      width: '150px',
      align: 'right',
      hideLabelOnMobile: true,
      cell: (s) => <StatusBadge status={s.status} />,
    },
  ];

  return (
    <AppLayout>
      <PageHeader
        title="Riwayat Pengiriman"
        description="Pengantaran yang sudah selesai diserahterimakan kepada mitra."
      />

      <DataTable
        columns={columns}
        rows={deliveredShipments}
        getKey={(s) => s.id}
        emptyIcon={History}
        emptyTitle="Belum ada pengiriman selesai"
        emptyMessage="Pengantaran yang sudah Anda selesaikan akan tercatat di sini."
      />
    </AppLayout>
  );
}
