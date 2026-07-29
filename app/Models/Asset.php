<?php

namespace App\Models;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\SterilizationMethod;
use App\Enums\ZoneBucket;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'current_code', 'asset_type', 'instrument_set_id', 'item_id',
    'status', 'status_changed_at', 'is_complete', 'assembled_photo_path',
    'batch_id', 'sterilization_method', 'cycle_count', 'notes',
])]
class Asset extends Model
{
    protected function casts(): array
    {
        return [
            'asset_type' => AssetType::class,
            'status' => AssetStatus::class,
            'sterilization_method' => SterilizationMethod::class,
            'status_changed_at' => 'datetime',
            'is_complete' => 'boolean',
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

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AssetEvent::class)->orderBy('occurred_at')->orderBy('id');
    }

    /** Seluruh barcode yang pernah dipakai aset ini, terbaru dulu. */
    public function codes(): HasMany
    {
        return $this->hasMany(AssetCode::class)->orderByDesc('issued_at');
    }

    public function contentChecks(): HasMany
    {
        return $this->hasMany(SetContentCheck::class)->orderByDesc('checked_at');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_assets')->withTimestamps();
    }

    public function zone(): ZoneBucket
    {
        return $this->status->zone();
    }

    public function displayName(): string
    {
        return $this->asset_type === AssetType::Set
            ? ($this->instrumentSet?->name ?? '(set terhapus)')
            : ($this->item?->name ?? '(alat terhapus)');
    }

    public function displayCode(): string
    {
        return $this->asset_type === AssetType::Set
            ? ($this->instrumentSet?->code ?? 'tidak ada')
            : ($this->item?->code ?? 'tidak ada');
    }

    /** Unit pemilik saat ini, berasal dari batch; null berarti stok bebas. */
    public function ownerUnit(): ?Unit
    {
        return $this->batch?->unit;
    }

    public function isAvailableStock(): bool
    {
        return $this->batch_id === null && $this->status === AssetStatus::Available;
    }

    /** Isi set yang tercatat hilang pada pemeriksaan terakhir. */
    public function missingContents()
    {
        $latest = $this->contentChecks()->first()?->checked_at;

        if (! $latest) {
            return collect();
        }

        return $this->contentChecks()
            ->where('checked_at', $latest)
            ->where('is_present', false)
            ->with('item')
            ->get();
    }

    public function scopeAvailable(Builder $query): void
    {
        $query->where('status', AssetStatus::Available);
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereNotIn('status', [AssetStatus::Lost->value, AssetStatus::Retired->value]);
    }

    public function scopeInStock(Builder $query): void
    {
        $query->where('status', AssetStatus::Available)->whereNull('batch_id');
    }

    public function scopeForUnit(Builder $query, int $unitId): void
    {
        $query->whereHas('batch', fn ($q) => $q->where('unit_id', $unitId));
    }

    public function scopeInZone(Builder $query, ZoneBucket $zone): void
    {
        $query->whereIn('status', collect(AssetStatus::cases())
            ->filter(fn (AssetStatus $s) => $s->zone() === $zone)
            ->map(fn (AssetStatus $s) => $s->value)
            ->all());
    }
}
