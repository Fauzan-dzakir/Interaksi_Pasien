<?php

namespace App\Models;

use App\Enums\MaterialSensitivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['code', 'name', 'category', 'material_sensitivity', 'notes', 'photo_path', 'is_active'])]
class Item extends Model
{
    protected function casts(): array
    {
        return [
            'material_sensitivity' => MaterialSensitivity::class,
            'is_active' => 'boolean',
        ];
    }

    public function instrumentSets(): BelongsToMany
    {
        return $this->belongsToMany(InstrumentSet::class, 'instrument_set_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
