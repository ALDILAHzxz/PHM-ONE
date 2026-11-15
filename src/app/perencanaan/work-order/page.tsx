'use client';

import React, { useState } from 'react';
import { AppLayout } from '../../../components/layout/AppLayout';
import { PageHeader } from '../../../components/ui/PageHeader';
import { StatusBadge } from '../../../components/ui/StatusBadge';
import { DataTable } from '../../../components/ui/DataTable';
import { useAuth } from '../../../context/AuthContext';
import { useOperations } from '../../../context/OperationsContext';
import { PlusCircle, Layers, CheckCircle2 } from 'lucide-react';

export default function WorkOrderManagerPage() {
  const { user } = useAuth();
  const { orders, workOrders, addWorkOrder } = useOperations();

  const [orderId, setOrderId] = useState<number>(orders[0]?.id || 1);
  const [targetQuantity, setTargetQuantity] = useState<number | ''>('');
  const [priority, setPriority] = useState<'low' | 'normal' | 'high' | 'urgent'>('normal');
  const [deadline, setDeadline] = useState('2026-11-28');
  const [notes, setNotes] = useState('');
  const [isSuccess, setIsSuccess] = useState(false);

  const selectedOrder = orders.find((o) => o.id === Number(orderId));

  const handleCreateWO = (e: React.FormEvent) => {
    e.preventDefault();
    if (!targetQuantity || Number(targetQuantity) <= 0) {
      alert('Target jumlah harus lebih besar dari 0.');
      return;
    }

    addWorkOrder({
      order_id: Number(orderId),
      order_number: selectedOrder?.order_number || 'ORD-GEN',
      product_name: selectedOrder?.product_name || 'Komponen',
      target_quantity: Number(targetQuantity),
      status: 'in_progress',
      priority,
      deadline,
      notes,
      created_by: user?.name || 'Agus Wijaya',
    });

    setIsSuccess(true);
    setTargetQuantity('');
    setNotes('');
    setTimeout(() => setIsSuccess(false), 3000);
  };

  return (
    <AppLayout>
      <PageHeader
        title="Manajemen Work Order (Perintah Kerja)"
        description="Terbitkan work order resmi dari pesanan pelanggan untuk dialokasikan ke stasiun kerja pabrik."
      />

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Create WO Form */}
        <div className="phm-card lg:col-span-1">
          <h2 className="text-base font-bold text-teks mb-4">
            Terbitkan Work Order Baru
          </h2>

          {isSuccess && (
            <div className="mb-4 p-3 rounded-kontrol bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-200 text-label font-semibold flex items-center gap-2">
              <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
              <span>Work Order berhasil diterbitkan!</span>
            </div>
          )}

          <form onSubmit={handleCreateWO} className="space-y-4">
            <div>
              <label className="phm-label">Pilih Pesanan Pelanggan</label>
              <select
                value={orderId}
                onChange={(e) => setOrderId(Number(e.target.value))}
                className="phm-input text-label"
                required
              >
                {orders.map((o) => (
                  <option key={o.id} value={o.id}>
                    {o.order_number} — {o.product_name} ({o.quantity} pcs)
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label className="phm-label">Target Jumlah WO (pcs)</label>
              <input
                type="number"
                value={targetQuantity}
                onChange={(e) => setTargetQuantity(e.target.value === '' ? '' : Number(e.target.value))}
                placeholder={selectedOrder ? `Maksimal: ${selectedOrder.quantity}` : '500'}
                className="phm-input text-label font-bold"
                min={1}
                required
              />
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="phm-label">Prioritas</label>
                <select
                  value={priority}
                  onChange={(e) => setPriority(e.target.value as any)}
                  className="phm-input text-label"
                >
                  <option value="low">Rendah</option>
                  <option value="normal">Normal</option>
                  <option value="high">Tinggi</option>
                  <option value="urgent">Mendesak</option>
                </select>
              </div>

              <div>
                <label className="phm-label">Tenggat Selesai</label>
                <input
                  type="date"
                  value={deadline}
                  onChange={(e) => setDeadline(e.target.value)}
                  className="phm-input text-label"
                  required
                />
              </div>
            </div>

            <div>
              <label className="phm-label">Instruksi Khusus Lini</label>
              <textarea
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                placeholder="Catatan pengerjaan…"
                rows={2}
                className="phm-input text-label"
              />
            </div>

            <button
              type="submit"
              className="w-full h-10 bg-brand hover:bg-brand-pekat text-white font-semibold text-label rounded-kontrol flex items-center justify-center gap-1.5 transition-colors cursor-pointer"
            >
              <PlusCircle className="h-[1.125rem] w-[1.125rem] shrink-0" />
              <span>Rilis Work Order</span>
            </button>
          </form>
        </div>

        {/* Existing WOs Table */}
        <div className="lg:col-span-2">
          <h2 className="phm-section mb-3">Daftar Work Order Aktif</h2>
          <DataTable
            columns={[
              {
                key: 'wo',
                header: 'Work Order & Komponen',
                width: '34%',
                primary: true,
                hideLabelOnMobile: true,
                cell: (wo) => (
                  <div>
                    <span className="block font-semibold text-teks">{wo.product_name}</span>
                    <span className="phm-num mt-0.5 block text-label text-redup">
                      <span className="text-brand-aksen">{wo.work_order_number}</span> ·{' '}
                      {wo.order_number}
                    </span>
                  </div>
                ),
              },
              {
                key: 'target',
                header: 'Target',
                width: '110px',
                align: 'right',
                cell: (wo) => (
                  <span className="phm-num font-semibold text-teks">
                    {wo.target_quantity}
                    <span className="ml-1 text-label font-normal text-redup">pcs</span>
                  </span>
                ),
              },
              {
                key: 'priority',
                header: 'Prioritas',
                width: '130px',
                cell: (wo) => (
                  <span
                    className={`phm-badge ${
                      wo.priority === 'urgent' || wo.priority === 'high'
                        ? 'phm-badge-waspada'
                        : 'phm-badge-netral'
                    }`}
                  >
                    {wo.priority}
                  </span>
                ),
              },
              {
                key: 'deadline',
                header: 'Batas Waktu',
                width: '140px',
                cell: (wo) => <span className="phm-num text-teks">{wo.deadline}</span>,
              },
              {
                key: 'status',
                header: 'Status',
                width: '160px',
                align: 'right',
                hideLabelOnMobile: true,
                cell: (wo) => <StatusBadge status={wo.status} />,
              },
            ]}
            rows={workOrders}
            getKey={(wo) => wo.id}
            emptyIcon={Layers}
            emptyTitle="Belum ada work order"
            emptyMessage="Work order yang Anda buat akan muncul di sini."
          />
        </div>
      </div>
    </AppLayout>
  );
}
