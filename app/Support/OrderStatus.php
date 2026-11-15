<?php

namespace App\Support;

final class OrderStatus
{
    public const NEW = 'baru';

    public const IN_PRODUCTION = 'diproses';

    public const READY = 'siap_kirim';

    public const PARTIALLY_SHIPPED = 'sebagian_terkirim';

    public const COMPLETED = 'selesai';

    public const CANCELLED = 'dibatalkan';

    /** Orders still occupying operational attention. */
    public const ACTIVE = [self::NEW, self::IN_PRODUCTION, self::READY, self::PARTIALLY_SHIPPED];

    public const OPTIONS = [
        self::NEW => 'Baru',
        self::IN_PRODUCTION => 'Diproses',
        self::READY => 'Siap Kirim',
        self::PARTIALLY_SHIPPED => 'Sebagian Terkirim',
        self::COMPLETED => 'Selesai',
        self::CANCELLED => 'Dibatalkan',
    ];

    public static function label(string $status): string
    {
        return self::OPTIONS[$status] ?? $status;
    }

    public static function badgeClasses(string $status): string
    {
        return match ($status) {
            self::NEW => 'bg-sky-100 text-sky-900 ring-sky-300 dark:bg-sky-400/15 dark:text-sky-200 dark:ring-sky-400/40',
            self::IN_PRODUCTION => 'bg-amber-100 text-amber-900 ring-amber-300 dark:bg-amber-400/15 dark:text-amber-200 dark:ring-amber-400/40',
            self::READY => 'bg-indigo-100 text-indigo-900 ring-indigo-300 dark:bg-indigo-400/15 dark:text-indigo-200 dark:ring-indigo-400/40',
            self::PARTIALLY_SHIPPED => 'bg-violet-100 text-violet-900 ring-violet-300 dark:bg-violet-400/15 dark:text-violet-200 dark:ring-violet-400/40',
            self::COMPLETED => 'bg-emerald-100 text-emerald-900 ring-emerald-300 dark:bg-emerald-400/15 dark:text-emerald-200 dark:ring-emerald-400/40',
            self::CANCELLED => 'bg-slate-200 text-slate-700 ring-slate-300 dark:bg-slate-500/20 dark:text-slate-300 dark:ring-slate-500/40',
            default => 'bg-slate-200 text-slate-700 ring-slate-300',
        };
    }
}
