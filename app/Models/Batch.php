<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kumpulan alat milik satu unit yang menetap lintas siklus.
 * Unit memesan ulang batch ini tiap kali butuh, boleh menambah/mengurangi isinya.
 */
#[Fillable(['code', 'name', 'unit_id', 'created_by_user_id', 'is_active', 'notes'])]
class Batch extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** Aset yang saat ini terikat pada batch ini. */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ReturnShipment::class);
    }

    /** Ringkasan isi batch untuk ditampilkan: "1 set, 2 alat". */
    public function summary(): string
    {
        $sets = $this->assets->where('asset_type', \App\Enums\AssetType::Set)->count();
        $items = $this->assets->where('asset_type', \App\Enums\AssetType::Item)->count();

        return collect([
            $sets > 0 ? "{$sets} set" : null,
            $items > 0 ? "{$items} alat" : null,
        ])->filter()->join(', ') ?: 'kosong';
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeForUnit(Builder $query, int $unitId): void
    {
        $query->where('unit_id', $unitId);
    }
}
