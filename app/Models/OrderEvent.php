<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/** Jejak audit level pesanan, append-only, sama seperti AssetEvent. */
#[Fillable(['order_id', 'from_status', 'to_status', 'actor_user_id', 'note', 'occurred_at'])]
class OrderEvent extends Model
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
            'from_status' => OrderStatus::class,
            'to_status' => OrderStatus::class,
            'occurred_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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
