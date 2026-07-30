<?php

namespace App\Livewire\Cssd;

use App\Enums\DeliveryMethod;
use App\Enums\ItemBatchStatus;
use App\Models\ItemBatch;
use App\Models\Pickup;
use App\Models\Unit;
use App\Services\ItemBatchTransitionService;
use App\Services\PickupService;
use InvalidArgumentException;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Menyerahkan alat steril dari gudang kembali ke unit — mencakup dua jalur
 * pada alur: diambil sendiri oleh unit, atau "Dikirim Langsung" oleh CSSD.
 */
class Distribution extends Component
{
    #[Url(as: 'unit', keep: false)]
    public string $unitId = '';

    /** @var array<int, int> */
    public array $selected = [];

    public string $method = 'unit_pickup';

    public string $receiverName = '';

    public string $notes = '';

    public function updatedUnitId(): void
    {
        $this->selected = [];
    }

    public function toggleAll(): void
    {
        $available = $this->availableBatches()->pluck('id')->all();

        $this->selected = count($this->selected) === count($available) ? [] : $available;
    }

    public function dispatchItems(PickupService $service): void
    {
        $this->authorize('advanceStage', ItemBatch::class);

        $this->validate([
            'unitId' => ['required', 'exists:units,id'],
            'selected' => ['required', 'array', 'min:1'],
            'method' => ['required', 'in:unit_pickup,direct_delivery'],
            'receiverName' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'unitId.required' => 'Pilih unit tujuan terlebih dahulu.',
            'selected.required' => 'Pilih minimal satu alat untuk diserahkan.',
        ]);

        try {
            $pickup = $service->dispatch(
                staff: auth()->user(),
                unitId: (int) $this->unitId,
                batchIds: array_map('intval', $this->selected),
                method: DeliveryMethod::from($this->method),
                receiverName: $this->receiverName ?: null,
                notes: $this->notes ?: null,
            );
        } catch (InvalidArgumentException $e) {
            $this->addError('selected', $e->getMessage());

            return;
        }

        $this->reset(['selected', 'receiverName', 'notes']);

        session()->flash('status', "{$pickup->pickup_number} dibuat. Unit sudah diberi notifikasi untuk mengonfirmasi penerimaan.");
    }

    /**
     * Menandai alat yang masa sterilnya sudah lewat agar dikemas & disterilkan
     * ulang — mencegah alat kedaluwarsa terlanjur diserahkan ke unit.
     */
    public function reSterilize(int $batchId, ItemBatchTransitionService $transitions): void
    {
        $this->authorize('advanceStage', ItemBatch::class);

        $batch = ItemBatch::findOrFail($batchId);

        try {
            $transitions->flagExpiredForResterilization($batch, auth()->user());
        } catch (InvalidArgumentException $e) {
            $this->addError('resterilize', $e->getMessage());

            return;
        }

        session()->flash('status', "{$batch->public_code} dikirim balik untuk sterilisasi ulang.");
    }

    /** Alat yang sudah steril, tersimpan di gudang, dan MASIH berlaku sterilisasinya — siap diserahkan. */
    private function availableBatches()
    {
        if (! $this->unitId) {
            return collect();
        }

        return ItemBatch::query()
            ->with(['instrumentSet', 'item', 'currentDeliveryOrder'])
            ->where('origin_unit_id', $this->unitId)
            ->where('status', ItemBatchStatus::InStorage)
            ->where(function ($q) {
                $q->whereNull('sterilization_expired_at')
                    ->orWhere('sterilization_expired_at', '>=', now());
            })
            ->orderBy('public_code')
            ->get();
    }

    /** Alat di gudang yang masa sterilnya sudah lewat — tidak boleh diserahkan sampai disterilkan ulang. */
    private function expiredBatches()
    {
        if (! $this->unitId) {
            return collect();
        }

        return ItemBatch::query()
            ->with(['instrumentSet', 'item', 'currentDeliveryOrder'])
            ->where('origin_unit_id', $this->unitId)
            ->where('status', ItemBatchStatus::InStorage)
            ->whereNotNull('sterilization_expired_at')
            ->where('sterilization_expired_at', '<', now())
            ->orderBy('sterilization_expired_at')
            ->get();
    }

    public function render()
    {
        return view('livewire.cssd.distribution', [
            'available' => $this->availableBatches(),
            'expired' => $this->expiredBatches(),
            'unitOptions' => Unit::active()->orderBy('name')->get(['id', 'code', 'name']),
            'methodOptions' => DeliveryMethod::options(),
            'pendingPickups' => Pickup::query()
                ->pending()
                ->with(['originUnit', 'dispatchedBy'])
                ->withCount('itemBatches')
                ->latest('dispatched_at')
                ->limit(15)
                ->get(),
            // Ringkasan alat siap serah per unit (yang masih berlaku sterilisasinya), supaya petugas tahu unit mana yang perlu dilayani.
            'readyPerUnit' => ItemBatch::query()
                ->where('status', ItemBatchStatus::InStorage)
                ->where(function ($q) {
                    $q->whereNull('sterilization_expired_at')
                        ->orWhere('sterilization_expired_at', '>=', now());
                })
                ->selectRaw('origin_unit_id, count(*) as total')
                ->groupBy('origin_unit_id')
                ->with('originUnit')
                ->get(),
        ]);
    }
}
