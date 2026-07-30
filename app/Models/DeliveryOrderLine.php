<?php

namespace App\Models;

use App\Enums\BatchType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'delivery_order_id', 'line_type', 'instrument_set_id', 'item_id',
    'quantity', 'notes', 'recorded_by_user_id', 'recorded_at',
])]
class DeliveryOrderLine extends Model
{
    protected function casts(): array
    {
        return [
            'line_type' => BatchType::class,
            'recorded_at' => 'datetime',
        ];
    }

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function instrumentSet(): BelongsTo
    {
        return $this->belongsTo(InstrumentSet::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function itemBatches(): HasMany
    {
        return $this->hasMany(ItemBatch::class);
    }

    /** Checklist kelengkapan isi set — hanya relevan untuk baris "Per Set". */
    public function itemChecks(): HasMany
    {
        return $this->hasMany(DeliveryOrderLineItemCheck::class);
    }

    /** Nama alat/set yang didata pada baris ini. */
    public function subjectName(): string
    {
        return $this->line_type === BatchType::Set
            ? ($this->instrumentSet?->name ?? '(set terhapus)')
            : ($this->item?->name ?? '(alat terhapus)');
    }

    public function subjectCode(): string
    {
        return $this->line_type === BatchType::Set
            ? ($this->instrumentSet?->code ?? '—')
            : ($this->item?->code ?? '—');
    }
}
