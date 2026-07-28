<?php

namespace App\Livewire\Unit;

use App\Enums\ItemBatchStatus;
use App\Enums\ScanInputMethod;
use App\Exceptions\InvalidTransitionException;
use App\Models\ItemBatch;
use App\Models\Pickup;
use App\Services\ItemBatchTransitionService;
use App\Services\PickupService;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Kotak masuk penerimaan alat steril di unit.
 *
 * Tombol "Diterima" inilah titik yang membuktikan alat berpindah tanggung jawab
 * dari CSSD ke unit — lengkap dengan nama penerima dan jam penerimaan.
 */
class PickupInbox extends Component
{
    use WithPagination;

    public string $code = '';

    public string $feedback = '';

    public string $feedbackType = '';

    public function confirm(int $pickupId, PickupService $service): void
    {
        $pickup = Pickup::with('itemBatches')->findOrFail($pickupId);

        $this->authorize('confirmReceipt', $pickup);

        $service->confirmReceipt($pickup, auth()->user());

        session()->flash('status', "Penerimaan {$pickup->pickup_number} dikonfirmasi. Terima kasih.");
    }

    /**
     * Menandai alat mulai dipakai. Alat yang tidak jadi dipakai cukup dibiarkan —
     * sistem tetap mengenalinya saat dikembalikan ke CSSD.
     */
    public function markInUse(ItemBatchTransitionService $transitions): void
    {
        $code = \App\Services\PublicCodeGenerator::normalize($this->code);
        $this->code = '';

        if ($code === '') {
            return;
        }

        $batch = ItemBatch::where('public_code', $code)
            ->where('origin_unit_id', auth()->user()->unit_id)
            ->first();

        if (! $batch) {
            $this->flash('error', "Kode {$code} tidak ditemukan pada alat milik unit Anda.");

            return;
        }

        try {
            $changed = $transitions->transition(
                $batch,
                ItemBatchStatus::InUse,
                auth()->user(),
                ScanInputMethod::HidScanner,
                'Penandaan Pemakaian Unit',
            );
        } catch (InvalidTransitionException $e) {
            $this->flash('error', $e->getMessage());

            return;
        }

        $this->flash(
            $changed ? 'success' : 'info',
            $changed
                ? "{$batch->displayName()} ditandai sedang dipakai."
                : "{$code} memang sudah bertanda dipakai."
        );
    }

    private function flash(string $type, string $message): void
    {
        $this->feedbackType = $type;
        $this->feedback = $message;
    }

    public function render()
    {
        $unitId = auth()->user()->unit_id;

        return view('livewire.unit.pickup-inbox', [
            'pending' => Pickup::query()
                ->forUnit($unitId)
                ->pending()
                ->with(['dispatchedBy', 'itemBatches.instrumentSet', 'itemBatches.item'])
                ->latest('dispatched_at')
                ->get(),
            'history' => Pickup::query()
                ->forUnit($unitId)
                ->whereNotNull('confirmed_at')
                ->with(['confirmedBy'])
                ->withCount('itemBatches')
                ->latest('confirmed_at')
                ->paginate(10),
            'atUnitCount' => ItemBatch::forUnit($unitId)
                ->whereIn('status', [ItemBatchStatus::PickedUp, ItemBatchStatus::InUse])
                ->count(),
        ]);
    }
}
