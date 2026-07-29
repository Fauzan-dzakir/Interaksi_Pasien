<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hasil pemeriksaan isi set saat barcode baru dibuat.
 * Dicentang = alat ada, disilang = alat tidak ditemukan.
 */
#[Fillable([
    'asset_id', 'item_id', 'expected_quantity', 'is_present',
    'checked_by_user_id', 'checked_at', 'note',
])]
class SetContentCheck extends Model
{
    protected function casts(): array
    {
        return [
            'is_present' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by_user_id');
    }

    public function scopeMissing(Builder $query): void
    {
        $query->where('is_present', false);
    }
}
