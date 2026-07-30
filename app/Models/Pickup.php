<?php

namespace App\Models;

use App\Enums\DeliveryMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'pickup_number', 'origin_unit_id', 'delivery_method', 'dispatched_by_user_id',
    'dispatched_at', 'receiver_name', 'confirmed_by_user_id', 'confirmed_at', 'notes',
])]
class Pickup extends Model
{
    protected function casts(): array
    {
        return [
            'delivery_method' => DeliveryMethod::class,
            'dispatched_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function originUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'origin_unit_id');
    }

    public function dispatchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by_user_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }

    public function itemBatches(): BelongsToMany
    {
        return $this->belongsToMany(ItemBatch::class, 'pickup_items')->withTimestamps();
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    public function scopePending(Builder $query): void
    {
        $query->whereNull('confirmed_at');
    }

    public function scopeForUnit(Builder $query, int $unitId): void
    {
        $query->where('origin_unit_id', $unitId);
    }
}
