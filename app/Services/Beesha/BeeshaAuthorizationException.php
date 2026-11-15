<?php

namespace App\Services\Beesha;

use RuntimeException;

class BeeshaAuthorizationException extends RuntimeException
{
    public function __construct(public readonly string $tool)
    {
        parent::__construct(sprintf(
            'Beesha tidak diizinkan mengakses data ini (%s) untuk peran Anda.',
            $tool
        ));
    }
}
