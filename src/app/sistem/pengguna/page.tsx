'use client';

import React from 'react';
import { AppLayout } from '../../../components/layout/AppLayout';
import { PageHeader } from '../../../components/ui/PageHeader';
import { INITIAL_USERS } from '../../../data/mockData';
import { Users, Shield } from 'lucide-react';

export default function SystemUsersPage() {
  return (
    <AppLayout>
      <PageHeader
        title="Manajemen Pengguna & Akun Karyawan"
        description="Daftar akun karyawan terdaftar pada sistem operasional PT Putra Hatim Mandiri."
      />

      <div className="phm-card overflow-hidden p-0">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 font-bold uppercase tracking-wider text-[0.6875rem]">
              <tr>
                <th className="py-3 px-4">Nama Karyawan</th>
                <th className="py-3 px-4">Nomor Induk (NIK)</th>
                <th className="py-3 px-4">Jabatan</th>
                <th className="py-3 px-4">Departemen</th>
                <th className="py-3 px-4">Peran Akses</th>
                <th className="py-3 px-4 text-right">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {INITIAL_USERS.map((usr) => (
                <tr key={usr.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                  <td className="py-3.5 px-4 font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span className="w-6 h-6 rounded-md bg-[#1B365D] text-white text-[0.6875rem] font-bold flex items-center justify-center">
                      {usr.avatar_initials}
                    </span>
                    <span>{usr.name}</span>
                  </td>
                  <td className="py-3.5 px-4 font-mono font-bold text-[#0077C8]">
                    {usr.employee_number}
                  </td>
                  <td className="py-3.5 px-4 text-slate-700 dark:text-slate-300">
                    {usr.position}
                  </td>
                  <td className="py-3.5 px-4 text-slate-500">
                    {usr.department}
                  </td>
                  <td className="py-3.5 px-4">
                    <div className="flex flex-wrap gap-1">
                      {usr.roles.map((r) => (
                        <span key={r} className="px-2 py-0.5 rounded-full text-[0.6875rem] font-semibold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200">
                          {r}
                        </span>
                      ))}
                    </div>
                  </td>
                  <td className="py-3.5 px-4 text-right">
                    <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[0.6875rem] font-semibold bg-emerald-100 text-emerald-800">
                      Aktif
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </AppLayout>
  );
}
