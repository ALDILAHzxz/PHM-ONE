'use client';

import React, { useState } from 'react';
import { useRouter } from 'next/navigation';
import { AppLayout } from '../../../components/layout/AppLayout';
import { PageHeader } from '../../../components/ui/PageHeader';
import { useAuth } from '../../../context/AuthContext';
import { useOperations } from '../../../context/OperationsContext';
import { INITIAL_USERS } from '../../../data/mockData';
import { ArrowLeft, Truck, CheckCircle2 } from 'lucide-react';
import Link from 'next/link';

export default function CreateShipmentPage() {
  const router = useRouter();
  const { user } = useAuth();
  const { orders, createShipment, getOrderProductionProgress } = useOperations();

  const drivers = INITIAL_USERS.filter((u) => u.roles.includes('driver'));

  const [orderId, setOrderId] = useState<number>(orders[0]?.id || 1);
  const [driverId, setDriverId] = useState<number>(drivers[0]?.id || 6);
  const [quantity, setQuantity] = useState<number | ''>('');
  const [vehiclePlate, setVehiclePlate] = useState('B 9421 PHM');
  const [destination, setDestination] = useState('Kawasan Industri MM2100 Cikarang');
  const [notes, setNotes] = useState('');
  const [isSuccess, setIsSuccess] = useState(false);

  const selectedOrder = orders.find((o) => o.id === Number(orderId));
  const selectedDriver = drivers.find((d) => d.id === Number(driverId));
  const orderProgress = selectedOrder ? getOrderProductionProgress(selectedOrder.id) : { verified: 0, target: 0 };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!quantity || Number(quantity) <= 0) {
      alert('Jumlah barang harus lebih besar dari 0.');
      return;
    }

    createShipment({
      order_id: Number(orderId),
      order_number: selectedOrder?.order_number || 'ORD-GEN',
      customer_name: selectedOrder?.partner_name || 'Mitra PHM',
      product_name: selectedOrder?.product_name || 'Komponen Presisi',
      driver_id: Number(driverId),
      driver_name: selectedDriver?.name || 'Joko Prasetyo',
      driver_nik: selectedDriver?.employee_number || 'PHM-DRV-001',
      quantity: Number(quantity),
      status: 'pending',
      vehicle_plate: vehiclePlate,
      destination_address: destination,
      notes,
      idempotency_key: `SHP-IDEMP-${Date.now()}`,
    });

    setIsSuccess(true);
    setTimeout(() => {
      router.push('/pengiriman');
    }, 1200);
  };

  return (
    <AppLayout>
      <PageHeader
        title="Penerbitan Surat Jalan & Pengiriman"
        description="Buat dokumen ekspedisi resmi pengiriman komponen jadi yang telah lolos verifikasi QC."
      />

      <div className="max-w-2xl mx-auto">
        <Link
          href="/pengiriman"
          className="inline-flex items-center gap-1.5 text-label font-semibold text-redup hover:text-teks dark:hover:text-slate-300 mb-4 transition-colors"
        >
          <ArrowLeft className="h-[1.125rem] w-[1.125rem] shrink-0" />
          <span>Kembali ke Daftar Pengiriman</span>
        </Link>

        <div className="phm-card">
          <h2 className="text-lg font-bold text-teks mb-4">
            Formulir Surat Jalan Ekspedisi
          </h2>

          {isSuccess && (
            <div className="mb-6 p-4 rounded-kontrol bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 flex items-center gap-3 text-emerald-800 dark:text-emerald-200 text-label font-semibold animate-in fade-in">
              <CheckCircle2 className="w-5 h-5 text-emerald-600 shrink-0" />
              <span>Surat Jalan berhasil diterbitkan! Mengalihkan…</span>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="phm-label">Pilih Pesanan Mitra</label>
              <select
                value={orderId}
                onChange={(e) => setOrderId(Number(e.target.value))}
                className="phm-input"
                required
              >
                {orders.map((o) => (
                  <option key={o.id} value={o.id}>
                    {o.order_number} — {o.partner_name} ({o.product_name})
                  </option>
                ))}
              </select>
            </div>

            {selectedOrder && (
              <div className="p-3 bg-emerald-50 dark:bg-emerald-950/30 rounded-kontrol border border-emerald-200 dark:border-emerald-900 text-label">
                <div className="text-emerald-800 dark:text-emerald-300 font-semibold">
                  Stok Produksi Terverifikasi: <strong>{orderProgress.verified} pcs</strong> (dari target {orderProgress.target} pcs)
                </div>
              </div>
            )}

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="phm-label">Jumlah Muatan (pcs)</label>
                <input
                  type="number"
                  value={quantity}
                  onChange={(e) => setQuantity(e.target.value === '' ? '' : Number(e.target.value))}
                  placeholder="Contoh: 100"
                  className="phm-input font-bold"
                  min={1}
                  required
                />
              </div>

              <div>
                <label className="phm-label">Supir / Driver Ekspedisi</label>
                <select
                  value={driverId}
                  onChange={(e) => setDriverId(Number(e.target.value))}
                  className="phm-input"
                  required
                >
                  {drivers.map((d) => (
                    <option key={d.id} value={d.id}>
                      {d.name} ({d.employee_number})
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div>
              <label className="phm-label">Nomor Polisi Kendaraan (Plat Nomor)</label>
              <input
                type="text"
                value={vehiclePlate}
                onChange={(e) => setVehiclePlate(e.target.value)}
                placeholder="Contoh: B 9421 PHM"
                className="phm-input uppercase"
                required
              />
            </div>

            <div>
              <label className="phm-label">Alamat / Lokasi Tujuan Pengiriman</label>
              <input
                type="text"
                value={destination}
                onChange={(e) => setDestination(e.target.value)}
                placeholder="Alamat pabrik / gudang mitra…"
                className="phm-input"
                required
              />
            </div>

            <div>
              <label className="phm-label">Catatan Tambahan Surat Jalan</label>
              <textarea
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                placeholder="Instruksi penyerahan dokumen, packing list, dll."
                rows={2}
                className="phm-input text-label"
              />
            </div>

            <div className="pt-2">
              <button
                type="submit"
                disabled={isSuccess}
                className="w-full h-11 bg-brand hover:bg-brand-pekat text-white font-semibold text-label rounded-kontrol flex items-center justify-center gap-2 transition-colors cursor-pointer"
              >
                <Truck className="h-[1.125rem] w-[1.125rem] shrink-0" />
                <span>Terbitkan Surat Jalan & Serahkan ke Driver</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </AppLayout>
  );
}
