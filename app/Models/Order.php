<?php

namespace App\Models;

use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Pesanan alat steril oleh unit, checkout dari available stock. */
#[Fillable([
    'order_number', 'unit_id', 'batch_id', 'requested_by_user_id', 'status',
    'fulfillment_method', 'is_cito', 'needed_at', 'notes', 'verification_photo_path',
    'prepared_by_user_id', 'prepared_at', 'handover_photo_path', 'receiver_name',
    'handed_over_at', 'received_by_user_id', 'received_at',
])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'fulfillment_method' => FulfillmentMethod::class,
            'is_cito' => 'boolean',
            'needed_at' => 'datetime',
            'prepared_at' => 'datetime',
            'handed_over_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_user_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    /** Foto barang yang dilampirkan unit saat memesan cuci. */
    public function photos(): HasMany
    {
        return $this->hasMany(OrderPhoto::class);
    }

    /** Aset konkret yang dialokasikan CSSD untuk memenuhi pesanan ini. */
    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'order_assets')->withTimestamps();
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->orderBy('occurred_at');
    }

    public function totalRequested(): int
    {
        return (int) $this->lines->sum('quantity_requested');
    }

    public function totalFulfilled(): int
    {
        return (int) $this->lines->sum('quantity_fulfilled');
    }

    public function isFullyFulfilled(): bool
    {
        return $this->lines->every(
            fn (OrderLine $l) => $l->quantity_fulfilled >= $l->quantity_requested
        );
    }

    public function scopeForUnit(Builder $query, int $unitId): void
    {
        $query->where('unit_id', $unitId);
    }

    public function scopeOpen(Builder $query): void
    {
        $query->whereNotIn('status', [OrderStatus::Received->value, OrderStatus::Cancelled->value]);
    }

    public function scopeAwaitingPreparation(Builder $query): void
    {
        $query->where('status', OrderStatus::Pending);
    }
}
