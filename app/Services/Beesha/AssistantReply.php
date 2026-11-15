<?php

namespace App\Services\Beesha;

/**
 * One assistant answer, plus where it came from. The provider and the tool are
 * recorded so a user can always see whether an answer rests on real authorized
 * records or on a model's own words.
 */
class AssistantReply
{
    public function __construct(
        public readonly string $text,
        public readonly ?string $tool = null,
        public readonly string $provider = 'demo',
        public readonly ?array $dataContext = null,
    ) {}
}
