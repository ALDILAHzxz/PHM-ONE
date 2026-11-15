'use client';

import React, { useState, useRef, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { RoleType } from '../../types';
import { LogOut, ChevronDown, Check, Moon, Sun, Menu } from 'lucide-react';

interface NavbarProps {
  onToggleSidebar?: () => void;
}

const ROLE_LABELS: Record<RoleType, string> = {
  operator: 'Operator Produksi',
  supervisor: 'Supervisor Produksi',
  admin_order: 'Admin Pesanan',
  ppic: 'PPIC (Perencanaan)',
  warehouse: 'Petugas Gudang',
  driver: 'Driver Pengiriman',
  management: 'Pimpinan / Manajemen',
  sysadmin: 'Administrator Sistem',
};

/**
 * Navbar sengaja dibuat tenang: hanya tiga kontrol.
 *
 * Tombol "Tanya Beesha AI" berwarna gradien biru-indigo dipindahkan dari sini
 * menjadi tombol mengapung di kanan bawah. Dua alasannya:
 * 1. Gradien itu memasukkan warna merek kedua (indigo) — referensi desain PHM
 *    hanya mengenal satu warna merek.
 * 2. Animasi titik berdenyut di navbar menarik perhatian terus-menerus,
 *    padahal navbar seharusnya latar belakang, bukan pusat perhatian.
 */
export const Navbar: React.FC<NavbarProps> = ({ onToggleSidebar }) => {
  const { user, activeRole, logout, switchRole } = useAuth();
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [isDark, setIsDark] = useState(false);
  const menuRef = useRef<HTMLDivElement>(null);

  // Terapkan tema tersimpan sebelum interaksi pertama.
  useEffect(() => {
    try {
      const stored = localStorage.getItem('phm-tema');
      const wantDark = stored === 'gelap';
      setIsDark(wantDark);
      document.documentElement.classList.toggle('dark', wantDark);
    } catch {
      /* penyimpanan diblokir — tetap di tema terang */
    }
  }, []);

  // Tutup menu profil saat klik di luar atau tekan Escape.
  useEffect(() => {
    if (!isMenuOpen) return;

    const onClick = (e: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
        setIsMenuOpen(false);
      }
    };
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') setIsMenuOpen(false);
    };

    document.addEventListener('mousedown', onClick);
    document.addEventListener('keydown', onKey);
    return () => {
      document.removeEventListener('mousedown', onClick);
      document.removeEventListener('keydown', onKey);
    };
  }, [isMenuOpen]);

  const toggleTheme = () => {
    const next = !isDark;
    setIsDark(next);
    document.documentElement.classList.toggle('dark', next);
    try {
      localStorage.setItem('phm-tema', next ? 'gelap' : 'terang');
    } catch {
      /* abaikan */
    }
  };

  return (
    <header className="flex h-navbar items-center justify-between gap-3 border-b border-garis bg-kartu px-3 sm:px-5">
      {/* ----------------------------- KIRI ----------------------------- */}
      <div className="flex min-w-0 items-center gap-2">
        <button
          onClick={onToggleSidebar}
          className="phm-icon-btn md:hidden"
          aria-label="Buka menu navigasi"
        >
          <Menu className="h-5 w-5" aria-hidden="true" />
        </button>

        {/* Logo: lebar tetap + shrink-0 supaya tidak pernah terpotong lagi. */}
        <div className="flex min-w-0 items-center gap-2.5">
          <span
            className="grid h-9 w-9 shrink-0 place-items-center rounded-kontrol bg-brand text-isi font-bold text-brand-teks"
            aria-hidden="true"
          >
            P1
          </span>
          <span className="min-w-0">
            <span className="block truncate text-[1.0625rem] font-bold leading-tight tracking-tight text-teks">
              PHM <span className="text-brand-aksen">One</span>
            </span>
            <span className="hidden truncate text-mini leading-tight text-redup sm:block">
              PT Putra Hatim Mandiri
            </span>
          </span>
        </div>
      </div>

      {/* ----------------------------- KANAN ---------------------------- */}
      <div className="flex shrink-0 items-center gap-1">
        <button
          onClick={toggleTheme}
          className="phm-icon-btn"
          aria-label={isDark ? 'Ubah ke mode terang' : 'Ubah ke mode gelap'}
          title={isDark ? 'Mode terang' : 'Mode gelap'}
        >
          {isDark ? (
            <Sun className="h-5 w-5" aria-hidden="true" />
          ) : (
            <Moon className="h-5 w-5" aria-hidden="true" />
          )}
        </button>

        {user && (
          <div className="relative" ref={menuRef}>
            <button
              onClick={() => setIsMenuOpen((v) => !v)}
              className="flex items-center gap-2 rounded-kontrol border border-garis bg-kartu py-1 pl-2 pr-1.5 transition-colors hover:border-garis-kuat sm:pl-3"
              aria-expanded={isMenuOpen}
              aria-haspopup="menu"
            >
              <span className="hidden text-left sm:block">
                <span className="block max-w-[140px] truncate text-label font-semibold leading-tight text-teks">
                  {user.name}
                </span>
                <span className="block max-w-[140px] truncate text-mini leading-tight text-redup">
                  {activeRole ? ROLE_LABELS[activeRole] : ''}
                </span>
              </span>
              <span className="grid h-8 w-8 shrink-0 place-items-center rounded-kontrol bg-navy text-mini font-bold text-white">
                {user.avatar_initials}
              </span>
              <ChevronDown className="h-4 w-4 shrink-0 text-redup" aria-hidden="true" />
            </button>

            {isMenuOpen && (
              <div
                role="menu"
                className="absolute right-0 mt-2 w-72 overflow-hidden rounded-kartu border border-garis bg-kartu shadow-pop"
              >
                <div className="border-b border-garis px-4 py-3">
                  <p className="text-isi font-semibold text-teks">{user.name}</p>
                  <p className="mt-0.5 text-label text-redup">{user.employee_number}</p>
                  <p className="text-label text-redup">{user.position}</p>
                </div>

                {user.roles.length > 1 && (
                  <div className="border-b border-garis py-2">
                    <p className="phm-eyebrow px-4 pb-1.5">Ganti ruang kerja</p>
                    {user.roles.map((r) => (
                      <button
                        key={r}
                        role="menuitem"
                        onClick={() => {
                          switchRole(r);
                          setIsMenuOpen(false);
                        }}
                        className={`flex w-full items-center justify-between gap-2 px-4 py-2.5 text-left text-isi transition-colors ${
                          activeRole === r
                            ? 'bg-brand-muda font-semibold text-brand-aksen'
                            : 'text-teks hover:bg-brand-redup'
                        }`}
                      >
                        <span>{ROLE_LABELS[r]}</span>
                        {activeRole === r && (
                          <Check className="h-4 w-4 shrink-0" aria-hidden="true" />
                        )}
                      </button>
                    ))}
                  </div>
                )}

                <button
                  role="menuitem"
                  onClick={() => {
                    setIsMenuOpen(false);
                    logout();
                  }}
                  className="flex w-full items-center gap-2.5 px-4 py-3 text-left text-isi font-semibold text-kritis transition-colors hover:bg-kritis-latar"
                >
                  <LogOut className="h-4 w-4 shrink-0" aria-hidden="true" />
                  <span>Keluar</span>
                </button>
              </div>
            )}
          </div>
        )}
      </div>
    </header>
  );
};
