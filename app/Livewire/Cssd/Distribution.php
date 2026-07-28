<?php

namespace App\Livewire\Cssd;

use App\Enums\DeliveryMethod;
use App\Enums\ItemBatchStatus;
use App\Models\ItemBatch;
use App\Models\Pickup;
use App\Models\Unit;
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

    /** Alat yang sudah steril dan tersimpan di gudang — siap diserahkan. */
    private function availableBatches()
    {
        if (! $this->unitId) {
            return collect();
        }

        return ItemBatch::query()
            ->with(['instrumentSet', 'item', 'currentDeliveryOrder'])
            ->where('origin_unit_id', $this->unitId)
            ->where('status', ItemBatchStatus::InStorage)
            ->orderBy('public_code')
            ->get();
    }

    public function render()
    {
        return view('livewire.cssd.distribution', [
            'available' => $this->availableBatches(),
            'unitOptions' => Unit::active()->orderBy('name')->get(['id', 'code', 'name']),
            'methodOptions' => DeliveryMethod::options(),
            'pendingPickups' => Pickup::query()
                ->pending()
                ->with(['originUnit', 'dispatchedBy'])
                ->withCount('itemBatches')
                ->latest('dispatched_at')
                ->limit(15)
                ->get(),
            // Ringkasan alat siap serah per unit, supaya petugas tahu unit mana yang perlu dilayani.
            'readyPerUnit' => ItemBatch::query()
                ->where('status', ItemBatchStatus::InStorage)
                ->selectRaw('origin_unit_id, count(*) as total')
                ->groupBy('origin_unit_id')
                ->with('originUnit')
                ->get(),
        ]);
    }
}
