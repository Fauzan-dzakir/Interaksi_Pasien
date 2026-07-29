<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Pengembalian alat kotor dari unit ke CSSD, dengan konfirmasi dua sisi.
 *
 * Nama kelas sengaja ReturnShipment (bukan Return) karena "return" adalah
 * kata kunci PHP yang tidak bisa dipakai sebagai nama kelas.
 */
#[Fillable([
    'return_number', 'unit_id', 'batch_id', 'sent_by_user_id', 'sent_at',
    'sender_photo_path', 'courier_name', 'confirmed_by_user_id', 'confirmed_at',
    'receiver_photo_path', 'reorder_batch', 'notes',
])]
class ReturnShipment extends Model
{
    protected $table = 'returns';

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'reorder_batch' => 'boolean',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'return_assets', 'return_id', 'asset_id')
            ->withPivot('was_used')
            ->withTimestamps();
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    /** Selama belum dikonfirmasi CSSD, kiriman dianggap masih menggantung. */
    public function statusLabel(): string
    {
        return $this->isConfirmed() ? 'Diterima CSSD' : 'Menunggu Konfirmasi CSSD';
    }

    public function scopePending(Builder $query): void
    {
        $query->whereNull('confirmed_at');
    }

    public function scopeForUnit(Builder $query, int $unitId): void
    {
        $query->where('unit_id', $unitId);
    }
}
