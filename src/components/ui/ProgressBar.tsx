'use client';

import React from 'react';

interface ProgressBarProps {
  value: number;
  max: number;
  caption?: string;
  /** Teks kiri di bawah batang. Default: "value / max". */
  label?: string;
}

/**
 * Batang progres produksi.
 *
 * Warna batang memakai hijau status (--aman), bukan warna merek: batang ini
 * menyatakan "sudah terverifikasi", yaitu sebuah keadaan, bukan identitas.
 */
export const ProgressBar: React.FC<ProgressBarProps> = ({ value, max, caption, label }) => {
  const pct = max > 0 ? Math.min(100, Math.round((value / max) * 100)) : 0;

  return (
    <div className="space-y-1.5">
      <div className="flex items-center justify-between gap-2 text-label">
        <span className="phm-num text-redup">{label ?? `${value} / ${max}`}</span>
        {caption && <span className="phm-num font-semibold text-teks">{caption}</span>}
      </div>
      <div
        className="h-2 w-full overflow-hidden rounded-pil bg-netral-latar"
        role="img"
        aria-label={`${value} dari ${max} terverifikasi (${pct} persen)`}
      >
        <div
          className="h-full rounded-pil bg-aman transition-[width] duration-300"
          style={{ width: `${pct}%` }}
        />
      </div>
    </div>
  );
};
