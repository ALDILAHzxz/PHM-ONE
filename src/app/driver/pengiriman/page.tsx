'use client';

import React from 'react';
import { AppLayout } from '../../../components/layout/AppLayout';
import { PageHeader } from '../../../components/ui/PageHeader';
import { StatusBadge } from '../../../components/ui/StatusBadge';
import { useAuth } from '../../../context/AuthContext';
import { useOperations } from '../../../context/OperationsContext';
import { Truck, CheckCircle2, Navigation, PackageCheck, AlertCircle } from 'lucide-react';

export default function DriverDeliveriesPage() {
  const { user } = useAuth();
  const { shipments, updateShipmentStatus } = useOperations();

  // Show shipments assigned to this driver or all for admin
  const myShipments = shipments.filter(
    (s) => s.driver_id === user?.id || user?.roles.includes('sysadmin') || user?.roles.includes('warehouse')
  );

  const activeShipments = myShipments.filter((s) => s.status !== 'delivered' && s.status !== 'cancelled');

  const handleUpdate = (id: number, nextStatus: any) => {
    updateShipmentStatus(id, nextStatus);
  };

  return (
    <AppLayout>
      <PageHeader
        title="Antrean Tugas Pengiriman Driver"
        description="Daftar surat jalan muatan barang yang ditugaskan ke armada truk Anda hari ini."
      />

      {activeShipments.length === 0 ? (
        <div className="phm-card text-center py-12">
          <Truck className="w-12 h-12 text-slate-300 mx-auto mb-3" />
          <h3 className="text-base font-bold text-slate-800 dark:text-white">Tidak ada pengiriman aktif</h3>
          <p className="text-xs text-slate-500 mt-1">Seluruh surat jalan telah diantarkan ke tujuan atau belum ada penugasan baru.</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {activeShipments.map((s) => (
            <div key={s.id} className="phm-card flex flex-col justify-between">
              <div>
                <div className="flex items-center justify-between mb-2">
                  <span className="font-mono text-xs font-bold text-[#0077C8]">{s.shipment_number}</span>
                  <StatusBadge status={s.status} />
                </div>

                <h3 className="text-base font-bold text-slate-900 dark:text-white mb-1">
                  {s.customer_name}
                </h3>
                <div className="text-xs font-semibold text-slate-600 dark:text-slate-300">
                  {s.product_name} · <strong className="text-slate-900 dark:text-white">{s.quantity} pcs</strong>
                </div>

                <div className="my-3 p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-100 dark:border-slate-800 space-y-1 text-xs">
                  <div className="text-slate-500">Tujuan: <strong className="text-slate-800 dark:text-slate-200">{s.destination_address}</strong></div>
                  <div className="text-slate-500">Kendaraan: <strong className="text-slate-800 dark:text-slate-200">{s.vehicle_plate}</strong></div>
                  {s.notes && <div className="text-slate-500 italic mt-1">&ldquo;{s.notes}&rdquo;</div>}
                </div>
              </div>

              {/* Status Action Buttons */}
              <div className="pt-3 border-t border-slate-100 dark:border-slate-800 flex flex-wrap gap-2">
                {s.status === 'pending' && (
                  <button
                    onClick={() => handleUpdate(s.id, 'loaded')}
                    className="flex-1 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold flex items-center justify-center gap-1.5 cursor-pointer"
                  >
                    <PackageCheck className="w-3.5 h-3.5" />
                    <span>Barang Selesai Dimuat</span>
                  </button>
                )}

                {s.status === 'loaded' && (
                  <button
                    onClick={() => handleUpdate(s.id, 'in_transit')}
                    className="flex-1 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold flex items-center justify-center gap-1.5 cursor-pointer"
                  >
                    <Navigation className="w-3.5 h-3.5" />
                    <span>Berangkat Mengantar (Dalam Rute)</span>
                  </button>
                )}

                {s.status === 'in_transit' && (
                  <>
                    <button
                      onClick={() => handleUpdate(s.id, 'delivered')}
                      className="flex-1 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold flex items-center justify-center gap-1.5 cursor-pointer"
                    >
                      <CheckCircle2 className="w-3.5 h-3.5" />
                      <span>Konfirmasi Barang Diterima Customer</span>
                    </button>
                    <button
                      onClick={() => handleUpdate(s.id, 'failed')}
                      className="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-xl text-xs font-semibold cursor-pointer border border-rose-200"
                    >
                      <span>Gagal Antar</span>
                    </button>
                  </>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </AppLayout>
  );
}
