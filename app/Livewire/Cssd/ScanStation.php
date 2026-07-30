<?php

namespace App\Livewire\Cssd;

use App\Enums\ItemBatchStatus;
use App\Enums\ScanInputMethod;
use App\Enums\ScanStation as Station;
use App\Exceptions\InvalidTransitionException;
use App\Models\ItemBatch;
use App\Services\DeliveryOrderService;
use App\Services\ItemBatchTransitionService;
use App\Services\PublicCodeGenerator;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Stasiun scan di tiap tahap proses CSSD.
 *
 * Kamera HP dan scanner barcode fisik memanggil method handleScan() yang sama
 * persis — tidak ada percabangan logika bisnis berdasarkan metode input.
 * Metode input hanya dicatat di jejak audit untuk keperluan penelusuran.
 */
class ScanStation extends Component
{
    #[Url(as: 'stasiun', keep: true)]
    public Station $station = Station::Washing;

    public string $code = '';

    /** @var array<int, array{status: string, code: string, message: string, at: string}> */
    public array $log = [];

    public function updatedStation(): void
    {
        $this->log = [];
    }

    /**
     * Satu-satunya titik masuk scan — dipanggil dari input scanner fisik (Enter)
     * maupun dari callback kamera lewat Alpine.
     */
    public function handleScan(?string $raw = null, string $method = 'hid_scanner'): void
    {
        $this->authorize('advanceStage', ItemBatch::class);

        $code = PublicCodeGenerator::normalize($raw ?? $this->code);
        $this->code = '';

        if ($code === '') {
            return;
        }

        $station = $this->station;
        $inputMethod = ScanInputMethod::tryFrom($method) ?? ScanInputMethod::Manual;

        $batch = ItemBatch::where('public_code', $code)->first();

        if (! $batch) {
            $this->pushLog('error', $code, 'Kode tidak dikenal. Pastikan label discan dengan benar.');

            return;
        }

        if (! $batch->status->isActive()) {
            $this->pushLog('error', $code, "Alat ini berstatus \"{$batch->status->label()}\" dan tidak lagi diproses.");

            return;
        }

        try {
            $changed = app(ItemBatchTransitionService::class)->transition(
                $batch,
                $station->targetStatus(),
                auth()->user(),
                $inputMethod,
                $station->label(),
            );
        } catch (InvalidTransitionException $e) {
            // Ditolak tegas, bukan didiamkan — salah tahap di CSSD berisiko ke pasien.
            $this->pushLog('error', $code, $e->getMessage());

            return;
        }

        if (! $changed) {
            $this->pushLog('info', $code, "Sudah berstatus \"{$station->targetStatus()->label()}\" — scan diabaikan.");

            return;
        }

        if ($batch->currentDeliveryOrder) {
            app(DeliveryOrderService::class)->syncStatus($batch->currentDeliveryOrder, auth()->user());
        }

        $this->pushLog('success', $code, "{$batch->displayName()} → {$station->targetStatus()->label()}");
    }

    private function pushLog(string $status, string $code, string $message): void
    {
        array_unshift($this->log, [
            'status' => $status,
            'code' => $code,
            'message' => $message,
            'at' => now()->format('H:i:s'),
        ]);

        $this->log = array_slice($this->log, 0, 15);

        // Riwayat scan di halaman tetap ada seperti biasa — pop-up ini tambahan
        // supaya hasil scan kelihatan tanpa perlu scroll, terutama di HP.
        $this->dispatch('toast', type: $status, message: $message);
    }

    public function render()
    {
        $station = $this->station;

        // Alat yang saat ini berada tepat sebelum tahap stasiun ini — jadi panduan
        // petugas tentang apa yang seharusnya ada di meja mereka.
        $waitingStatuses = collect(ItemBatchStatus::cases())
            ->filter(fn (ItemBatchStatus $s) => $s->canTransitionTo($station->targetStatus()))
            ->map(fn (ItemBatchStatus $s) => $s->value)
            ->all();

        return view('livewire.cssd.scan-station', [
            'stationEnum' => $station,
            'stationOptions' => Station::cases(),
            'waitingCount' => ItemBatch::whereIn('status', $waitingStatuses)->count(),
            'atStationCount' => ItemBatch::where('status', $station->targetStatus())->count(),
        ]);
    }
}
