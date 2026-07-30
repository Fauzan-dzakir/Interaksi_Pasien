<?php

namespace App\Models;

use App\Enums\DeliveryOrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_number', 'origin_unit_id', 'submitted_by_user_id',
    'courier_name', 'sent_at', 'box_count', 'is_cito', 'needed_at', 'pickup_location', 'notes', 'photos',
    'status', 'intake_recorded_at', 'intake_recorded_by_user_id',
])]
class DeliveryOrder extends Model
{
    protected function casts(): array
    {
        return [
            'status' => DeliveryOrderStatus::class,
            'sent_at' => 'datetime',
            'intake_recorded_at' => 'datetime',
            'is_cito' => 'boolean',
            'needed_at' => 'datetime',
        ];
    }

    public function originUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'origin_unit_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function intakeRecordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'intake_recorded_by_user_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DeliveryOrderLine::class);
    }

    public function itemBatches(): HasMany
    {
        return $this->hasMany(ItemBatch::class, 'current_delivery_order_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(DeliveryOrderEvent::class)->orderBy('occurred_at');
    }

    /**
     * Alat yang DIDEKLARASIKAN unit saat membuat order ini (dipilih dari alat
     * yang sudah ditandai dipakai) — rujukan pembanding saja, BUKAN pendataan
     * resmi. Pendataan resmi tetap dilakukan CSSD secara independen lewat
     * recordIntake(), yang membuat batch/barcode baru sendiri.
     */
    public function declaredBatches(): BelongsToMany
    {
        return $this->belongsToMany(ItemBatch::class, 'delivery_order_declared_batches');
    }

    /** Rincian alat baru terbuka untuk unit setelah CSSD menyimpan pendataan. */
    public function detailVisibleToUnit(): bool
    {
        return $this->status->detailVisibleToUnit();
    }

    public function scopeForUnit(Builder $query, int $unitId): void
    {
        $query->where('origin_unit_id', $unitId);
    }

    public function getStatusSummaryAttribute(): array
    {
        if (! $this->relationLoaded('itemBatches')) {
            $this->load('itemBatches');
        }

        $summary = [];
        foreach ($this->itemBatches as $batch) {
            $label = $batch->status->label();
            if (!isset($summary[$label])) {
                $summary[$label] = 0;
            }
            $summary[$label]++;
        }

        return $summary;
    }

    public function scopeOpen(Builder $query): void
    {
        $query->whereNotIn('status', [
            DeliveryOrderStatus::Completed->value,
            DeliveryOrderStatus::Cancelled->value,
        ]);
    }

    public function scopeAwaitingIntake(Builder $query): void
    {
        $query->where('status', DeliveryOrderStatus::PendingCssdIntake);
    }
}
