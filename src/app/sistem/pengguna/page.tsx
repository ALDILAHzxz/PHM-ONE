'use client';

import React from 'react';
import { AppLayout } from '../../../components/layout/AppLayout';
import { PageHeader } from '../../../components/ui/PageHeader';
import { DataTable } from '../../../components/ui/DataTable';
import { INITIAL_USERS } from '../../../data/mockData';
import { Users, Shield } from 'lucide-react';

export default function SystemUsersPage() {
  return (
    <AppLayout>
      <PageHeader
        title="Manajemen Pengguna & Akun Karyawan"
        description="Daftar akun karyawan terdaftar pada sistem operasional PT Putra Hatim Mandiri."
      />

      <DataTable
        columns={[
          {
            key: 'name',
            header: 'Karyawan',
            width: '26%',
            primary: true,
            hideLabelOnMobile: true,
            cell: (usr) => (
              <div className="flex items-center gap-2.5">
                <span className="grid h-8 w-8 shrink-0 place-items-center rounded-kontrol bg-navy text-mini font-bold text-white">
                  {usr.avatar_initials}
                </span>
                <span className="min-w-0">
                  <span className="block truncate font-semibold text-teks">{usr.name}</span>
                  <span className="phm-num mt-0.5 block truncate text-label text-brand-aksen">
                    {usr.employee_number}
                  </span>
                </span>
              </div>
            ),
          },
          {
            key: 'position',
            header: 'Jabatan',
            width: '24%',
            cell: (usr) => (
              <div>
                <span className="block text-teks">{usr.position}</span>
                <span className="mt-0.5 block text-label text-redup">{usr.department}</span>
              </div>
            ),
          },
          {
            key: 'roles',
            header: 'Peran Akses',
            cell: (usr) => (
              <div className="flex flex-wrap gap-1.5">
                {usr.roles.map((r) => (
                  <span key={r} className="phm-badge phm-badge-netral">
                    {r}
                  </span>
                ))}
              </div>
            ),
          },
          {
            key: 'status',
            header: 'Status',
            width: '130px',
            align: 'right',
            hideLabelOnMobile: true,
            cell: () => <span className="phm-badge phm-badge-aman">Aktif</span>,
          },
        ]}
        rows={INITIAL_USERS}
        getKey={(usr) => usr.id}
        emptyIcon={Users}
        emptyTitle="Belum ada data pengguna"
      />
    </AppLayout>
  );
}
