<?php

namespace App\Models;

use App\Enums\DeliveryOrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/** Jejak audit level order — append-only, sama seperti ItemBatchEvent. */
#[Fillable(['delivery_order_id', 'from_status', 'to_status', 'actor_user_id', 'note', 'occurred_at'])]
class DeliveryOrderEvent extends Model
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
            'from_status' => DeliveryOrderStatus::class,
            'to_status' => DeliveryOrderStatus::class,
            'occurred_at' => 'datetime',
        ];
    }

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function actorName(): string
    {
        return $this->actor?->name ?? 'Sistem';
    }
}
