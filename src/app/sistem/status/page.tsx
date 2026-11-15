'use client';

import React from 'react';
import { AppLayout } from '../../../components/layout/AppLayout';
import { PageHeader } from '../../../components/ui/PageHeader';
import { Activity, CheckCircle2, Server, Cpu, Database, Globe } from 'lucide-react';

export default function SystemStatusPage() {
  return (
    <AppLayout>
      <PageHeader
        title="Status Sistem & Lingkungan Operasional"
        description="Informasi infrastruktur server, framework Next.js, dan kesehatan modul aplikasi PHM One."
      />

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="phm-card space-y-3">
          <div className="flex items-center gap-2.5 mb-2">
            <Server className="w-5 h-5 text-[#0077C8]" />
            <h3 className="font-bold text-sm text-slate-900 dark:text-white">Arsitektur Next.js</h3>
          </div>

          <div className="space-y-2 text-xs">
            <div className="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
              <span className="text-slate-500">Framework:</span>
              <span className="font-bold text-slate-800 dark:text-slate-200">Next.js 15 (App Router)</span>
            </div>
            <div className="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
              <span className="text-slate-500">Runtime:</span>
              <span className="font-bold text-slate-800 dark:text-slate-200">Node.js / Edge Compatible</span>
            </div>
            <div className="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
              <span className="text-slate-500">Styling Engine:</span>
              <span className="font-bold text-slate-800 dark:text-slate-200">Tailwind CSS (Custom PHM Tokens)</span>
            </div>
            <div className="flex justify-between py-1.5">
              <span className="text-slate-500">Deploy Target:</span>
              <span className="font-bold text-emerald-600">Vercel / Netlify / Self-hosted Node</span>
            </div>
          </div>
        </div>

        <div className="phm-card space-y-3">
          <div className="flex items-center gap-2.5 mb-2">
            <Activity className="w-5 h-5 text-emerald-600" />
            <h3 className="font-bold text-sm text-slate-900 dark:text-white">Kondisi Modul Layanan</h3>
          </div>

          <div className="space-y-2 text-xs">
            <div className="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
              <span className="text-slate-600 dark:text-slate-300">Modul Otentikasi NIK & Peran</span>
              <span className="inline-flex items-center gap-1 text-emerald-600 font-bold">
                <CheckCircle2 className="w-3.5 h-3.5" /> Normal
              </span>
            </div>
            <div className="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
              <span className="text-slate-600 dark:text-slate-300">Lini Verifikasi Supervisor</span>
              <span className="inline-flex items-center gap-1 text-emerald-600 font-bold">
                <CheckCircle2 className="w-3.5 h-3.5" /> Normal
              </span>
            </div>
            <div className="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
              <span className="text-slate-600 dark:text-slate-300">Modul Perencanaan & Work Order</span>
              <span className="inline-flex items-center gap-1 text-emerald-600 font-bold">
                <CheckCircle2 className="w-3.5 h-3.5" /> Normal
              </span>
            </div>
            <div className="flex items-center justify-between py-1.5">
              <span className="text-slate-600 dark:text-slate-300">Beesha AI Assistant (Simulated Engine)</span>
              <span className="inline-flex items-center gap-1 text-emerald-600 font-bold">
                <CheckCircle2 className="w-3.5 h-3.5" /> Siap
              </span>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
