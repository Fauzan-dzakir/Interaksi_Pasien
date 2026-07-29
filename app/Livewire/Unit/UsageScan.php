<?php

namespace App\Livewire\Unit;

use App\Enums\AssetStatus;
use App\Enums\ScanInputMethod;
use App\Exceptions\InvalidTransitionException;
use App\Models\Asset;
use App\Services\AssetTransitionService;
use App\Services\PublicCodeGenerator;
use Livewire\Component;

/**
 * Perawat menandai alat yang benar-benar dipakai.
 *
 * Alat yang tidak jadi dipakai TIDAK perlu discan, sistem tetap mengenalinya
 * sebagai "Di Unit" dan tetap bisa dikembalikan ke CSSD. Penandaan ini yang
 * membedakan alat terpakai (wajib dicuci) dari alat yang hanya dibawa.
 */
class UsageScan extends Component
{
    public string $code = '';

    /** @var array<int, array{status: string, code: string, message: string, at: string}> */
    public array $log = [];

    public function handleScan(?string $raw = null, string $method = 'hid_scanner'): void
    {
        $code = PublicCodeGenerator::normalize($raw ?? $this->code);
        $this->code = '';

        if ($code === '') {
            return;
        }

        $asset = Asset::where('current_code', $code)->first();

        if (! $asset) {
            $this->pushLog('error', $code, 'Barcode tidak dikenal.');

            return;
        }

        // Cegah unit menandai alat milik unit lain.
        if ($asset->batch && $asset->batch->unit_id !== auth()->user()->unit_id) {
            $this->pushLog('error', $code, 'Alat ini terdaftar pada batch unit lain.');

            return;
        }

        try {
            $changed = app(AssetTransitionService::class)->transition(
                $asset,
                AssetStatus::InUse,
                auth()->user(),
                ScanInputMethod::tryFrom($method) ?? ScanInputMethod::Manual,
                'Penandaan Pemakaian Unit',
            );
        } catch (InvalidTransitionException $e) {
            $this->pushLog('error', $code, $e->getMessage());

            return;
        }

        $this->pushLog(
            $changed ? 'success' : 'info',
            $code,
            $changed
                ? "{$asset->displayName()} ditandai sedang dipakai."
                : 'Alat ini memang sudah bertanda dipakai.',
        );
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
        $unitId = auth()->user()->unit_id;

        return view('livewire.unit.usage-scan', [
            'atUnit' => Asset::query()
                ->whereIn('status', [AssetStatus::AtUnit, AssetStatus::InUse])
                ->where(fn ($q) => $q->whereNull('batch_id')
                    ->orWhereHas('batch', fn ($b) => $b->where('unit_id', $unitId)))
                ->with(['instrumentSet', 'item'])
                ->orderBy('status')
                ->get(),
        ]);
    }
}
