<?php

namespace App\Support;

use App\Models\DomainEvent;
use App\Models\User;

class DomainEventService
{
    public const AGGREGATE_ORDER = 'order';

    public const AGGREGATE_ORDER_ITEM = 'order_item';

    public const AGGREGATE_CONSIGNMENT = 'consignment';

    public const AGGREGATE_UP_JURUSAN = 'up_jurusan';

    /**
     * Persist a domain event. Must be called inside an open DB transaction
     * so rollback discards the audit row.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function record(
        string $aggregateType,
        int $aggregateId,
        string $eventType,
        ?User $actor = null,
        array $metadata = [],
    ): DomainEvent {
        return DomainEvent::query()->create([
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'event_type' => $eventType,
            'actor_id' => $actor?->id,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
