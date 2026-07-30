<?php

namespace App\Models;

use App\Enums\PickupLocationRequestStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['unit_id', 'name', 'requested_by_user_id', 'status', 'reviewed_by_user_id', 'reviewed_at'])]
class PickupLocationRequest extends Model
{
    protected function casts(): array
    {
        return [
            'status' => PickupLocationRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', PickupLocationRequestStatus::Pending);
    }
}
