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
            <Server className="w-5 h-5 text-brand-aksen" />
            <h3 className="font-bold text-isi text-teks">Arsitektur Next.js</h3>
          </div>

          <div className="space-y-2 text-label">
            <div className="flex justify-between py-1.5 border-b border-garis">
              <span className="text-redup">Framework:</span>
              <span className="font-bold text-teks">Next.js 15 (App Router)</span>
            </div>
            <div className="flex justify-between py-1.5 border-b border-garis">
              <span className="text-redup">Runtime:</span>
              <span className="font-bold text-teks">Node.js / Edge Compatible</span>
            </div>
            <div className="flex justify-between py-1.5 border-b border-garis">
              <span className="text-redup">Styling Engine:</span>
              <span className="font-bold text-teks">Tailwind CSS (Custom PHM Tokens)</span>
            </div>
            <div className="flex justify-between py-1.5">
              <span className="text-redup">Deploy Target:</span>
              <span className="font-bold text-emerald-600">Vercel / Netlify / Self-hosted Node</span>
            </div>
          </div>
        </div>

        <div className="phm-card space-y-3">
          <div className="flex items-center gap-2.5 mb-2">
            <Activity className="w-5 h-5 text-emerald-600" />
            <h3 className="font-bold text-isi text-teks">Kondisi Modul Layanan</h3>
          </div>

          <div className="space-y-2 text-label">
            <div className="flex items-center justify-between py-1.5 border-b border-garis">
              <span className="text-teks">Modul Otentikasi NIK & Peran</span>
              <span className="inline-flex items-center gap-1 text-emerald-600 font-bold">
                <CheckCircle2 className="h-[1.125rem] w-[1.125rem] shrink-0" /> Normal
              </span>
            </div>
            <div className="flex items-center justify-between py-1.5 border-b border-garis">
              <span className="text-teks">Lini Verifikasi Supervisor</span>
              <span className="inline-flex items-center gap-1 text-emerald-600 font-bold">
                <CheckCircle2 className="h-[1.125rem] w-[1.125rem] shrink-0" /> Normal
              </span>
            </div>
            <div className="flex items-center justify-between py-1.5 border-b border-garis">
              <span className="text-teks">Modul Perencanaan & Work Order</span>
              <span className="inline-flex items-center gap-1 text-emerald-600 font-bold">
                <CheckCircle2 className="h-[1.125rem] w-[1.125rem] shrink-0" /> Normal
              </span>
            </div>
            <div className="flex items-center justify-between py-1.5">
              <span className="text-teks">Beesha AI Assistant (Simulated Engine)</span>
              <span className="inline-flex items-center gap-1 text-emerald-600 font-bold">
                <CheckCircle2 className="h-[1.125rem] w-[1.125rem] shrink-0" /> Siap
              </span>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
