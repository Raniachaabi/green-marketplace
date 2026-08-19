<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recall extends Model
{
    use HasUuids;

    protected $fillable = [
        'listing_id', 'lot_number', 'reason', 'initiated_by_admin_id', 'status',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(RecallNotification::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_admin_id');
    }
}
