'use client';

import React, { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '../../context/AuthContext';
import { Navbar } from './Navbar';
import { Sidebar } from './Sidebar';
import { BeeshaAssistant } from '../assistant/BeeshaAssistant';
import { BeeshaFab } from '../assistant/BeeshaFab';
import { AlertCircle } from 'lucide-react';

interface AppLayoutProps {
  children: React.ReactNode;
}

/**
 * Kerangka aplikasi.
 *
 * Catatan perbaikan tata letak:
 * Sebelumnya pita demo berada di aliran normal (mendorong navbar ~28px ke bawah),
 * sementara sidebar dipasang `fixed top-16`. Akibatnya sidebar menutupi bagian
 * bawah navbar dan memotong logo "PHM ONE / Manufaktur".
 *
 * Sekarang pita + navbar menjadi SATU blok sticky, dan tingginya ditulis sekali
 * sebagai `--h-shell` di globals.css. Sidebar, konten, dan drawer semuanya
 * membaca angka yang sama, jadi ketiganya tidak mungkin lagi bergeser sendiri.
 */
export const AppLayout: React.FC<AppLayoutProps> = ({ children }) => {
  const { user, isAuthenticated } = useAuth();
  const router = useRouter();

  const [isSidebarOpenMobile, setIsSidebarOpenMobile] = useState(false);
  const [isAssistantOpen, setIsAssistantOpen] = useState(false);

  useEffect(() => {
    if (!isAuthenticated) {
      router.replace('/masuk');
    }
  }, [isAuthenticated, router]);

  if (!user) {
    return (
      <div className="min-h-screen bg-latar flex items-center justify-center text-redup">
        Memeriksa otentikasi…
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-latar text-teks">
      <a
        href="#konten-utama"
        className="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[80] focus:rounded-kontrol focus:bg-kartu focus:px-4 focus:py-2 focus:font-semibold focus:text-brand-aksen focus:shadow-pop"
      >
        Lewati ke konten utama
      </a>

      {/* ================= PITA DEMO + NAVBAR = satu blok sticky ============= */}
      <div className="sticky top-0 z-shell">
        {/* Pita mode demo — wajib tampil selama data masih fiktif. */}
        <div
          className="phm-pita"
          role="note"
        >
          <AlertCircle className="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
          <span className="truncate">
            <strong>MODE DEMO</strong>
            <span className="hidden sm:inline">
              {' '}
              — Data bersifat fiktif untuk simulasi PT Putra Hatim Mandiri.
            </span>
          </span>
        </div>

        <Navbar onToggleSidebar={() => setIsSidebarOpenMobile((v) => !v)} />
      </div>

      {/* ============================== SIDEBAR ============================= */}
      <Sidebar
        isOpenMobile={isSidebarOpenMobile}
        onCloseMobile={() => setIsSidebarOpenMobile(false)}
      />

      {/* ============================== KONTEN ============================== */}
      {/* pl-sidebar menyediakan ruang untuk sidebar tetap; wadah di dalamnya
          yang memusatkan konten, sehingga tidak ada lagi margin + mx-auto
          yang saling berebut dan membuat halaman miring ke satu sisi. */}
      <div className="md:pl-sidebar">
        <main
          id="konten-utama"
          className="mx-auto w-full max-w-[1200px] px-4 pb-24 pt-6 sm:px-6 lg:px-8"
        >
          {children}
        </main>
      </div>

      {/* ===================== BEESHA — tombol mengapung ==================== */}
      {!isAssistantOpen && <BeeshaFab onClick={() => setIsAssistantOpen(true)} />}

      <BeeshaAssistant isOpen={isAssistantOpen} onClose={() => setIsAssistantOpen(false)} />
    </div>
  );
};
