<?php

namespace App\Livewire\Shared;

use App\Enums\AssetStatus;
use App\Exceptions\InvalidTransitionException;
use App\Models\Asset;
use App\Services\AssetTransitionService;
use App\Services\QrCodeService;
use InvalidArgumentException;
use Livewire\Component;

/**
 * Riwayat satu aset, inti pembuktian saat audit kehilangan: setiap perpindahan
 * lengkap dengan pelaku, jam, dan metode input, ditambah riwayat pergantian barcode.
 */
class AssetShow extends Component
{
    public Asset $asset;

    public string $overrideStatus = '';

    public string $overrideReason = '';

    public bool $showOverride = false;

    public function mount(Asset $asset): void
    {
        $this->authorize('view', $asset);
        $this->asset = $asset;
    }

    public function moveTo(string $status, AssetTransitionService $transitions): void
    {
        $this->authorize('advanceStage', Asset::class);

        $target = AssetStatus::from($status);

        try {
            $transitions->transition(
                $this->asset,
                $target,
                auth()->user(),
                \App\Enums\ScanInputMethod::Manual,
                'Tindakan dari halaman detail',
            );
        } catch (InvalidTransitionException $e) {
            $this->addError('action', $e->getMessage());

            return;
        }

        $this->asset->refresh();
        session()->flash('status', "Alat dipindah ke \"{$target->label()}\".");
    }

    /** Koreksi Admin, boleh melompati alur, tapi alasannya wajib dan tercatat. */
    public function applyOverride(AssetTransitionService $transitions): void
    {
        $this->authorize('override', Asset::class);

        $this->validate([
            'overrideStatus' => ['required'],
            'overrideReason' => ['required', 'string', 'min:5', 'max:500'],
        ], [], [
            'overrideStatus' => 'status tujuan',
            'overrideReason' => 'alasan koreksi',
        ]);

        try {
            $transitions->adminOverride(
                $this->asset,
                AssetStatus::from($this->overrideStatus),
                auth()->user(),
                $this->overrideReason,
            );
        } catch (InvalidArgumentException $e) {
            $this->addError('overrideReason', $e->getMessage());

            return;
        }

        $this->showOverride = false;
        $this->reset(['overrideStatus', 'overrideReason']);
        $this->asset->refresh();

        session()->flash('status', 'Koreksi tersimpan dan tercatat pada jejak audit.');
    }

    public function render(QrCodeService $qr)
    {
        $this->asset->load([
            'instrumentSet', 'item', 'batch.unit', 'events.actor', 'codes.issuedBy',
            'contentChecks.item', 'contentChecks.checkedBy',
        ]);

        $user = auth()->user();

        // Hasil pemeriksaan isi set terbaru saja, yang lama tetap tersimpan di riwayat.
        $latestCheckAt = $this->asset->contentChecks->first()?->checked_at;

        return view('livewire.shared.asset-show', [
            'qrSvg' => $qr->svg($this->asset->current_code, 150),
            'canAdvance' => $user->can('advanceStage', Asset::class),
            'canOverride' => $user->can('override', Asset::class),
            'nextOptions' => $this->asset->status->allowedNext(),
            'overrideOptions' => AssetStatus::cases(),
            'latestChecks' => $latestCheckAt
                ? $this->asset->contentChecks->where('checked_at', $latestCheckAt)
                : collect(),
        ]);
    }
}
