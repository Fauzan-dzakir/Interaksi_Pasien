<?php

namespace App\Models;

use App\Enums\BatchType;
use App\Enums\ItemBatchStatus;
use App\Enums\SterilizationMethod;
use App\Enums\ZoneBucket;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'public_code', 'batch_type', 'instrument_set_id', 'item_id', 'quantity',
    'status', 'status_changed_at', 'sterilization_method', 'sterilization_expired_at', 'assembled_photo_path',
    'origin_unit_id', 'current_delivery_order_id', 'delivery_order_line_id',
])]
class ItemBatch extends Model
{
    protected function casts(): array
    {
        return [
            'batch_type' => BatchType::class,
            'status' => ItemBatchStatus::class,
            'sterilization_method' => SterilizationMethod::class,
            'sterilization_expired_at' => 'datetime',
            'status_changed_at' => 'datetime',
        ];
    }

    public function instrumentSet(): BelongsTo
    {
        return $this->belongsTo(InstrumentSet::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function originUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'origin_unit_id');
    }

    public function currentDeliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class, 'current_delivery_order_id');
    }

    public function deliveryOrderLine(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrderLine::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ItemBatchEvent::class)->orderBy('occurred_at')->orderBy('id');
    }

    /** Riwayat checklist QC per tahap — dokumentasi tambahan, lihat BatchQcStage. */
    public function stageChecks(): HasMany
    {
        return $this->hasMany(ItemBatchStageCheck::class)->orderBy('recorded_at')->orderBy('id');
    }

    public function pickups()
    {
        return $this->belongsToMany(Pickup::class, 'pickup_items')->withTimestamps();
    }

    /** Tautan ke barcode penerus (jika batch ini sudah diganti). */
    public function supersededBy(): HasOne
    {
        return $this->hasOne(ItemBatchSupersession::class, 'old_item_batch_id');
    }

    /** Barcode-barcode lama yang digantikan oleh batch ini. */
    public function supersedes(): HasMany
    {
        return $this->hasMany(ItemBatchSupersession::class, 'new_item_batch_id');
    }

    public function zone(): ZoneBucket
    {
        return $this->status->zone();
    }

    public function displayName(): string
    {
        return $this->batch_type === BatchType::Set
            ? ($this->instrumentSet?->name ?? '(set terhapus)')
            : ($this->item?->name ?? '(alat terhapus)');
    }

    public function displayQuantity(): string
    {
        return $this->batch_type === BatchType::Set
            ? '1 set'
            : $this->quantity.' pcs';
    }

    /**
     * Menelusuri seluruh rantai penggantian barcode ke belakang — dipakai saat
     * audit untuk melihat riwayat satu alat fisik lintas beberapa siklus.
     *
     * @return \Illuminate\Support\Collection<int, self>
     */
    public function lineage(): \Illuminate\Support\Collection
    {
        $chain = collect([$this]);
        $current = $this;

        // Batas 50 untuk berjaga-jaga dari data melingkar akibat koreksi manual.
        for ($i = 0; $i < 50; $i++) {
            $previous = self::whereHas('supersededBy', fn ($q) => $q->where('new_item_batch_id', $current->id))->first();

            if (! $previous) {
                break;
            }

            $chain->prepend($previous);
            $current = $previous;
        }

        return $chain;
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', collect(ItemBatchStatus::cases())
            ->filter(fn (ItemBatchStatus $s) => $s->isActive())
            ->map(fn (ItemBatchStatus $s) => $s->value)
            ->all());
    }

    public function scopeInZone(Builder $query, ZoneBucket $zone): void
    {
        $query->whereIn('status', collect($zone->statuses())
            ->map(fn (ItemBatchStatus $s) => $s->value)
            ->all());
    }

    public function scopeForUnit(Builder $query, int $unitId): void
    {
        $query->where('origin_unit_id', $unitId);
    }
}
