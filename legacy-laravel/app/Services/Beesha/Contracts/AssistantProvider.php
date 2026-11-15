<?php

namespace App\Services\Beesha\Contracts;

use App\Models\User;
use App\Services\Beesha\AssistantReply;

/**
 * Swappable AI backend. The demo provider answers purely from the database;
 * an approved model provider can be dropped in without touching the tool layer
 * or the authorization rules.
 */
interface AssistantProvider
{
    /**
     * @param  array<int, array{author: string, content: string}>  $history
     */
    public function answer(User $user, string $question, array $history = []): AssistantReply;

    /** Identifier stored with each message (demo | anthropic | ...). */
    public function name(): string;

    /** False when the provider is configured but unusable (e.g. missing key). */
    public function isAvailable(): bool;
}
