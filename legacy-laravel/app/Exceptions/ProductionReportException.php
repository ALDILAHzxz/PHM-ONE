<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A business-rule rejection from the production reporting service, carrying a
 * message already written for the worker (Bahasa Indonesia, no jargon) plus
 * the form field it belongs to.
 */
class ProductionReportException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $field = 'quantity_completed',
    ) {
        parent::__construct($message);
    }

    public static function assignmentClosed(): self
    {
        return new self(
            'Penugasan ini sudah ditutup sehingga tidak bisa menerima pencatatan baru. Hubungi atasan Anda.',
            'assignment_id',
        );
    }

    public static function notAuthorized(): self
    {
        return new self(
            'Anda tidak memiliki akses untuk mencatat hasil pada penugasan ini.',
            'assignment_id',
        );
    }

    public static function exceedsRemaining(int $remaining, int $submitted, string $unit): self
    {
        return new self(
            sprintf(
                'Jumlah yang Anda masukkan (%s %s) melebihi sisa target (%s %s). '
                .'Periksa kembali jumlahnya, atau minta atasan mencatat sebagai pengecualian.',
                number_format($submitted, 0, ',', '.'),
                $unit,
                number_format($remaining, 0, ',', '.'),
                $unit,
            ),
        );
    }

    public static function assignmentChanged(): self
    {
        return new self(
            'Ada perubahan pada penugasan ini. Silakan periksa kembali.',
            'assignment_id',
        );
    }
}
