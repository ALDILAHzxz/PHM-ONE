<?php

namespace App\Support;

/**
 * Production report lifecycle plus the authoritative counting rules.
 *
 * A report's quantity is counted exactly once, decided only by its status:
 *
 *   menunggu_pemeriksaan -> counts toward REPORTED, not verified
 *   terverifikasi        -> counts toward REPORTED and VERIFIED
 *   perlu_perbaikan      -> counted nowhere (stays visible in the correction list)
 *   dibatalkan           -> counted nowhere
 *
 * This prevents double counting: REPORTED and VERIFIED are both derived from
 * the same rows on read, never accumulated into a running column.
 */
final class ReportStatus
{
    public const PENDING = 'menunggu_pemeriksaan';

    public const VERIFIED = 'terverifikasi';

    public const NEEDS_CORRECTION = 'perlu_perbaikan';

    public const VOIDED = 'dibatalkan';

    /** Statuses whose quantity counts as "dilaporkan". */
    public const COUNTS_AS_REPORTED = [self::PENDING, self::VERIFIED];

    /** Statuses whose quantity counts as "terverifikasi". */
    public const COUNTS_AS_VERIFIED = [self::VERIFIED];

    public static function label(string $status): string
    {
        return match ($status) {
            self::PENDING => 'Menunggu Pemeriksaan',
            self::VERIFIED => 'Terverifikasi',
            self::NEEDS_CORRECTION => 'Perlu Perbaikan',
            self::VOIDED => 'Dibatalkan',
            default => $status,
        };
    }

    /** Tailwind classes for the status badge. */
    public static function badgeClasses(string $status): string
    {
        return match ($status) {
            self::PENDING => 'bg-amber-100 text-amber-900 ring-amber-300 dark:bg-amber-400/15 dark:text-amber-200 dark:ring-amber-400/40',
            self::VERIFIED => 'bg-emerald-100 text-emerald-900 ring-emerald-300 dark:bg-emerald-400/15 dark:text-emerald-200 dark:ring-emerald-400/40',
            self::NEEDS_CORRECTION => 'bg-rose-100 text-rose-900 ring-rose-300 dark:bg-rose-400/15 dark:text-rose-200 dark:ring-rose-400/40',
            self::VOIDED => 'bg-slate-200 text-slate-700 ring-slate-300 dark:bg-slate-500/20 dark:text-slate-300 dark:ring-slate-500/40',
            default => 'bg-slate-200 text-slate-700 ring-slate-300',
        };
    }
}
