<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeeshaMessage extends Model
{
    public const AUTHOR_USER = 'user';

    public const AUTHOR_ASSISTANT = 'assistant';

    protected $fillable = [
        'user_id',
        'conversation_id',
        'author',
        'content',
        'tool_used',
        'provider',
        'data_context',
    ];

    protected function casts(): array
    {
        return ['data_context' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFromUser(): bool
    {
        return $this->author === self::AUTHOR_USER;
    }
}
