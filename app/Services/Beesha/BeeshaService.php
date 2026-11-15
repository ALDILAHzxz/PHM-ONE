<?php

namespace App\Services\Beesha;

use App\Models\BeeshaMessage;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Beesha\Contracts\AssistantProvider;
use App\Support\Permissions;
use Illuminate\Support\Collection;

/**
 * Orchestrates one assistant turn: permission gate -> persist question ->
 * provider answer -> persist answer -> audit the tool access.
 */
class BeeshaService
{
    public function __construct(
        private readonly AssistantProvider $provider,
        private readonly DemoAssistantProvider $demo,
        private readonly AuditLogger $audit,
    ) {}

    public function providerName(): string
    {
        return $this->provider->isAvailable() ? $this->provider->name() : 'demo';
    }

    public function isDemoProvider(): bool
    {
        return $this->providerName() === 'demo';
    }

    /** @return array<int, string> */
    public function suggestionsFor(User $user): array
    {
        return $this->demo->suggestionsFor($user);
    }

    /** @return Collection<int, BeeshaMessage> */
    public function history(User $user, string $conversationId): Collection
    {
        return BeeshaMessage::query()
            ->where('user_id', $user->id)
            ->where('conversation_id', $conversationId)
            ->oldest()
            ->limit(config('phm.beesha.max_history'))
            ->get();
    }

    public function ask(User $user, string $conversationId, string $question): BeeshaMessage
    {
        abort_unless($user->hasPermission(Permissions::BEESHA_USE), 403);

        $question = trim($question);

        BeeshaMessage::create([
            'user_id' => $user->id,
            'conversation_id' => $conversationId,
            'author' => BeeshaMessage::AUTHOR_USER,
            'content' => $question,
        ]);

        $history = $this->history($user, $conversationId)
            ->slice(-8)
            ->map(fn (BeeshaMessage $m) => ['author' => $m->author, 'content' => $m->content])
            ->values()
            ->all();

        // Drop the question we just stored — the provider receives it separately.
        array_pop($history);

        $reply = $this->provider->answer($user, $question, $history);

        $message = BeeshaMessage::create([
            'user_id' => $user->id,
            'conversation_id' => $conversationId,
            'author' => BeeshaMessage::AUTHOR_ASSISTANT,
            'content' => $reply->text,
            'tool_used' => $reply->tool,
            'provider' => $reply->provider,
            'data_context' => $reply->dataContext,
        ]);

        // Audit the data access, not the conversation content.
        if ($reply->tool !== null) {
            $this->audit->log(
                $user,
                'beesha.tool_called',
                'BeeshaMessage',
                $message->id,
                sprintf('%s meminta data melalui Beesha (%s).', $user->full_name, $reply->tool),
                ['tools' => $reply->tool, 'provider' => $reply->provider],
                visibleInFeed: false,
            );
        }

        return $message;
    }

    public function clearHistory(User $user, string $conversationId): void
    {
        BeeshaMessage::where('user_id', $user->id)
            ->where('conversation_id', $conversationId)
            ->delete();
    }
}
