'use client';

import React from 'react';
import { AppLayout } from '../../../components/layout/AppLayout';
import { PageHeader } from '../../../components/ui/PageHeader';
import { StatusBadge } from '../../../components/ui/StatusBadge';
import { useAuth } from '../../../context/AuthContext';
import { useOperations } from '../../../context/OperationsContext';

export default function DriverHistoryPage() {
  const { user } = useAuth();
  const { shipments } = useOperations();

  const deliveredShipments = shipments.filter(
    (s) => (s.driver_id === user?.id || user?.roles.includes('sysadmin')) && s.status === 'delivered'
  );

  return (
    <AppLayout>
      <PageHeader
        title="Riwayat Pengiriman Selesai"
        description="Arsip seluruh surat jalan dan pengantaran barang yang telah sukses diserahterimakan kepada mitra."
      />

      <div className="phm-card overflow-hidden p-0">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 font-bold uppercase tracking-wider text-[0.6875rem]">
              <tr>
                <th className="py-3 px-4">No. Pengiriman</th>
                <th className="py-3 px-4">Tujuan Mitra</th>
                <th className="py-3 px-4">Komponen</th>
                <th className="py-3 px-4 text-center">Jumlah</th>
                <th className="py-3 px-4">Kendaraan</th>
                <th className="py-3 px-4 text-right">Waktu Serah Terima</th>
                <th className="py-3 px-4 text-right">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {deliveredShipments.length === 0 ? (
                <tr>
                  <td colSpan={7} className="py-8 text-center text-slate-400">
                    Belum ada riwayat pengiriman selesai.
                  </td>
                </tr>
              ) : (
                deliveredShipments.map((s) => (
                  <tr key={s.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                    <td className="py-3.5 px-4 font-mono font-bold text-[#0077C8]">
                      {s.shipment_number}
                    </td>
                    <td className="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                      {s.customer_name}
                    </td>
                    <td className="py-3.5 px-4 text-slate-700 dark:text-slate-300 font-medium">
                      {s.product_name}
                    </td>
                    <td className="py-3.5 px-4 text-center font-extrabold text-sm">
                      {s.quantity} pcs
                    </td>
                    <td className="py-3.5 px-4 text-slate-500 font-mono">
                      {s.vehicle_plate}
                    </td>
                    <td className="py-3.5 px-4 text-right text-slate-500">
                      {s.delivered_at || '15/11/2026 14:00'}
                    </td>
                    <td className="py-3.5 px-4 text-right">
                      <StatusBadge status={s.status} />
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </AppLayout>
  );
}
