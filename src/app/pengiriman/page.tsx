'use client';

import React from 'react';
import { AppLayout } from '../../components/layout/AppLayout';
import { PageHeader } from '../../components/ui/PageHeader';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { useOperations } from '../../context/OperationsContext';
import Link from 'next/link';
import { Truck, PlusCircle, ArrowRight } from 'lucide-react';

export default function ShipmentsListPage() {
  const { shipments } = useOperations();

  return (
    <AppLayout>
      <PageHeader
        title="Daftar Pengiriman Barang & Ekspedisi"
        description="Kelola jadwal keberangkatan armada logistik dan penerbitan Surat Jalan (SJ) resmi PT PHM."
        action={
          <Link href="/pengiriman/catat" className="phm-btn-primary text-xs">
            <PlusCircle className="w-3.5 h-3.5" />
            <span>Terbitkan Surat Jalan</span>
          </Link>
        }
      />

      <div className="phm-card overflow-hidden p-0">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 font-bold uppercase tracking-wider text-[0.6875rem]">
              <tr>
                <th className="py-3 px-4">No. Pengiriman</th>
                <th className="py-3 px-4">Pesanan & Mitra</th>
                <th className="py-3 px-4">Komponen</th>
                <th className="py-3 px-4 text-center">Muatan (pcs)</th>
                <th className="py-3 px-4">Armada & Supir</th>
                <th className="py-3 px-4">Tujuan Pengiriman</th>
                <th className="py-3 px-4 text-right">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {shipments.map((s) => (
                <tr key={s.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                  <td className="py-3.5 px-4 font-mono font-bold text-[#0077C8]">
                    {s.shipment_number}
                  </td>
                  <td className="py-3.5 px-4">
                    <div className="font-bold text-slate-900 dark:text-white">{s.customer_name}</div>
                    <div className="text-[0.6875rem] text-slate-400 font-mono">{s.order_number}</div>
                  </td>
                  <td className="py-3.5 px-4 font-semibold text-slate-800 dark:text-slate-200">
                    {s.product_name}
                  </td>
                  <td className="py-3.5 px-4 text-center font-extrabold text-sm">
                    {s.quantity} pcs
                  </td>
                  <td className="py-3.5 px-4 text-slate-600 dark:text-slate-300">
                    <div className="font-semibold text-slate-900 dark:text-white">{s.driver_name}</div>
                    <div className="text-[0.6875rem] text-slate-400 font-mono">{s.vehicle_plate}</div>
                  </td>
                  <td className="py-3.5 px-4 max-w-xs text-slate-600 dark:text-slate-300 truncate">
                    {s.destination_address}
                  </td>
                  <td className="py-3.5 px-4 text-right">
                    <StatusBadge status={s.status} />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </AppLayout>
  );
}
