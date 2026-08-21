<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderLine extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id', 'listing_id', 'seller_user_id', 'seller_org_id',
        'title_snapshot', 'unit', 'qty', 'unit_price', 'line_total',
        'lot_number', 'status',
    ];

    protected function casts(): array
    {
        return [
            'title_snapshot' => 'array',
            'qty' => 'decimal:3',
            'unit_price' => 'integer',
            'line_total' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function title(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $snapshot = $this->title_snapshot ?? [];

        return $snapshot[$locale]
            ?? collect($snapshot)->filter()->first()
            ?? __('common.unknown_item');
    }

    public function formattedTotal(): string
    {
        return Money::format((int) $this->line_total);
    }
}
