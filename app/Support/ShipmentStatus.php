<?php

namespace App\Support;

final class ShipmentStatus
{
    public const PREPARED = 'disiapkan';

    public const IN_TRANSIT = 'dikirim';

    public const ARRIVED = 'tiba';

    public const RECEIVED = 'diterima';

    public const ISSUE = 'kendala';

    public const CANCELLED = 'dibatalkan';

    /**
     * Allowed state transitions. Anything not listed here is rejected server
     * side, so contradictory delivery statuses cannot be recorded.
     */
    public const TRANSITIONS = [
        self::PREPARED => [self::IN_TRANSIT, self::ISSUE, self::CANCELLED],
        self::IN_TRANSIT => [self::ARRIVED, self::ISSUE],
        self::ARRIVED => [self::RECEIVED, self::ISSUE],
        self::RECEIVED => [],
        self::ISSUE => [self::IN_TRANSIT, self::ARRIVED, self::CANCELLED],
        self::CANCELLED => [],
    ];

    /** Statuses that count as goods having left the factory. */
    public const COUNTS_AS_SHIPPED = [self::IN_TRANSIT, self::ARRIVED, self::RECEIVED];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /** @return array<int, string> */
    public static function nextStatuses(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::PREPARED => 'Disiapkan',
            self::IN_TRANSIT => 'Dalam Perjalanan',
            self::ARRIVED => 'Sudah Tiba',
            self::RECEIVED => 'Barang Diterima',
            self::ISSUE => 'Ada Kendala',
            self::CANCELLED => 'Dibatalkan',
            default => $status,
        };
    }

    public static function actionLabel(string $to): string
    {
        return match ($to) {
            self::IN_TRANSIT => 'Mulai Pengiriman',
            self::ARRIVED => 'Sudah Tiba',
            self::RECEIVED => 'Barang Diterima',
            self::ISSUE => 'Laporkan Kendala',
            self::CANCELLED => 'Batalkan Pengiriman',
            default => $to,
        };
    }

    public static function badgeClasses(string $status): string
    {
        return match ($status) {
            self::PREPARED => 'bg-slate-200 text-slate-800 ring-slate-300 dark:bg-slate-500/20 dark:text-slate-200 dark:ring-slate-500/40',
            self::IN_TRANSIT => 'bg-sky-100 text-sky-900 ring-sky-300 dark:bg-sky-400/15 dark:text-sky-200 dark:ring-sky-400/40',
            self::ARRIVED => 'bg-indigo-100 text-indigo-900 ring-indigo-300 dark:bg-indigo-400/15 dark:text-indigo-200 dark:ring-indigo-400/40',
            self::RECEIVED => 'bg-emerald-100 text-emerald-900 ring-emerald-300 dark:bg-emerald-400/15 dark:text-emerald-200 dark:ring-emerald-400/40',
            self::ISSUE => 'bg-rose-100 text-rose-900 ring-rose-300 dark:bg-rose-400/15 dark:text-rose-200 dark:ring-rose-400/40',
            self::CANCELLED => 'bg-slate-200 text-slate-700 ring-slate-300 dark:bg-slate-500/20 dark:text-slate-300 dark:ring-slate-500/40',
            default => 'bg-slate-200 text-slate-700 ring-slate-300',
        };
    }
}
