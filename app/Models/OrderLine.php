<?php

namespace App\Models;

use App\Enums\AssetType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id', 'line_type', 'instrument_set_id', 'item_id',
    'quantity_requested', 'quantity_fulfilled', 'notes',
])]
class OrderLine extends Model
{
    protected function casts(): array
    {
        return ['line_type' => AssetType::class];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function instrumentSet(): BelongsTo
    {
        return $this->belongsTo(InstrumentSet::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function subjectName(): string
    {
        return $this->line_type === AssetType::Set
            ? ($this->instrumentSet?->name ?? '(set terhapus)')
            : ($this->item?->name ?? '(alat terhapus)');
    }

    public function subjectCode(): string
    {
        return $this->line_type === AssetType::Set
            ? ($this->instrumentSet?->code ?? 'tidak ada')
            : ($this->item?->code ?? 'tidak ada');
    }

    public function shortage(): int
    {
        return max(0, $this->quantity_requested - $this->quantity_fulfilled);
    }
}
