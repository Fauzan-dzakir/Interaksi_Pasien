<?php

namespace App\Livewire\Cssd;

use App\Enums\AssetStatus;
use App\Enums\ScanInputMethod;
use App\Exceptions\InvalidTransitionException;
use App\Models\Asset;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PublicCodeGenerator;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Penanganan pesanan cuci di CSSD.
 *
 * Unit hanya mengirim foto, jadi pendataan sesungguhnya terjadi di sini:
 * petugas men-scan barcode tiap alat kotor yang datang. Setelah semua alat
 * selesai disterilkan, pesanan diserahkan kembali ke unit disertai foto bukti.
 */
class OrderPrepare extends Component
{
    use WithFileUploads;

    public Order $order;

    public string $scanCode = '';

    public string $feedback = '';

    public string $feedbackType = '';

    public bool $showHandBack = false;

    public string $receiverName = '';

    public $handoverPhoto;

    public bool $showCancel = false;

    public string $cancelReason = '';

    public function mount(Order $order): void
    {
        $this->authorize('view', $order);
        $this->order = $order;
    }

    /** Satu-satunya titik masuk pendataan alat kotor, lewat scan barcode lama. */
    public function receiveScan(OrderService $service): void
    {
        $this->authorize('prepare', $this->order);

        $code = PublicCodeGenerator::normalize($this->scanCode);
        $this->scanCode = '';

        if ($code === '') {
            return;
        }

        $asset = Asset::where('current_code', $code)->first();

        if (! $asset) {
            $this->flash('error', "Barcode {$code} tidak dikenal.");

            return;
        }

        if ($this->order->assets()->whereKey($asset->id)->exists()) {
            $this->flash('info', "{$code} sudah terdata pada pesanan ini.");

            return;
        }

        try {
            $service->receiveDirtyAsset($this->order, $asset, auth()->user(), ScanInputMethod::HidScanner);
        } catch (InvalidArgumentException|InvalidTransitionException $e) {
            $this->flash('error', $e->getMessage());

            return;
        }

        $this->order->refresh();
        $this->flash('success', "{$asset->displayName()} diterima dan masuk antrian pencucian.");
    }

    public function handBack(OrderService $service): void
    {
        $this->authorize('prepare', $this->order);

        $this->validate([
            'handoverPhoto' => ['nullable', 'image', 'max:4096'],
            'receiverName' => ['nullable', 'string', 'max:255'],
        ], [], ['handoverPhoto' => 'foto serah terima']);

        $photoPath = $this->handoverPhoto?->store('handovers', 'public');

        try {
            $service->handBack($this->order, auth()->user(), $photoPath, $this->receiverName ?: null);
        } catch (InvalidArgumentException $e) {
            $this->addError('receiverName', $e->getMessage());

            return;
        }

        $this->showHandBack = false;
        $this->reset(['handoverPhoto', 'receiverName']);
        $this->order->refresh();

        session()->flash('status', 'Alat dikembalikan ke unit dan unit sudah diberi notifikasi.');
    }

    public function cancel(OrderService $service): void
    {
        $this->authorize('cancel', $this->order);

        $this->validate([
            'cancelReason' => ['required', 'string', 'min:5', 'max:500'],
        ], [], ['cancelReason' => 'alasan pembatalan']);

        try {
            $service->cancel($this->order, auth()->user(), $this->cancelReason);
        } catch (InvalidArgumentException $e) {
            $this->addError('cancelReason', $e->getMessage());

            return;
        }

        $this->showCancel = false;
        $this->cancelReason = '';
        $this->order->refresh();

        session()->flash('status', 'Pesanan dibatalkan.');
    }

    private function flash(string $type, string $message): void
    {
        $this->feedbackType = $type;
        $this->feedback = $message;
    }

    public function render()
    {
        $this->order->load([
            'unit', 'requestedBy', 'batch', 'preparedBy', 'receivedBy',
            'photos', 'assets.instrumentSet', 'assets.item', 'events.actor',
        ]);

        $assets = $this->order->assets;

        // Pengembalian hanya boleh dilakukan bila seluruh alat sudah selesai steril.
        $allDone = $assets->isNotEmpty()
            && $assets->every(fn (Asset $a) => $a->status === AssetStatus::Available);

        return view('livewire.cssd.order-prepare', [
            'canPrepare' => auth()->user()->can('prepare', $this->order),
            'canHandBack' => auth()->user()->can('prepare', $this->order)
                && $allDone
                && $this->order->status === \App\Enums\OrderStatus::Preparing,
            'allDone' => $allDone,
            'pendingAssets' => $assets->filter(fn (Asset $a) => $a->status !== AssetStatus::Available),
        ]);
    }
}
