'use client';

import React, { useState } from 'react';
import { useRouter } from 'next/navigation';
import { AppLayout } from '../../../components/layout/AppLayout';
import { PageHeader } from '../../../components/ui/PageHeader';
import { useAuth } from '../../../context/AuthContext';
import { useOperations } from '../../../context/OperationsContext';
import { ArrowLeft, PlusCircle, CheckCircle2 } from 'lucide-react';
import Link from 'next/link';

export default function AddOrderPage() {
  const router = useRouter();
  const { user } = useAuth();
  const { partners, products, addOrder } = useOperations();

  const [partnerId, setPartnerId] = useState(partners[0]?.id || 1);
  const [productId, setProductId] = useState(products[0]?.id || 1);
  const [quantity, setQuantity] = useState<number | ''>('');
  const [poNumber, setPoNumber] = useState('');
  const [deliveryDate, setDeliveryDate] = useState('2026-11-30');
  const [notes, setNotes] = useState('');
  const [isSuccess, setIsSuccess] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!quantity || Number(quantity) <= 0) {
      alert('Jumlah pesanan harus lebih besar dari 0.');
      return;
    }

    const selPartner = partners.find((p) => p.id === Number(partnerId));
    const selProduct = products.find((p) => p.id === Number(productId));

    addOrder({
      po_number: poNumber.trim() || `PO-PHM-${Date.now().toString().slice(-4)}`,
      partner_id: Number(partnerId),
      partner_name: selPartner?.name || 'Mitra PHM',
      product_id: Number(productId),
      product_name: selProduct?.name || 'Komponen Presisi',
      quantity: Number(quantity),
      status: 'confirmed',
      requested_delivery_date: deliveryDate,
      notes: notes.trim(),
      created_by: user?.name || 'Dewi Kartika',
    });

    setIsSuccess(true);
    setTimeout(() => {
      router.push('/pesanan');
    }, 1200);
  };

  return (
    <AppLayout>
      <PageHeader
        title="Input Pesanan Baru"
        description="Masukkan Purchase Order (PO) yang diterima dari perusahaan rekanan."
      />

      <div className="max-w-2xl mx-auto">
        <Link
          href="/pesanan"
          className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 mb-4 transition-colors"
        >
          <ArrowLeft className="w-3.5 h-3.5" />
          <span>Kembali ke Daftar Pesanan</span>
        </Link>

        <div className="phm-card">
          <h2 className="text-lg font-bold text-slate-900 dark:text-white mb-4">
            Formulir Pesanan Penjualan
          </h2>

          {isSuccess && (
            <div className="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 flex items-center gap-3 text-emerald-800 dark:text-emerald-200 text-xs font-semibold animate-in fade-in">
              <CheckCircle2 className="w-5 h-5 text-emerald-600 shrink-0" />
              <span>Pesanan berhasil disimpan! Mengalihkan ke daftar pesanan…</span>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="phm-label">Mitra Pemesan (Customer)</label>
              <select
                value={partnerId}
                onChange={(e) => setPartnerId(Number(e.target.value))}
                className="phm-input"
                required
              >
                {partners.map((p) => (
                  <option key={p.id} value={p.id}>
                    {p.name} ({p.code})
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label className="phm-label">Komponen / Produk Manufaktur</label>
              <select
                value={productId}
                onChange={(e) => setProductId(Number(e.target.value))}
                className="phm-input"
                required
              >
                {products.map((p) => (
                  <option key={p.id} value={p.id}>
                    {p.name} ({p.code})
                  </option>
                ))}
              </select>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="phm-label">Nomor Purchase Order (PO)</label>
                <input
                  type="text"
                  value={poNumber}
                  onChange={(e) => setPoNumber(e.target.value)}
                  placeholder="Contoh: PO-AHM-2026-9921"
                  className="phm-input"
                  required
                />
              </div>

              <div>
                <label className="phm-label">Target Jumlah (pcs)</label>
                <input
                  type="number"
                  value={quantity}
                  onChange={(e) => setQuantity(e.target.value === '' ? '' : Number(e.target.value))}
                  placeholder="Contoh: 500"
                  className="phm-input font-bold"
                  min={1}
                  required
                />
              </div>
            </div>

            <div>
              <label className="phm-label">Tenggat Waktu Pengiriman (Delivery Deadline)</label>
              <input
                type="date"
                value={deliveryDate}
                onChange={(e) => setDeliveryDate(e.target.value)}
                className="phm-input"
                required
              />
            </div>

            <div>
              <label className="phm-label">Catatan Spesifikasi / Instruksi Khusus</label>
              <textarea
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                placeholder="Contoh: Toleransi dimensi khusus ±0.01mm, sertifikasi uji kekerasan terlampir."
                rows={3}
                className="phm-input text-xs"
              />
            </div>

            <div className="pt-3">
              <button
                type="submit"
                disabled={isSuccess}
                className="w-full h-11 bg-[#0077C8] hover:bg-[#0062A3] text-white font-semibold text-xs rounded-xl flex items-center justify-center gap-2 transition-colors cursor-pointer"
              >
                <PlusCircle className="w-4 h-4" />
                <span>Simpan Pesanan & Rilis ke PPIC</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </AppLayout>
  );
}
