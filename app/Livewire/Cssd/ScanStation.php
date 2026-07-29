<?php

namespace App\Livewire\Cssd;

use App\Enums\AssetStatus;
use App\Enums\ScanInputMethod;
use App\Enums\ScanStation as Station;
use App\Enums\SterilizationMethod;
use App\Exceptions\InvalidTransitionException;
use App\Models\Asset;
use App\Models\Batch;
use App\Services\SterilizationService;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Stasiun scan CSSD. Kamera HP dan scanner barcode fisik memanggil method
 * handleScan() yang sama persis, tidak ada percabangan logika bisnis
 * berdasarkan metode input; metode hanya dicatat untuk jejak audit.
 */
class ScanStation extends Component
{
    #[Url(as: 'stasiun', keep: true)]
    public string $station = 'sterilizing';

    public string $method = 'steam';

    public string $code = '';

    /** @var array<int, array{status: string, code: string, message: string, at: string}> */
    public array $log = [];

    public string $bulkBatchId = '';

    public function updatedStation(): void
    {
        $this->log = [];
    }

    /** Satu-satunya titik masuk scan, dipakai jalur scanner fisik maupun kamera. */
    public function handleScan(?string $raw = null, string $method = 'hid_scanner'): void
    {
        $this->authorize('advanceStage', Asset::class);

        $code = \App\Services\PublicCodeGenerator::normalize($raw ?? $this->code);
        $this->code = '';

        if ($code === '') {
            return;
        }

        $station = Station::from($this->station);
        $inputMethod = ScanInputMethod::tryFrom($method) ?? ScanInputMethod::Manual;

        $asset = Asset::where('current_code', $code)->first();

        if (! $asset) {
            $this->pushLog('error', $code, 'Barcode tidak dikenal. Pastikan label discan dengan benar.');

            return;
        }

        if (! $asset->status->isActive()) {
            $this->pushLog('error', $code, "Alat berstatus \"{$asset->status->label()}\" dan tidak lagi diproses.");

            return;
        }

        try {
            $changed = $station === Station::Sterilizing
                ? app(SterilizationService::class)->startSterilizing(
                    $asset, auth()->user(), SterilizationMethod::from($this->method), $inputMethod)
                : ($station === Station::Complete
                    ? app(SterilizationService::class)->complete($asset, auth()->user(), $inputMethod)
                    : app(\App\Services\AssetTransitionService::class)->transition(
                        $asset, $station->targetStatus(), auth()->user(), $inputMethod, $station->label()));
        } catch (InvalidTransitionException $e) {
            // Ditolak tegas, bukan didiamkan, salah tahap di CSSD berisiko ke pasien.
            $this->pushLog('error', $code, $e->getMessage());

            return;
        }

        if (! $changed) {
            $this->pushLog('info', $code, "Sudah berstatus \"{$station->targetStatus()->label()}\", scan diabaikan.");

            return;
        }

        $this->pushLog('success', $code, "{$asset->displayName()} → {$station->targetStatus()->label()}");
    }

    /**
     * Menyelesaikan seluruh alat satu batch sekaligus, dipakai saat satu muatan
     * autoclave keluar bersamaan, supaya petugas tidak perlu scan satu per satu.
     */
    public function completeBatch(SterilizationService $service): void
    {
        $this->authorize('advanceStage', Asset::class);

        if (! $this->bulkBatchId) {
            $this->addError('bulkBatchId', 'Pilih batch terlebih dahulu.');

            return;
        }

        $batch = Batch::findOrFail($this->bulkBatchId);
        $result = $service->completeBatch($batch, auth()->user());

        if ($result['completed'] === 0) {
            $this->addError('bulkBatchId', 'Tidak ada alat berstatus "Proses Sterilisasi" pada batch ini.');

            return;
        }

        $this->bulkBatchId = '';
        session()->flash('status', "{$result['completed']} alat pada batch \"{$batch->name}\" dinyatakan selesai.");
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
    }

    public function render()
    {
        $station = Station::from($this->station);

        $waitingStatuses = collect(AssetStatus::cases())
            ->filter(fn (AssetStatus $s) => $s->canTransitionTo($station->targetStatus()))
            ->map(fn (AssetStatus $s) => $s->value)
            ->all();

        return view('livewire.cssd.scan-station', [
            'stationEnum' => $station,
            'stationOptions' => Station::cases(),
            'methodOptions' => SterilizationMethod::options(),
            'waitingCount' => Asset::whereIn('status', $waitingStatuses)->count(),
            'atStationCount' => Asset::where('status', $station->targetStatus())->count(),
            'sterilizingBatches' => Batch::query()
                ->whereHas('assets', fn ($q) => $q->where('status', AssetStatus::Sterilizing))
                ->withCount(['assets' => fn ($q) => $q->where('status', AssetStatus::Sterilizing)])
                ->with('unit')
                ->get(),
        ]);
    }
}
