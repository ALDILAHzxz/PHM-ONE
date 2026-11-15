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
    <div className="fixed inset-0 z-50 overflow-hidden flex justify-end">
      {/* Backdrop */}
      <div
        onClick={onClose}
        className="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"
      />

      {/* Drawer */}
      <div className="relative w-full max-w-md bg-white dark:bg-[#1E293B] shadow-2xl h-full flex flex-col z-10 border-l border-slate-200 dark:border-slate-800 animate-in slide-in-from-right duration-200">
        {/* Header */}
        <div className="p-4 bg-gradient-to-r from-[#1B365D] to-[#0077C8] text-white flex items-center justify-between shadow-xs">
          <div className="flex items-center gap-2.5">
            <div className="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center">
              <Bot className="w-5 h-5 text-white" />
            </div>
            <div>
              <div className="text-sm font-bold flex items-center gap-1.5">
                <span>Beesha AI</span>
                <span className="text-[0.625rem] px-1.5 py-0.5 rounded-full bg-white/20 text-white font-mono">
                  v1.2
                </span>
              </div>
              <div className="text-[0.6875rem] text-blue-100">Asisten Pintar Operasi PHM</div>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-1.5 rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Quick Suggestion Pills */}
        <div className="p-2.5 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700 flex gap-1.5 overflow-x-auto text-[0.6875rem] whitespace-nowrap">
          <button
            onClick={() => handleSend('Cek tugas saya')}
            className="px-2.5 py-1 rounded-full bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 hover:border-blue-400 text-slate-700 dark:text-slate-200 transition-colors flex items-center gap-1 cursor-pointer"
          >
            <ClipboardList className="w-3 h-3 text-[#0077C8]" />
            <span>Tugas Saya</span>
          </button>
          <button
            onClick={() => handleSend('Ringkasan produksi')}
            className="px-2.5 py-1 rounded-full bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 hover:border-blue-400 text-slate-700 dark:text-slate-200 transition-colors flex items-center gap-1 cursor-pointer"
          >
            <Sparkles className="w-3 h-3 text-amber-500" />
            <span>Produksi</span>
          </button>
          <button
            onClick={() => handleSend('Antrian pemeriksaan')}
            className="px-2.5 py-1 rounded-full bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 hover:border-blue-400 text-slate-700 dark:text-slate-200 transition-colors flex items-center gap-1 cursor-pointer"
          >
            <CheckCircle className="w-3 h-3 text-emerald-500" />
            <span>Verifikasi</span>
          </button>
          <button
            onClick={() => handleSend('Data pengiriman')}
            className="px-2.5 py-1 rounded-full bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 hover:border-blue-400 text-slate-700 dark:text-slate-200 transition-colors flex items-center gap-1 cursor-pointer"
          >
            <Truck className="w-3 h-3 text-sky-500" />
            <span>Pengiriman</span>
          </button>
        </div>

        {/* Chat History */}
        <div className="flex-1 overflow-y-auto p-4 space-y-3 text-xs leading-relaxed">
          {messages.map((msg) => (
            <div
              key={msg.id}
              className={`flex ${msg.sender === 'user' ? 'justify-end' : 'justify-start'}`}
            >
              <div
                className={`max-w-[85%] rounded-2xl p-3 whitespace-pre-wrap ${
                  msg.sender === 'user'
                    ? 'bg-[#0077C8] text-white rounded-br-xs'
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded-bl-xs border border-slate-200 dark:border-slate-700'
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
          className="p-3 bg-white dark:bg-[#1E293B] border-t border-slate-200 dark:border-slate-800 flex items-center gap-2"
        >
          <input
            type="text"
            value={input}
            onChange={(e) => setInput(e.target.value)}
            placeholder="Tanyakan status pekerjaan atau data PHM…"
            className="flex-1 px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white outline-none focus:border-[#0077C8]"
          />
          <button
            type="submit"
            className="p-2 rounded-xl bg-[#0077C8] hover:bg-[#0062A3] text-white transition-colors cursor-pointer"
            aria-label="Kirim pesan"
          >
            <Send className="w-4 h-4" />
          </button>
        </form>
      </div>
    </div>
  );
};
