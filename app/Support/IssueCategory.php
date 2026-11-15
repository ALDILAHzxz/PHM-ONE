<?php

namespace App\Support;

final class IssueCategory
{
    public const MACHINE = 'mesin';

    public const MATERIAL = 'material';

    public const QUALITY = 'kualitas';

    public const OTHER = 'lainnya';

    public const OPTIONS = [
        self::MACHINE => 'Mesin / Peralatan',
        self::MATERIAL => 'Bahan / Material',
        self::QUALITY => 'Kualitas Produk',
        self::OTHER => 'Lainnya',
    ];

    public static function label(?string $code): string
    {
        if ($code === null) {
            return 'Tidak ada';
        }

        return self::OPTIONS[$code] ?? $code;
    }
}
