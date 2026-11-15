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
          <h3 className="text-base font-bold text-teks dark:text-white">Tidak ada pengiriman aktif</h3>
          <p className="text-label text-redup mt-1">Seluruh surat jalan telah diantarkan ke tujuan atau belum ada penugasan baru.</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {activeShipments.map((s) => (
            <div key={s.id} className="phm-card flex flex-col justify-between">
              <div>
                <div className="flex items-center justify-between mb-2">
                  <span className="font-mono text-label font-bold text-brand-aksen">{s.shipment_number}</span>
                  <StatusBadge status={s.status} />
                </div>

                <h3 className="text-base font-bold text-teks mb-1">
                  {s.customer_name}
                </h3>
                <div className="text-label font-semibold text-teks">
                  {s.product_name} · <strong className="text-teks">{s.quantity} pcs</strong>
                </div>

                <div className="my-3 p-3 bg-kartu-dalam rounded-kontrol border border-garis space-y-1 text-label">
                  <div className="text-redup">Tujuan: <strong className="text-teks">{s.destination_address}</strong></div>
                  <div className="text-redup">Kendaraan: <strong className="text-teks">{s.vehicle_plate}</strong></div>
                  {s.notes && <div className="text-redup italic mt-1">&ldquo;{s.notes}&rdquo;</div>}
                </div>
              </div>

              {/* Status Action Buttons */}
              <div className="pt-3 border-t border-garis flex flex-wrap gap-2">
                {s.status === 'pending' && (
                  <button
                    onClick={() => handleUpdate(s.id, 'loaded')}
                    className="flex-1 py-2 bg-brand hover:bg-brand-pekat text-white rounded-kontrol text-label font-semibold flex items-center justify-center gap-1.5 cursor-pointer"
                  >
                    <PackageCheck className="h-[1.125rem] w-[1.125rem] shrink-0" />
                    <span>Barang Selesai Dimuat</span>
                  </button>
                )}

                {s.status === 'loaded' && (
                  <button
                    onClick={() => handleUpdate(s.id, 'in_transit')}
                    className="flex-1 py-2 bg-brand hover:bg-brand-pekat text-white rounded-kontrol text-label font-semibold flex items-center justify-center gap-1.5 cursor-pointer"
                  >
                    <Navigation className="h-[1.125rem] w-[1.125rem] shrink-0" />
                    <span>Berangkat Mengantar (Dalam Rute)</span>
                  </button>
                )}

                {s.status === 'in_transit' && (
                  <>
                    <button
                      onClick={() => handleUpdate(s.id, 'delivered')}
                      className="flex-1 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-kontrol text-label font-semibold flex items-center justify-center gap-1.5 cursor-pointer"
                    >
                      <CheckCircle2 className="h-[1.125rem] w-[1.125rem] shrink-0" />
                      <span>Konfirmasi Barang Diterima Customer</span>
                    </button>
                    <button
                      onClick={() => handleUpdate(s.id, 'failed')}
                      className="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-kontrol text-label font-semibold cursor-pointer border border-rose-200"
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
