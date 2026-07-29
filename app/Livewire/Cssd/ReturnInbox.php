<?php

namespace App\Livewire\Cssd;

use App\Models\ReturnShipment;
use App\Services\ReturnService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Sisi kedua dari konfirmasi dua sisi: CSSD menyatakan alat kotor sudah diterima.
 *
 * Selama belum dikonfirmasi, alat menggantung di status "Menunggu Konfirmasi CSSD",
 * di situlah selisih antar-serah ketahuan lebih awal, bukan baru saat audit.
 */
class ReturnInbox extends Component
{
    use WithFileUploads, WithPagination;

    public ?int $confirmingId = null;

    public $receiverPhoto;

    public function startConfirm(int $id): void
    {
        $this->confirmingId = $id;
        $this->receiverPhoto = null;
        $this->resetErrorBag();
    }

    public function confirm(ReturnService $service): void
    {
        $shipment = ReturnShipment::with('assets')->findOrFail($this->confirmingId);

        $this->authorize('confirmReceipt', $shipment);

        $this->validate([
            'receiverPhoto' => ['nullable', 'image', 'max:4096'],
        ], [], ['receiverPhoto' => 'foto penerimaan']);

        $photoPath = $this->receiverPhoto?->store('returns', 'public');

        $service->confirmReceipt($shipment, auth()->user(), $photoPath);

        $this->confirmingId = null;
        $this->receiverPhoto = null;

        session()->flash('status', "{$shipment->return_number} dikonfirmasi. Alat masuk tahap pencucian.");
    }

    public function render()
    {
        return view('livewire.cssd.return-inbox', [
            'pending' => ReturnShipment::query()
                ->pending()
                ->with(['unit', 'sentBy', 'batch', 'assets.instrumentSet', 'assets.item'])
                ->latest('sent_at')
                ->get(),
            'history' => ReturnShipment::query()
                ->whereNotNull('confirmed_at')
                ->with(['unit', 'confirmedBy'])
                ->withCount('assets')
                ->latest('confirmed_at')
                ->paginate(10),
        ]);
    }
}
