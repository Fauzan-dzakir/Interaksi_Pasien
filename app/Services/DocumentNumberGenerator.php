<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Order;
use App\Models\ReturnShipment;
use App\Models\SterilizationRecord;
use Illuminate\Support\Facades\DB;

/**
 * Nomor dokumen yang mudah dibaca manusia, mis. OR-20260728-0001.
 * Nomor urut di-reset tiap hari agar tetap pendek saat disebut lewat telepon.
 */
class DocumentNumberGenerator
{
    public function order(): string
    {
        return $this->nextDaily('OR', Order::class, 'order_number');
    }

    public function returnShipment(): string
    {
        return $this->nextDaily('RT', ReturnShipment::class, 'return_number');
    }

    public function sterilizationRecord(): string
    {
        return $this->nextDaily('ST', SterilizationRecord::class, 'record_number');
    }

    /** Kode batch tidak di-reset harian karena batch hidup lintas siklus. */
    public function batch(): string
    {
        return DB::transaction(function () {
            $last = Batch::where('code', 'like', 'BATCH-%')
                ->lockForUpdate()
                ->orderByDesc('code')
                ->value('code');

            $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

            return 'BATCH-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        });
    }

    private function nextDaily(string $prefix, string $modelClass, string $column): string
    {
        $needle = "{$prefix}-".now()->format('Ymd').'-';

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
