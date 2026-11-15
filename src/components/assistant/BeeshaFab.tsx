'use client';

import React from 'react';
import { Bot } from 'lucide-react';

interface BeeshaFabProps {
  onClick: () => void;
}

/**
 * Tombol mengapung Beesha — kanan bawah, hadir di semua halaman terautentikasi.
 *
 * Dipasang di luar <main> yang memiliki pb-24, sehingga tombol ini tidak pernah
 * menutupi tombol simpan atau pesan validasi di bagian bawah halaman.
 * z-fab (40) berada di bawah sidebar (56) dan shell (60), jadi tombol ini juga
 * tidak pernah menutupi menu yang sedang terbuka.
 */
export const BeeshaFab: React.FC<BeeshaFabProps> = ({ onClick }) => {
  return (
    <button
      onClick={onClick}
      className="group fixed bottom-5 right-5 z-fab flex h-14 items-center gap-2.5 rounded-pil bg-brand pl-4 pr-5 text-brand-teks shadow-pop transition-colors duration-200 hover:bg-brand-pekat max-sm:w-14 max-sm:justify-center max-sm:px-0 sm:bottom-6 sm:right-6"
      style={{ paddingBottom: 'env(safe-area-inset-bottom, 0)' }}
      aria-label="Buka Beesha AI — tanya tentang pekerjaan Anda"
      title="Tanya Beesha"
    >
      <Bot className="h-6 w-6 shrink-0" aria-hidden="true" />
      <span className="text-isi font-semibold max-sm:hidden">Tanya Beesha</span>
    </button>
  );
};
