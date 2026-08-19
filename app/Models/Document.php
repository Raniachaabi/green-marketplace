<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id', 'type', 'number', 'sequence', 'year',
        'payload', 'pdf_path', 'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sequence' => 'integer',
            'year' => 'integer',
            'issued_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
