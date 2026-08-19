<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecallNotification extends Model
{
    use HasUuids;

    protected $fillable = [
        'recall_id', 'order_line_id', 'user_id', 'channel', 'sent_at', 'acknowledged_at',
    ];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'acknowledged_at' => 'datetime'];
    }

    public function recall(): BelongsTo
    {
        return $this->belongsTo(Recall::class);
    }

    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(OrderLine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
