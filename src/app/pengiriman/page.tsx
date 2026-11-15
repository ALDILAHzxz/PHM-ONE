'use client';

import React from 'react';
import Link from 'next/link';
import { AppLayout } from '../../components/layout/AppLayout';
import { PageHeader } from '../../components/ui/PageHeader';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { DataTable, DataColumn } from '../../components/ui/DataTable';
import { useOperations } from '../../context/OperationsContext';
import { Shipment } from '../../types';
import { Truck, PlusCircle } from 'lucide-react';

export default function ShipmentsListPage() {
  const { shipments } = useOperations();

  const columns: DataColumn<Shipment>[] = [
    {
      key: 'partner',
      header: 'Mitra & Surat Jalan',
      width: '26%',
      primary: true,
      hideLabelOnMobile: true,
      cell: (s) => (
        <div>
          <span className="block font-semibold text-teks">{s.customer_name}</span>
          <span className="phm-num mt-0.5 block text-label text-redup">
            <span className="text-brand-aksen">{s.shipment_number}</span> · {s.order_number}
          </span>
        </div>
      ),
    },
    {
      key: 'product',
      header: 'Komponen',
      width: '22%',
      cell: (s) => <span className="text-teks">{s.product_name}</span>,
    },
    {
      key: 'qty',
      header: 'Muatan',
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
      key: 'fleet',
      header: 'Armada & Supir',
      width: '18%',
      cell: (s) => (
        <div>
          <span className="block text-teks">{s.driver_name || '—'}</span>
          <span className="phm-num mt-0.5 block text-label text-redup">
            {s.vehicle_plate || 'Kendaraan belum ditentukan'}
          </span>
        </div>
      ),
    },
    {
      key: 'destination',
      header: 'Tujuan',
      cell: (s) => <span className="text-teks">{s.destination_address}</span>,
    },
    {
      key: 'status',
      header: 'Status',
      width: '180px',
      align: 'right',
      hideLabelOnMobile: true,
      cell: (s) => <StatusBadge status={s.status} />,
    },
  ];

  return (
    <AppLayout>
      <PageHeader
        title="Daftar Pengiriman"
        description="Jadwal keberangkatan armada dan penerbitan surat jalan."
        action={
          <Link href="/pengiriman/catat" className="phm-btn-primary">
            <PlusCircle className="h-[1.125rem] w-[1.125rem]" aria-hidden="true" />
            <span>Buat Surat Jalan</span>
          </Link>
        }
      />

      <DataTable
        columns={columns}
        rows={shipments}
        getKey={(s) => s.id}
        emptyIcon={Truck}
        emptyTitle="Belum ada pengiriman"
        emptyMessage="Surat jalan yang Anda terbitkan akan muncul di sini."
      />
    </AppLayout>
  );
}
