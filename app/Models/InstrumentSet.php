<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['code', 'name', 'description', 'is_active'])]
class InstrumentSet extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'instrument_set_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /** Total jumlah alat fisik di dalam satu set (menjumlahkan qty tiap jenis). */
    public function totalItemCount(): int
    {
        return (int) $this->items->sum(fn (Item $item) => $item->pivot->quantity);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
