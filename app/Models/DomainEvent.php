<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $aggregate_type
 * @property int $aggregate_id
 * @property string $event_type
 * @property int|null $actor_id
 * @property array<string, mixed>|null $metadata
 * @property Carbon $created_at
 * @property User|null $actor
 */
#[Fillable(['aggregate_type', 'aggregate_id', 'event_type', 'actor_id', 'metadata', 'created_at'])]
class DomainEvent extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aggregate_id' => 'integer',
            'actor_id' => 'integer',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
