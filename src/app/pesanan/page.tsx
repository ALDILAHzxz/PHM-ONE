'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { AppLayout } from '../../components/layout/AppLayout';
import { PageHeader } from '../../components/ui/PageHeader';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { DataTable, DataColumn } from '../../components/ui/DataTable';
import { ProgressBar } from '../../components/ui/ProgressBar';
import { useOperations } from '../../context/OperationsContext';
import { CustomerOrder } from '../../types';
import { PlusCircle, Search, Package } from 'lucide-react';

export default function OrdersListPage() {
  const { orders, getOrderProductionProgress } = useOperations();
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('ALL');

  const filteredOrders = orders.filter((o) => {
    const q = search.toLowerCase();
    const matchQuery =
      o.order_number.toLowerCase().includes(q) ||
      o.partner_name.toLowerCase().includes(q) ||
      o.product_name.toLowerCase().includes(q) ||
      o.po_number.toLowerCase().includes(q);

    const matchStatus = statusFilter === 'ALL' || o.status === statusFilter;
    return matchQuery && matchStatus;
  });

  const columns: DataColumn<CustomerOrder>[] = [
    {
      key: 'partner',
      header: 'Mitra & Pesanan',
      width: '28%',
      primary: true,
      hideLabelOnMobile: true,
      cell: (o) => (
        <div>
          <span className="block font-semibold text-teks">{o.partner_name}</span>
          <span className="phm-num mt-0.5 block text-label text-redup">
            <span className="text-brand-aksen">{o.order_number}</span> · PO {o.po_number}
          </span>
        </div>
      ),
    },
    {
      key: 'product',
      header: 'Produk',
      width: '22%',
      cell: (o) => <span className="text-teks">{o.product_name}</span>,
    },
    {
      key: 'qty',
      header: 'Dipesan',
      width: '110px',
      align: 'right',
      cell: (o) => (
        <span className="phm-num font-semibold text-teks">
          {o.quantity}
          <span className="ml-1 text-label font-normal text-redup">pcs</span>
        </span>
      ),
    },
    {
      key: 'progress',
      header: 'Produksi Terverifikasi',
      width: '190px',
      cell: (o) => {
        const prog = getOrderProductionProgress(o.id);
        return (
          <ProgressBar value={prog.verified} max={prog.target} caption={`${prog.percentage}%`} />
        );
      },
    },
    {
      key: 'due',
      header: 'Tenggat',
      width: '130px',
      cell: (o) => <span className="phm-num text-teks">{o.requested_delivery_date}</span>,
    },
    {
      key: 'status',
      header: 'Status',
      width: '180px',
      align: 'right',
      hideLabelOnMobile: true,
      cell: (o) => <StatusBadge status={o.status} />,
    },
  ];

  return (
    <AppLayout>
      <PageHeader
        title="Pesanan Mitra"
        description="Seluruh pesanan komponen dari mitra industri PT Putra Hatim Mandiri."
        action={
          <Link href="/pesanan/tambah" className="phm-btn-primary">
            <PlusCircle className="h-[1.125rem] w-[1.125rem]" aria-hidden="true" />
            <span>Tambah Pesanan</span>
          </Link>
        }
      />

      <div className="phm-card mb-5">
        <div className="flex flex-col gap-3 sm:flex-row">
          <div className="relative flex-1">
            <label htmlFor="cari-pesanan" className="sr-only">
              Cari pesanan
            </label>
            <Search
              className="pointer-events-none absolute left-3 top-1/2 h-[1.125rem] w-[1.125rem] -translate-y-1/2 text-redup"
              aria-hidden="true"
            />
            <input
              id="cari-pesanan"
              type="search"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Cari nomor pesanan, PO, mitra, atau produk…"
              className="phm-input pl-10"
            />
          </div>
          <div className="sm:w-56">
            <label htmlFor="filter-status" className="sr-only">
              Filter status
            </label>
            <select
              id="filter-status"
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="phm-select"
            >
              <option value="ALL">Semua status</option>
              <option value="confirmed">Terkonfirmasi</option>
              <option value="in_production">Sedang Diproduksi</option>
              <option value="ready_to_ship">Siap Dikirim</option>
              <option value="partially_shipped">Sebagian Terkirim</option>
              <option value="completed">Selesai</option>
            </select>
          </div>
        </div>
      </div>

      <DataTable
        columns={columns}
        rows={filteredOrders}
        getKey={(o) => o.id}
        emptyIcon={Package}
        emptyTitle="Tidak ada pesanan"
        emptyMessage={
          search || statusFilter !== 'ALL'
            ? 'Tidak ada pesanan yang cocok dengan pencarian Anda.'
            : 'Belum ada pesanan mitra yang tercatat.'
        }
      />
    </AppLayout>
  );
}
