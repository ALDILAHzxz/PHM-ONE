'use client';

import React, { useState } from 'react';
import { AppLayout } from '../../../components/layout/AppLayout';
import { PageHeader } from '../../../components/ui/PageHeader';
import { StatusBadge } from '../../../components/ui/StatusBadge';
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
          <h2 className="text-base font-bold text-slate-900 dark:text-white mb-4">
            Terbitkan Work Order Baru
          </h2>

          {isSuccess && (
            <div className="mb-4 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-200 text-xs font-semibold flex items-center gap-2">
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
                className="phm-input text-xs"
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
                className="phm-input text-xs font-bold"
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
                  className="phm-input text-xs"
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
                  className="phm-input text-xs"
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
                className="phm-input text-xs"
              />
            </div>

            <button
              type="submit"
              className="w-full h-10 bg-[#0077C8] hover:bg-[#0062A3] text-white font-semibold text-xs rounded-xl flex items-center justify-center gap-1.5 transition-colors cursor-pointer"
            >
              <PlusCircle className="w-3.5 h-3.5" />
              <span>Rilis Work Order</span>
            </button>
          </form>
        </div>

        {/* Existing WOs Table */}
        <div className="phm-card lg:col-span-2 overflow-hidden p-0">
          <div className="p-4 border-b border-slate-100 dark:border-slate-800">
            <h2 className="text-base font-bold text-slate-900 dark:text-white">
              Daftar Work Order Aktif
            </h2>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 font-bold uppercase tracking-wider text-[0.6875rem]">
                <tr>
                  <th className="py-3 px-4">No. Work Order</th>
                  <th className="py-3 px-4">Komponen & Referensi</th>
                  <th className="py-3 px-4 text-center">Target (pcs)</th>
                  <th className="py-3 px-4">Prioritas</th>
                  <th className="py-3 px-4">Batas Waktu</th>
                  <th className="py-3 px-4 text-right">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                {workOrders.map((wo) => (
                  <tr key={wo.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                    <td className="py-3.5 px-4 font-mono font-bold text-[#0077C8]">
                      {wo.work_order_number}
                    </td>
                    <td className="py-3.5 px-4">
                      <div className="font-bold text-slate-900 dark:text-white">{wo.product_name}</div>
                      <div className="text-[0.6875rem] text-slate-400 font-mono">{wo.order_number}</div>
                    </td>
                    <td className="py-3.5 px-4 text-center font-extrabold text-sm">
                      {wo.target_quantity}
                    </td>
                    <td className="py-3.5 px-4">
                      <span className="text-[0.6875rem] font-bold uppercase px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                        {wo.priority}
                      </span>
                    </td>
                    <td className="py-3.5 px-4 text-slate-600 dark:text-slate-300">
                      {wo.deadline}
                    </td>
                    <td className="py-3.5 px-4 text-right">
                      <StatusBadge status={wo.status} />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
