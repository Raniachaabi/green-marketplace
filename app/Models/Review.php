<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    use HasUuids;

    protected $fillable = [
        'author_user_id', 'order_line_id', 'target_type', 'target_id',
        'rating', 'dimensions', 'body', 'media_paths',
    ];

    protected function casts(): array
    {
        return [
            'dimensions' => 'array',
            'media_paths' => 'array',
            'rating' => 'integer',
            'seller_response_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(OrderLine::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ReviewReport::class);
    }

    public function hasSellerResponse(): bool
    {
        return $this->seller_response !== null;
    }
}
