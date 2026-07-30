<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\Pickup;
use Illuminate\Support\Facades\DB;

/**
 * Nomor dokumen yang mudah dibaca manusia, mis. DO-20260728-0001.
 * Nomor urut di-reset tiap hari agar tetap pendek saat disebut lewat telepon.
 */
class DocumentNumberGenerator
{
    public function deliveryOrder(): string
    {
        return $this->next('DO', DeliveryOrder::class, 'order_number');
    }

    public function pickup(): string
    {
        return $this->next('PU', Pickup::class, 'pickup_number');
    }

    private function next(string $prefix, string $modelClass, string $column): string
    {
        $datePart = now()->format('Ymd');
        $needle = "{$prefix}-{$datePart}-";

        // Dikunci di level tabel supaya dua petugas yang menyimpan bersamaan
        // tidak mendapat nomor urut kembar.
        return DB::transaction(function () use ($modelClass, $column, $needle) {
            $last = $modelClass::where($column, 'like', $needle.'%')
                ->lockForUpdate()
                ->orderByDesc($column)
                ->value($column);

            $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

            return $needle.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        });
    }
}
