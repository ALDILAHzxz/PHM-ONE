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
  X,
} from 'lucide-react';

interface SidebarProps {
  isOpenMobile?: boolean;
  onCloseMobile?: () => void;
}

export const Sidebar: React.FC<SidebarProps> = ({ isOpenMobile, onCloseMobile }) => {
  const pathname = usePathname();
  const { activeRole } = useAuth();
  const { pendingReviewsCount } = useOperations();

  const navSections = [
    {
      title: 'Utama',
      roles: ['management', 'sysadmin', 'admin_order', 'ppic', 'supervisor'],
      items: [
        {
          label: 'Dashboard',
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
        {
          label: 'Tugas Produksi',
          href: '/operator/tugas',
          icon: ClipboardList,
          roles: ['operator', 'sysadmin', 'supervisor'],
        },
        { label: 'Catat Hasil', href: '/operator/catat-hasil', icon: PlusCircle, roles: ['operator', 'sysadmin'] },
        { label: 'Riwayat Laporan', href: '/operator/riwayat', icon: History, roles: ['operator', 'sysadmin'] },
      ],
    },
    {
      title: 'Supervisor',
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
        { label: 'Pesanan Baru', href: '/pesanan/tambah', icon: PlusCircle, roles: ['admin_order', 'sysadmin'] },
      ],
    },
    {
      title: 'Perencanaan',
      roles: ['ppic', 'management', 'sysadmin'],
      items: [
        { label: 'Ringkasan Rencana', href: '/perencanaan', icon: CalendarCheck, roles: ['ppic', 'management', 'sysadmin'] },
        { label: 'Work Order', href: '/perencanaan/work-order', icon: Layers, roles: ['ppic', 'sysadmin'] },
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
      title: 'Driver',
      roles: ['driver', 'warehouse', 'sysadmin'],
      items: [
        { label: 'Antrean Antaran', href: '/driver/pengiriman', icon: Truck, roles: ['driver', 'warehouse', 'sysadmin'] },
        { label: 'Riwayat Selesai', href: '/driver/riwayat', icon: History, roles: ['driver', 'warehouse', 'sysadmin'] },
      ],
    },
    {
      title: 'Sistem',
      roles: ['sysadmin', 'management'],
      items: [
        { label: 'Data Pengguna', href: '/sistem/pengguna', icon: Users, roles: ['sysadmin'] },
        { label: 'Audit Trail', href: '/sistem/audit', icon: FileText, roles: ['sysadmin', 'management'] },
        { label: 'Status Sistem', href: '/sistem/status', icon: Activity, roles: ['sysadmin', 'management'] },
      ],
    },
  ];

  const currentRole = activeRole || 'operator';

  const visibleSections = navSections
    .map((sec) => ({
      ...sec,
      items: sec.items.filter(
        (item) => currentRole === 'sysadmin' || item.roles.includes(currentRole)
      ),
    }))
    .filter(
      (sec) =>
        (currentRole === 'sysadmin' || sec.roles.includes(currentRole)) && sec.items.length > 0
    );

  return (
    <>
      {/* Backdrop hanya di mobile */}
      {isOpenMobile && (
        <div
          onClick={onCloseMobile}
          className="fixed inset-0 z-[55] bg-slate-900/50 md:hidden"
          aria-hidden="true"
        />
      )}

      {/* Sidebar dimulai tepat di bawah shell (pita + navbar). Angkanya satu,
          dibaca dari --h-shell, jadi tidak bisa lagi meleset dari navbar. */}
      <aside
        className={`phm-scroll fixed bottom-0 left-0 z-[56] w-sidebar overflow-y-auto border-r border-garis bg-kartu transition-transform duration-200 ease-panel md:translate-x-0 ${
          isOpenMobile ? 'translate-x-0' : '-translate-x-full'
        }`}
        style={{ top: 'var(--h-shell)' }}
        aria-label="Navigasi utama"
      >
        {/* Tombol tutup hanya tampil di mobile */}
        <div className="flex items-center justify-between border-b border-garis px-4 py-2 md:hidden">
          <span className="phm-eyebrow">Menu</span>
          <button onClick={onCloseMobile} className="phm-icon-btn" aria-label="Tutup menu navigasi">
            <X className="h-5 w-5" aria-hidden="true" />
          </button>
        </div>

        <nav className="space-y-5 px-3 py-4">
          {visibleSections.map((sec) => (
            <div key={sec.title}>
              <p className="phm-eyebrow px-3 pb-1.5">{sec.title}</p>
              <div className="space-y-0.5">
                {sec.items.map((item) => {
                  const isActive = pathname === item.href;
                  const Icon = item.icon;

                  return (
                    <Link
                      key={item.href}
                      href={item.href}
                      onClick={onCloseMobile}
                      aria-current={isActive ? 'page' : undefined}
                      className={`phm-nav-link ${isActive ? 'phm-nav-link-active' : ''}`}
                    >
                      <Icon
                        className={`h-[1.125rem] w-[1.125rem] shrink-0 ${
                          isActive ? 'text-brand-aksen' : 'text-redup'
                        }`}
                        aria-hidden="true"
                      />
                      <span className="min-w-0 flex-1 truncate">{item.label}</span>
                      {item.badge !== undefined && (
                        <span className="phm-badge phm-badge-waspada shrink-0 px-2 py-0 text-mini">
                          {item.badge}
                        </span>
                      )}
                    </Link>
                  );
                })}
              </div>
            </div>
          ))}
        </nav>
      </aside>
    </>
  );
};
