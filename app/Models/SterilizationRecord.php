<?php

namespace App\Models;

use App\Enums\BiologicalIndicatorResult;
use App\Enums\SterilizationMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Catatan satu proses sterilisasi, sumber data laporan PDF pengganti formulir kertas.
 *
 * Tahapan, jam, dan petugas TIDAK disimpan di sini: semuanya sudah ada di jejak
 * audit asset_events dan diambil saat laporan dicetak. Tabel ini hanya menampung
 * hal yang tidak terekam otomatis: metode dan hasil indikator biologi.
 */
#[Fillable([
    'record_number', 'batch_id', 'unit_id', 'method', 'biological_indicator_result',
    'bi_incubated_at', 'bi_read_at', 'bi_read_by_user_id', 'created_by_user_id', 'notes',
])]
class SterilizationRecord extends Model
{
    protected function casts(): array
    {
        return [
            'method' => SterilizationMethod::class,
            'biological_indicator_result' => BiologicalIndicatorResult::class,
            'bi_incubated_at' => 'datetime',
            'bi_read_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function biReadBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bi_read_by_user_id');
    }

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'sterilization_record_assets')->withTimestamps();
    }
}
