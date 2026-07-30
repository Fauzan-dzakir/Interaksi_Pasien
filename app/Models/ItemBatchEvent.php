<?php

namespace App\Models;

use App\Enums\ItemBatchStatus;
use App\Enums\ScanInputMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Jejak audit per batch. Bersifat APPEND-ONLY: baris yang sudah tercatat
 * tidak boleh diubah maupun dihapus, karena inilah bukti hukum posisi alat
 * saat terjadi audit kehilangan.
 */
#[Fillable([
    'item_batch_id', 'delivery_order_id', 'from_status', 'to_status', 'actor_user_id',
    'input_method', 'station_context', 'note', 'is_admin_override', 'metadata', 'occurred_at',
])]
class ItemBatchEvent extends Model
{
    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new RuntimeException('Jejak audit bersifat append-only dan tidak boleh diubah.');
        });

        static::deleting(function (): never {
            throw new RuntimeException('Jejak audit bersifat append-only dan tidak boleh dihapus.');
        });
    }

    protected function casts(): array
    {
        return [
            'from_status' => ItemBatchStatus::class,
            'to_status' => ItemBatchStatus::class,
            'input_method' => ScanInputMethod::class,
            'is_admin_override' => 'boolean',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function itemBatch(): BelongsTo
    {
        return $this->belongsTo(ItemBatch::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function actorName(): string
    {
        return $this->actor?->name ?? 'Sistem';
    }
}
