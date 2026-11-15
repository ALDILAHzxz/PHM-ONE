'use client';

import React from 'react';
import { LucideIcon } from 'lucide-react';

interface StatCardProps {
  label: string;
  value: string | number;
  subtext?: string;
  icon: LucideIcon;
  /**
   * Nada kartu. Hanya menerima MAKNA, bukan warna.
   *
   * Sebelumnya prop ini bernama `variant` dan menerima 'blue' | 'emerald' |
   * 'amber' | 'indigo' | 'rose' — lima warna dekoratif yang dipakai bergantian
   * supaya dashboard terlihat berwarna. Akibatnya warna kehilangan arti:
   * kartu indigo dan kartu biru tidak menyatakan apa pun yang berbeda.
   *
   * Sekarang: `brand` untuk angka netral, dan tiga sisanya hanya dipakai bila
   * angkanya memang berstatus baik/perlu perhatian/kritis.
   */
  tone?: 'brand' | 'aman' | 'waspada' | 'kritis';
}

export const StatCard: React.FC<StatCardProps> = ({
  label,
  value,
  subtext,
  icon: Icon,
  tone = 'brand',
}) => {
  const toneClasses: Record<string, { box: string; value: string }> = {
    brand: { box: 'bg-brand-muda text-brand-aksen', value: 'text-teks' },
    aman: { box: 'bg-aman-latar text-aman-teks', value: 'text-aman-teks' },
    waspada: { box: 'bg-waspada-latar text-waspada-teks', value: 'text-waspada-teks' },
    kritis: { box: 'bg-kritis-latar text-kritis-teks', value: 'text-kritis-teks' },
  };

  const t = toneClasses[tone];

  return (
    <div className="phm-card flex items-start justify-between gap-3">
      <div className="min-w-0">
        <p className="phm-eyebrow">{label}</p>
        <p className={`mt-1.5 text-kpi font-bold tabular-nums tracking-tight ${t.value}`}>
          {value}
        </p>
        {subtext && <p className="phm-helper mt-1 text-label">{subtext}</p>}
      </div>
      <span className={`grid h-11 w-11 shrink-0 place-items-center rounded-kontrol ${t.box}`}>
        <Icon className="h-5 w-5" aria-hidden="true" />
      </span>
    </div>
  );
};
