'use client';

import React, { useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { useOperations } from '../../context/OperationsContext';
import { BeeshaMessage } from '../../types';
import {
  Bot,
  X,
  Send,
  Sparkles,
  ClipboardList,
  CheckCircle,
  Truck,
  Package,
} from 'lucide-react';

interface BeeshaAssistantProps {
  isOpen: boolean;
  onClose: () => void;
}

export const BeeshaAssistant: React.FC<BeeshaAssistantProps> = ({ isOpen, onClose }) => {
  const { user, activeRole } = useAuth();
  const { assignments, reports, orders, shipments } = useOperations();

  const [input, setInput] = useState('');
  const [messages, setMessages] = useState<BeeshaMessage[]>([
    {
      id: 'welcome',
      sender: 'assistant',
      content: `Halo ${user?.name || 'Rekan PHM'}! Saya **Beesha**, asisten operasional cerdas PT Putra Hatim Mandiri. Ada yang bisa saya bantu terkait tugas, verifikasi, atau data pengiriman hari ini?`,
      timestamp: 'Baru saja',
    },
  ]);

  if (!isOpen) return null;

  const handleSend = (textToSend?: string) => {
    const query = (textToSend || input).trim();
    if (!query) return;

    const userMsg: BeeshaMessage = {
      id: `usr-${Date.now()}`,
      sender: 'user',
      content: query,
      timestamp: 'Baru saja',
    };

    setMessages((prev) => [...prev, userMsg]);
    if (!textToSend) setInput('');

    // Generate intelligent response based on Indonesian keywords & active role
    setTimeout(() => {
      let botResponse = '';
      const lower = query.toLowerCase();

      if (lower.includes('tugas') || lower.includes('pekerjaan') || lower.includes('tugas saya')) {
        const myTasks = assignments.filter((a) => a.operator_id === user?.id || activeRole !== 'operator');
        if (myTasks.length > 0) {
          botResponse = `Ditemukan **${myTasks.length} tugas produksi aktif**:\n\n` +
            myTasks.map((t) => `• **${t.product_name}** (${t.work_order_number})\n  Target: ${t.target_quantity} pcs | Stasiun: ${t.workstation_name}`).join('\n\n');
        } else {
          botResponse = 'Saat ini tidak ada tugas produksi aktif yang ditugaskan ke Anda.';
        }
      } else if (lower.includes('produksi') || lower.includes('ringkasan') || lower.includes('hasil')) {
        const totalRep = reports.reduce((s, r) => s + r.quantity, 0);
        const verified = reports.filter((r) => r.status === 'disetujui').reduce((s, r) => s + r.quantity, 0);
        const pending = reports.filter((r) => r.status === 'menunggu_pemeriksaan').reduce((s, r) => s + r.quantity, 0);

        botResponse = `📊 **Ringkasan Produksi Terkini**:\n\n` +
          `• Total Dilaporkan: **${totalRep} pcs**\n` +
          `• Terverifikasi QC: **${verified} pcs**\n` +
          `• Menunggu Pemeriksaan: **${pending} pcs**\n\n*Catatan: Sistem PHM memisahkan angka yang dilaporkan dan yang terverifikasi.*`;
      } else if (lower.includes('periksa') || lower.includes('antrian') || lower.includes('verifikasi')) {
        const pendingList = reports.filter((r) => r.status === 'menunggu_pemeriksaan');
        if (pendingList.length > 0) {
          botResponse = `Terdapat **${pendingList.length} laporan** yang menunggu verifikasi supervisor:\n\n` +
            pendingList.map((r) => `• #${r.id} **${r.product_name}** (+${r.quantity} pcs) oleh ${r.reported_by_name}`).join('\n');
        } else {
          botResponse = '✅ Antrean pemeriksaan bersih. Tidak ada laporan yang menunggu verifikasi saat ini.';
        }
      } else if (lower.includes('pesanan') || lower.includes('order')) {
        botResponse = `📦 **Status Pesanan Mitra Teratas**:\n\n` +
          orders.slice(0, 3).map((o) => `• **${o.order_number}** (${o.partner_name})\n  ${o.product_name}: ${o.quantity} pcs [Status: ${o.status}]`).join('\n\n');
      } else if (lower.includes('kirim') || lower.includes('pengiriman') || lower.includes('jalan')) {
        botResponse = `🚚 **Jadwal & Status Pengiriman**:\n\n` +
          shipments.map((s) => `• **${s.shipment_number}** ke ${s.customer_name}\n  Muatan: ${s.quantity} pcs | Supir: ${s.driver_name} (${s.status})`).join('\n\n');
      } else {
        botResponse = `Saya dapat membantu Anda mencari informasi:
• **"Cek tugas saya"** — Melihat pekerjaan yang ditugaskan
• **"Ringkasan produksi"** — Melihat statistik hasil dan QC
• **"Antrian pemeriksaan"** — Cek laporan pending
• **"Status pesanan"** — Cek pesanan mitra pabrik
• **"Data pengiriman"** — Status ekspedisi & surat jalan`;
      }

      const botMsg: BeeshaMessage = {
        id: `bot-${Date.now()}`,
        sender: 'assistant',
        content: botResponse,
        timestamp: 'Baru saja',
      };
      setMessages((prev) => [...prev, botMsg]);
    }, 400);
  };

  return (
    <div className="fixed inset-0 z-drawer flex justify-end overflow-hidden">
      {/* Backdrop */}
      <div
        onClick={onClose}
        className="fixed inset-0 bg-slate-900/50"
      />

      {/* Drawer */}
      <div className="relative z-10 flex h-full w-full max-w-[28rem] flex-col border-l border-garis bg-kartu shadow-modal">
        {/* Header — satu warna merek, tanpa gradien dan tanpa lencana versi. */}
        <div className="flex items-center justify-between gap-3 border-b border-garis bg-kartu px-4 py-3">
          <div className="flex min-w-0 items-center gap-2.5">
            <span className="grid h-10 w-10 shrink-0 place-items-center rounded-kontrol bg-brand text-brand-teks">
              <Bot className="h-5 w-5" aria-hidden="true" />
            </span>
            <span className="min-w-0">
              <span className="block truncate font-semibold text-teks">Beesha</span>
              <span className="block truncate text-label text-redup">
                Asisten demo — menjawab dari data PHM One
              </span>
            </span>
          </div>
          <button onClick={onClose} className="phm-icon-btn" aria-label="Tutup Beesha">
            <X className="h-5 w-5" aria-hidden="true" />
          </button>
        </div>

        {/* Quick Suggestion Pills */}
        <div className="phm-scroll flex gap-2 overflow-x-auto border-b border-garis bg-kartu-dalam p-3">
          <button
            onClick={() => handleSend('Cek tugas saya')}
            className="flex shrink-0 items-center gap-1.5 rounded-pil border border-garis-kuat bg-kartu px-3 py-1.5 text-label text-teks transition-colors hover:border-brand hover:text-brand-aksen"
          >
            <ClipboardList className="h-4 w-4 shrink-0" aria-hidden="true" />
            <span>Tugas Saya</span>
          </button>
          <button
            onClick={() => handleSend('Ringkasan produksi')}
            className="flex shrink-0 items-center gap-1.5 rounded-pil border border-garis-kuat bg-kartu px-3 py-1.5 text-label text-teks transition-colors hover:border-brand hover:text-brand-aksen"
          >
            <Sparkles className="h-4 w-4 shrink-0" aria-hidden="true" />
            <span>Produksi</span>
          </button>
          <button
            onClick={() => handleSend('Antrian pemeriksaan')}
            className="flex shrink-0 items-center gap-1.5 rounded-pil border border-garis-kuat bg-kartu px-3 py-1.5 text-label text-teks transition-colors hover:border-brand hover:text-brand-aksen"
          >
            <CheckCircle className="h-4 w-4 shrink-0" aria-hidden="true" />
            <span>Verifikasi</span>
          </button>
          <button
            onClick={() => handleSend('Data pengiriman')}
            className="flex shrink-0 items-center gap-1.5 rounded-pil border border-garis-kuat bg-kartu px-3 py-1.5 text-label text-teks transition-colors hover:border-brand hover:text-brand-aksen"
          >
            <Truck className="h-4 w-4 shrink-0" aria-hidden="true" />
            <span>Pengiriman</span>
          </button>
        </div>

        {/* Chat History */}
        <div className="phm-scroll flex-1 space-y-3 overflow-y-auto p-4 text-isi leading-relaxed">
          {messages.map((msg) => (
            <div
              key={msg.id}
              className={`flex ${msg.sender === 'user' ? 'justify-end' : 'justify-start'}`}
            >
              <div
                className={`max-w-[85%] rounded-kartu p-3 whitespace-pre-wrap ${
                  msg.sender === 'user'
                    ? 'bg-brand text-brand-teks rounded-br-sm'
                    : 'bg-kartu-dalam text-teks rounded-bl-sm border border-garis'
                }`}
              >
                {msg.content}
              </div>
            </div>
          ))}
        </div>

        {/* Input Form */}
        <form
          onSubmit={(e) => {
            e.preventDefault();
            handleSend();
          }}
          className="p-3 bg-kartu border-t border-garis flex items-center gap-2"
        >
          <input
            type="text"
            value={input}
            onChange={(e) => setInput(e.target.value)}
            placeholder="Tanyakan status pekerjaan atau data PHM…"
            className="phm-input flex-1"
          />
          <button
            type="submit"
            className="grid h-11 w-11 shrink-0 place-items-center rounded-kontrol bg-brand text-brand-teks transition-colors hover:bg-brand-pekat"
            aria-label="Kirim pesan"
          >
            <Send className="h-5 w-5" aria-hidden="true" />
          </button>
        </form>
      </div>
    </div>
  );
};
