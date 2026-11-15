'use client';

import React from 'react';
import { LucideIcon } from 'lucide-react';

interface StatCardProps {
  label: string;
  value: string | number;
  subtext?: string;
  icon: LucideIcon;
  variant?: 'blue' | 'emerald' | 'amber' | 'indigo' | 'rose';
}

export const StatCard: React.FC<StatCardProps> = ({
  label,
  value,
  subtext,
  icon: Icon,
  variant = 'blue',
}) => {
  const getColors = () => {
    switch (variant) {
      case 'emerald':
        return 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-400';
      case 'amber':
        return 'text-amber-600 bg-amber-50 dark:bg-amber-950/40 dark:text-amber-400';
      case 'indigo':
        return 'text-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 dark:text-indigo-400';
      case 'rose':
        return 'text-rose-600 bg-rose-50 dark:bg-rose-950/40 dark:text-rose-400';
      default:
        return 'text-[#0077C8] bg-blue-50 dark:bg-blue-950/40 dark:text-blue-400';
    }
  };

  return (
    <div className="phm-card flex items-start justify-between">
      <div>
        <p className="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
          {label}
        </p>
        <p className="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mt-1">
          {value}
        </p>
        {subtext && (
          <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">
            {subtext}
          </p>
        )}
      </div>
      <div className={`p-3 rounded-2xl ${getColors()}`}>
        <Icon className="w-6 h-6" />
      </div>
    </div>
  );
};
