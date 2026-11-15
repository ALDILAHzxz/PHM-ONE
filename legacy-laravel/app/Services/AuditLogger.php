<?php

namespace App\Services;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

/**
 * Append-only audit trail. Called inside the same transaction as the mutation
 * it describes, so a record and its audit event commit or fail together.
 */
class AuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        ?User $actor,
        string $action,
        string $entityType,
        ?int $entityId,
        string $summary,
        array $metadata = [],
        bool $visibleInFeed = true,
    ): AuditEvent {
        return AuditEvent::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'summary' => $summary,
            'metadata' => $metadata === [] ? null : $metadata,
            'ip_address' => Request::ip(),
            'is_visible_feed' => $visibleInFeed,
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logModel(
        ?User $actor,
        string $action,
        Model $model,
        string $summary,
        array $metadata = [],
        bool $visibleInFeed = true,
    ): AuditEvent {
        return $this->log(
            $actor,
            $action,
            class_basename($model),
            $model->getKey(),
            $summary,
            $metadata,
            $visibleInFeed,
        );
    }
}
