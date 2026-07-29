<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat barcode satu aset. Barcode lama tidak dihapus, hanya diberi
 * retired_at, supaya kode lama tetap bisa dicari saat penelusuran audit.
 */
#[Fillable(['asset_id', 'code', 'issued_by_user_id', 'issued_at', 'retired_at'])]
class AssetCode extends Model
{
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'retired_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    public function isActive(): bool
    {
        return $this->retired_at === null;
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereNull('retired_at');
    }
}
