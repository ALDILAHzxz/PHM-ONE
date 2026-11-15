<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditEvent extends Model
{
    protected $fillable = [
        'actor_id',
        'action',
        'entity_type',
        'entity_id',
        'summary',
        'metadata',
        'ip_address',
        'is_visible_feed',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_visible_feed' => 'boolean',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function scopeFeed(Builder $query): Builder
    {
        return $query->where('is_visible_feed', true)->latest();
    }
}
