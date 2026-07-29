<?php

namespace App\Models;

use App\Enums\AssetStatus;
use App\Enums\ScanInputMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Jejak audit per aset. APPEND-ONLY: tidak boleh diubah maupun dihapus,
 * karena inilah bukti posisi alat saat terjadi audit kehilangan.
 */
#[Fillable([
    'asset_id', 'from_status', 'to_status', 'actor_user_id', 'input_method',
    'station_context', 'note', 'is_admin_override', 'metadata', 'occurred_at',
])]
class AssetEvent extends Model
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
            'from_status' => AssetStatus::class,
            'to_status' => AssetStatus::class,
            'input_method' => ScanInputMethod::class,
            'is_admin_override' => 'boolean',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
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
