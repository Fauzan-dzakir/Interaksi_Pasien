<?php

namespace App\Livewire\Cssd;

use App\Models\Asset;
use App\Services\PublicCodeGenerator;
use App\Services\QrCodeService;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Dua kebutuhan cetak label dalam satu halaman:
 *
 *  1. Label alat terdaftar, mencetak ulang barcode aset yang sudah ada di sistem.
 *  2. Label kosong bercode, lembar barcode baru yang dicetak lebih dulu, lalu
 *     ditempel dan discan di menu "Scan Barcode Baru" setelah dekontaminasi.
 *     Kode-nya sengaja belum terikat ke alat mana pun; pengikatan terjadi saat discan.
 */
class BarcodeStudio extends Component
{
    #[Url(as: 'q', keep: false)]
    public string $search = '';

    #[Url(as: 'mode', keep: true)]
    public string $mode = 'registered';

    public int $blankCount = 12;

    /** @var array<int, string> */
    public array $blankCodes = [];

    public function generateBlanks(PublicCodeGenerator $generator): void
    {
        $this->authorize('advanceStage', Asset::class);

        $this->validate([
            'blankCount' => ['required', 'integer', 'min:1', 'max:60'],
        ], [], ['blankCount' => 'jumlah label']);

        $this->blankCodes = collect(range(1, $this->blankCount))
            ->map(fn () => $generator->generate())
            ->all();
    }

    public function render(QrCodeService $qr)
    {
        $assets = $this->mode === 'registered'
            ? Asset::query()
                ->with(['instrumentSet', 'item', 'batch.unit'])
                ->when($this->search, fn ($q) => $q->where('current_code', 'like', '%'.strtoupper($this->search).'%'))
                ->active()
                ->orderByDesc('status_changed_at')
                ->limit(60)
                ->get()
            : collect();

        return view('livewire.cssd.barcode-studio', [
            'assets' => $assets,
            // QR dirender sebagai SVG inline supaya tajam di segala ukuran cetak
            // dan tidak bergantung pada ekstensi gambar di server.
            'assetQrs' => $assets->mapWithKeys(fn (Asset $a) => [$a->id => $qr->svg($a->current_code, 120)]),
            'blankQrs' => collect($this->blankCodes)->mapWithKeys(fn (string $c) => [$c => $qr->svg($c, 120)]),
        ]);
    }
}
