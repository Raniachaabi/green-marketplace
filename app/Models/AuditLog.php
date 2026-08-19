<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FR-108 / NFR-12 — append-only.
 *
 * There is deliberately no update or delete path. If you ever need to show a
 * regulator or a court that you exercised diligence, this table is the
 * argument, and a mutable audit log is not an argument.
 */
class AuditLog extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'actor_user_id', 'entity_type', 'entity_id', 'action', 'context', 'ip', 'created_at',
    ];

    protected function casts(): array
    {
        return ['context' => 'array', 'created_at' => 'datetime'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public static function record(
        string $action,
        Model $entity,
        array $context = [],
        ?User $actor = null,
    ): self {
        return static::create([
            'actor_user_id' => $actor?->id ?? auth()->id(),
            'entity_type' => class_basename($entity),
            'entity_id' => $entity->getKey(),
            'action' => $action,
            'context' => $context,
            'ip' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
