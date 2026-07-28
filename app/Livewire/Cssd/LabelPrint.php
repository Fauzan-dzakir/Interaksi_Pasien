<?php

namespace App\Livewire\Cssd;

use App\Models\DeliveryOrder;
use App\Models\ItemBatch;
use App\Services\QrCodeService;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Halaman cetak label QR. Layout-nya dibuat rapat dan hitam-putih supaya
 * hemat tinta dan tetap terbaca setelah label terkena panas/uap di area autoclave.
 */
class LabelPrint extends Component
{
    #[Url(as: 'order', keep: false)]
    public string $orderId = '';

    #[Url(as: 'q', keep: false)]
    public string $search = '';

    public function render(QrCodeService $qr)
    {
        $batches = ItemBatch::query()
            ->with(['instrumentSet', 'item', 'originUnit', 'currentDeliveryOrder'])
            ->when($this->orderId, fn ($q) => $q->where('current_delivery_order_id', $this->orderId))
            ->when($this->search, fn ($q) => $q->where('public_code', 'like', '%'.strtoupper($this->search).'%'))
            ->active()
            ->orderBy('public_code')
            ->limit(120)
            ->get();

        return view('livewire.cssd.label-print', [
            'batches' => $batches,
            // QR dirender sebagai SVG inline supaya tajam di segala ukuran cetak
            // dan tidak bergantung ekstensi gambar di server.
            'qrSvgs' => $batches->mapWithKeys(fn (ItemBatch $b) => [$b->id => $qr->svg($b->public_code, 120)]),
            'orderOptions' => DeliveryOrder::query()
                ->whereHas('itemBatches')
                ->with('originUnit')
                ->latest('sent_at')
                ->limit(50)
                ->get(),
        ]);
    }
}
