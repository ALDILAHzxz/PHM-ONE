'use client';

import React, { useState } from 'react';
import { AppLayout } from '../../components/layout/AppLayout';
import { PageHeader } from '../../components/ui/PageHeader';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { useOperations } from '../../context/OperationsContext';
import Link from 'next/link';
import { PlusCircle, Search, Filter, ArrowRight } from 'lucide-react';

export default function OrdersListPage() {
  const { orders, getOrderProductionProgress } = useOperations();
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('ALL');

  const filteredOrders = orders.filter((o) => {
    const matchQuery =
      o.order_number.toLowerCase().includes(search.toLowerCase()) ||
      o.partner_name.toLowerCase().includes(search.toLowerCase()) ||
      o.product_name.toLowerCase().includes(search.toLowerCase()) ||
      o.po_number.toLowerCase().includes(search.toLowerCase());

    const matchStatus = statusFilter === 'ALL' || o.status === statusFilter;
    return matchQuery && matchStatus;
  });

  return (
    <AppLayout>
      <PageHeader
        title="Daftar Pesanan Mitra"
        description="Kelola dan pantau seluruh pesanan komponen presisi dari mitra industri PT PHM."
        action={
          <Link href="/pesanan/tambah" className="phm-btn-primary text-xs">
            <PlusCircle className="w-3.5 h-3.5" />
            <span>Tambah Pesanan Baru</span>
          </Link>
        }
      />

      {/* Filter and Search Bar */}
      <div className="phm-card mb-6 p-4">
        <div className="flex flex-col sm:flex-row gap-3">
          <div className="relative flex-1">
            <Search className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Cari nomor pesanan, PO, mitra, atau nama produk…"
              className="phm-input pl-10 text-xs"
            />
          </div>
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="phm-input sm:w-48 text-xs"
          >
            <option value="ALL">Semua Status</option>
            <option value="confirmed">Terkonfirmasi</option>
            <option value="in_production">Sedang Diproduksi</option>
            <option value="ready_to_ship">Siap Dikirim</option>
            <option value="completed">Selesai</option>
          </select>
        </div>
      </div>

      {/* Orders Table */}
      <div className="phm-card overflow-hidden p-0">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 font-bold uppercase tracking-wider text-[0.6875rem]">
              <tr>
                <th className="py-3 px-4">No. Pesanan</th>
                <th className="py-3 px-4">Mitra Pemesan & PO</th>
                <th className="py-3 px-4">Produk Komponen</th>
                <th className="py-3 px-4 text-center">Jumlah Target</th>
                <th className="py-3 px-4">Progres Verifikasi</th>
                <th className="py-3 px-4">Tenggat Waktu</th>
                <th className="py-3 px-4 text-right">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {filteredOrders.length === 0 ? (
                <tr>
                  <td colSpan={7} className="py-8 text-center text-slate-400">
                    Tidak ada pesanan yang sesuai dengan filter pencarian.
                  </td>
                </tr>
              ) : (
                filteredOrders.map((order) => {
                  const prog = getOrderProductionProgress(order.id);
                  return (
                    <tr key={order.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                      <td className="py-3.5 px-4 font-mono font-bold text-[#0077C8]">
                        {order.order_number}
                      </td>
                      <td className="py-3.5 px-4">
                        <div className="font-bold text-slate-900 dark:text-white">{order.partner_name}</div>
                        <div className="text-[0.6875rem] text-slate-400 font-mono">{order.po_number}</div>
                      </td>
                      <td className="py-3.5 px-4 font-semibold text-slate-800 dark:text-slate-200">
                        {order.product_name}
                      </td>
                      <td className="py-3.5 px-4 text-center font-extrabold text-sm text-slate-900 dark:text-white">
                        {order.quantity} pcs
                      </td>
                      <td className="py-3.5 px-4 min-w-[140px]">
                        <div className="space-y-1">
                          <div className="flex justify-between text-[0.6875rem] text-slate-500">
                            <span>{prog.verified} / {prog.target}</span>
                            <span className="font-bold">{prog.percentage}%</span>
                          </div>
                          <div className="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-700 overflow-hidden">
                            <div
                              className="h-full bg-emerald-500 rounded-full"
                              style={{ width: `${prog.percentage}%` }}
                            />
                          </div>
                        </div>
                      </td>
                      <td className="py-3.5 px-4 text-slate-600 dark:text-slate-300 font-medium">
                        {order.requested_delivery_date}
                      </td>
                      <td className="py-3.5 px-4 text-right">
                        <StatusBadge status={order.status} />
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>
    </AppLayout>
  );
}
