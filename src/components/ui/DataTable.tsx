'use client';

import React from 'react';
import { LucideIcon, Inbox } from 'lucide-react';

export interface DataColumn<T> {
  /** Kunci unik kolom. */
  key: string;
  /** Judul kolom di header tabel. */
  header: string;
  /** Isi sel. */
  cell: (row: T) => React.ReactNode;
  /** Lebar kolom di desktop. Tanpa ini kolom teks panjang akan terhimpit. */
  width?: string;
  /** Rata kanan untuk kolom angka. */
  align?: 'left' | 'right' | 'center';
  /** Kolom utama: menjadi judul kartu di tampilan mobile. */
  primary?: boolean;
  /** Sembunyikan label kolom di tampilan mobile (untuk kolom utama/status). */
  hideLabelOnMobile?: boolean;
  /** Jangan tampilkan kolom ini sama sekali di mobile. */
  hideOnMobile?: boolean;
}

interface DataTableProps<T> {
  columns: DataColumn<T>[];
  rows: T[];
  getKey: (row: T) => React.Key;
  emptyIcon?: LucideIcon;
  emptyTitle?: string;
  emptyMessage?: string;
}

/**
 * Tabel data yang bisa dibaca.
 *
 * Masalah yang diperbaiki: tabel sebelumnya menumpuk 7 kolom dengan teks 12px
 * tanpa kontrol lebar, sehingga nama komponen seperti "Bracket Mounting Engine
 * CB-150" pecah menjadi empat baris dan tabel terlihat berantakan.
 *
 * Dua keputusan di sini:
 * 1. Setiap kolom WAJIB bisa menyatakan lebarnya, dan tabel memakai
 *    `table-fixed` sehingga lebar itu dihormati, bukan ditebak browser.
 * 2. Di layar sempit tabel berubah menjadi kartu bertumpuk. Ini lebih jujur
 *    daripada memaksa scroll horizontal pada data yang padat — terutama untuk
 *    pengguna yang membaca di tablet lantai produksi.
 */
export function DataTable<T>({
  columns,
  rows,
  getKey,
  emptyIcon: EmptyIcon = Inbox,
  emptyTitle = 'Belum ada data',
  emptyMessage,
}: DataTableProps<T>) {
  if (rows.length === 0) {
    return (
      <div className="phm-card flex flex-col items-center gap-3 py-12 text-center">
        <span className="grid h-14 w-14 place-items-center rounded-pil bg-kartu-dalam text-redup">
          <EmptyIcon className="h-7 w-7" aria-hidden="true" />
        </span>
        <p className="phm-section">{emptyTitle}</p>
        {emptyMessage && <p className="phm-helper max-w-md">{emptyMessage}</p>}
      </div>
    );
  }

  const mobileColumns = columns.filter((c) => !c.hideOnMobile);
  const primaryColumn = columns.find((c) => c.primary);

  return (
    <>
      {/* ============================ DESKTOP ============================ */}
      <div className="phm-card-flat hidden overflow-hidden lg:block">
        <div className="overflow-x-auto">
          <table className="phm-table table-fixed">
            <colgroup>
              {columns.map((col) => (
                <col key={col.key} style={col.width ? { width: col.width } : undefined} />
              ))}
            </colgroup>
            <thead>
              <tr>
                {columns.map((col) => (
                  <th
                    key={col.key}
                    scope="col"
                    className={
                      col.align === 'right'
                        ? 'text-right'
                        : col.align === 'center'
                          ? 'text-center'
                          : ''
                    }
                  >
                    {col.header}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {rows.map((row) => (
                <tr key={getKey(row)}>
                  {columns.map((col) => (
                    <td
                      key={col.key}
                      className={
                        col.align === 'right'
                          ? 'text-right'
                          : col.align === 'center'
                            ? 'text-center'
                            : ''
                      }
                    >
                      {col.cell(row)}
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* ============================= MOBILE ============================ */}
      <div className="space-y-3 lg:hidden">
        {rows.map((row) => (
          <article key={getKey(row)} className="phm-card space-y-3">
            {primaryColumn && (
              <div className="border-b border-garis pb-3">{primaryColumn.cell(row)}</div>
            )}

            <dl className="space-y-2.5">
              {mobileColumns
                .filter((col) => !col.primary)
                .map((col) => (
                  <div key={col.key} className="flex items-start justify-between gap-4">
                    {!col.hideLabelOnMobile && (
                      <dt className="shrink-0 text-label text-redup">{col.header}</dt>
                    )}
                    <dd
                      className={`min-w-0 text-isi ${
                        col.hideLabelOnMobile ? 'w-full' : 'text-right'
                      }`}
                    >
                      {col.cell(row)}
                    </dd>
                  </div>
                ))}
            </dl>
          </article>
        ))}
      </div>
    </>
  );
}
