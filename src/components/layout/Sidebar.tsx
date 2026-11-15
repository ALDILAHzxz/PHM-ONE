'use client';

import React from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useAuth } from '../../context/AuthContext';
import { useOperations } from '../../context/OperationsContext';
import {
  LayoutDashboard,
  ClipboardList,
  PlusCircle,
  History,
  CheckSquare,
  AlertTriangle,
  Package,
  CalendarCheck,
  Truck,
  Users,
  FileText,
  Activity,
  Layers,
} from 'lucide-react';

interface SidebarProps {
  isOpenMobile?: boolean;
  onCloseMobile?: () => void;
}

export const Sidebar: React.FC<SidebarProps> = ({ isOpenMobile, onCloseMobile }) => {
  const pathname = usePathname();
  const { user, activeRole } = useAuth();
  const { pendingReviewsCount } = useOperations();

  const navSections = [
    {
      title: 'Utama',
      roles: ['management', 'sysadmin', 'admin_order', 'ppic', 'supervisor', 'operator', 'warehouse', 'driver'],
      items: [
        {
          label: 'Dashboard KPI',
          href: '/dashboard',
          icon: LayoutDashboard,
          roles: ['management', 'sysadmin', 'admin_order', 'ppic', 'supervisor'],
        },
      ],
    },
    {
      title: 'Lini Operator',
      roles: ['operator', 'supervisor', 'sysadmin'],
      items: [
        { label: 'Tugas Produksi', href: '/operator/tugas', icon: ClipboardList, roles: ['operator', 'sysadmin', 'supervisor'] },
        { label: 'Catat Hasil', href: '/operator/catat-hasil', icon: PlusCircle, roles: ['operator', 'sysadmin'] },
        { label: 'Riwayat Laporan', href: '/operator/riwayat', icon: History, roles: ['operator', 'sysadmin'] },
      ],
    },
    {
      title: 'Supervisor Produksi',
      roles: ['supervisor', 'sysadmin', 'management'],
      items: [
        {
          label: 'Verifikasi Hasil',
          href: '/supervisor/pemeriksaan',
          icon: CheckSquare,
          roles: ['supervisor', 'sysadmin', 'management'],
          badge: pendingReviewsCount > 0 ? pendingReviewsCount : undefined,
        },
        { label: 'Catatan Kendala', href: '/supervisor/kendala', icon: AlertTriangle, roles: ['supervisor', 'sysadmin'] },
        { label: 'Riwayat Verifikasi', href: '/supervisor/riwayat', icon: History, roles: ['supervisor', 'sysadmin'] },
      ],
    },
    {
      title: 'Pesanan Mitra',
      roles: ['admin_order', 'management', 'sysadmin', 'ppic'],
      items: [
        { label: 'Semua Pesanan', href: '/pesanan', icon: Package, roles: ['admin_order', 'management', 'sysadmin', 'ppic'] },
        { label: 'Input Pesanan Baru', href: '/pesanan/tambah', icon: PlusCircle, roles: ['admin_order', 'sysadmin'] },
      ],
    },
    {
      title: 'Perencanaan (PPIC)',
      roles: ['ppic', 'management', 'sysadmin'],
      items: [
        { label: 'Ringkasan Rencana', href: '/perencanaan', icon: CalendarCheck, roles: ['ppic', 'management', 'sysadmin'] },
        { label: 'Work Orders (WO)', href: '/perencanaan/work-order', icon: Layers, roles: ['ppic', 'sysadmin'] },
        { label: 'Penugasan Operator', href: '/perencanaan/penugasan', icon: Users, roles: ['ppic', 'sysadmin'] },
      ],
    },
    {
      title: 'Gudang & Logistik',
      roles: ['warehouse', 'management', 'sysadmin'],
      items: [
        { label: 'Daftar Pengiriman', href: '/pengiriman', icon: Truck, roles: ['warehouse', 'management', 'sysadmin'] },
        { label: 'Buat Surat Jalan', href: '/pengiriman/catat', icon: PlusCircle, roles: ['warehouse', 'sysadmin'] },
      ],
    },
    {
      title: 'Driver Pengiriman',
      roles: ['driver', 'warehouse', 'sysadmin'],
      items: [
        { label: 'Antrean Antaran', href: '/driver/pengiriman', icon: Truck, roles: ['driver', 'warehouse', 'sysadmin'] },
        { label: 'Riwayat Selesai', href: '/driver/riwayat', icon: History, roles: ['driver', 'warehouse', 'sysadmin'] },
      ],
    },
    {
      title: 'Sistem & Konfigurasi',
      roles: ['sysadmin', 'management'],
      items: [
        { label: 'Data Pengguna', href: '/sistem/pengguna', icon: Users, roles: ['sysadmin'] },
        { label: 'Audit Trail', href: '/sistem/audit', icon: FileText, roles: ['sysadmin', 'management'] },
        { label: 'Status Sistem', href: '/sistem/status', icon: Activity, roles: ['sysadmin', 'management'] },
      ],
    },
  ];

  const currentRole = activeRole || 'operator';

  return (
    <>
      {/* Mobile Backdrop */}
      {isOpenMobile && (
        <div
          onClick={onCloseMobile}
          className="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-xs md:hidden"
        />
      )}

      {/* Sidebar Panel */}
      <aside
        className={`fixed top-16 bottom-0 left-0 z-40 w-64 bg-white dark:bg-[#1E293B] border-r border-slate-200 dark:border-slate-800 overflow-y-auto transition-transform duration-200 ease-in-out md:translate-x-0 ${
          isOpenMobile ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        <div className="py-4 px-3 space-y-6">
          {navSections.map((sec, idx) => {
            // Check if section is relevant to active role or sysadmin
            const isSectionVisible =
              currentRole === 'sysadmin' ||
              sec.roles.includes(currentRole);

            if (!isSectionVisible) return null;

            // Filter items in section
            const visibleItems = sec.items.filter(
              (item) => currentRole === 'sysadmin' || item.roles.includes(currentRole)
            );

            if (visibleItems.length === 0) return null;

            return (
              <div key={idx}>
                <div className="px-3 mb-2 text-[0.6875rem] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                  {sec.title}
                </div>
                <div className="space-y-0.5">
                  {visibleItems.map((item) => {
                    const isActive = pathname === item.href;
                    const Icon = item.icon;

                    return (
                      <Link
                        key={item.href}
                        href={item.href}
                        onClick={onCloseMobile}
                        className={`flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-all ${
                          isActive
                            ? 'bg-[#0077C8] text-white shadow-xs'
                            : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                        }`}
                      >
                        <div className="flex items-center gap-2.5 truncate">
                          <Icon className={`w-4 h-4 shrink-0 ${isActive ? 'text-white' : 'text-slate-400 dark:text-slate-400'}`} />
                          <span className="truncate">{item.label}</span>
                        </div>
                        {item.badge !== undefined && (
                          <span
                            className={`px-1.5 py-0.5 rounded-full text-[0.6875rem] font-bold ${
                              isActive
                                ? 'bg-white text-[#0077C8]'
                                : 'bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300'
                            }`}
                          >
                            {item.badge}
                          </span>
                        )}
                      </Link>
                    );
                  })}
                </div>
              </div>
            );
          })}
        </div>
      </aside>
    </>
  );
};
