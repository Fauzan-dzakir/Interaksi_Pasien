<?php

namespace App\Livewire\Cssd;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\SterilizationRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mencetak laporan proses sterilisasi sebagai PDF, meniru formulir kertas CSSD.
 *
 * Nilai tambahnya dibanding kertas: kolom "Tahapan × Jam Mulai × Jam Selesai ×
 * Petugas" TIDAK diisi manual, melainkan direkonstruksi dari jejak audit yang
 * sudah terekam otomatis setiap kali petugas men-scan alat.
 */
class ReportPdfController extends Controller
{
    public function show(SterilizationRecord $record): Response
    {
        abort_unless(auth()->user()?->can('advanceStage', Asset::class), 403);

        $record->load(['batch.unit', 'unit', 'createdBy', 'biReadBy', 'assets.instrumentSet', 'assets.item']);

        $pdf = Pdf::loadView('pdf.sterilization-report', [
            'record' => $record,
            'stages' => $this->reconstructStages($record),
            'printedAt' => now(),
            'printedBy' => auth()->user(),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("Laporan-Sterilisasi-{$record->record_number}.pdf");
    }

    /**
     * Menyusun tabel tahapan proses dari jejak audit seluruh alat pada laporan ini.
     *
     * Untuk tiap tahap diambil waktu paling awal (jam mulai) dan paling akhir
     * (jam selesai) di antara semua alat, beserta nama petugas yang menanganinya,
     * persis kolom yang dulu ditulis tangan di formulir kertas.
     *
     * @return Collection<int, array{label: string, started_at: ?\Carbon\Carbon, finished_at: ?\Carbon\Carbon, staff: string, count: int}>
     */
    private function reconstructStages(SterilizationRecord $record): Collection
    {
        $assetIds = $record->assets->pluck('id');

        if ($assetIds->isEmpty()) {
            return collect();
        }

        $events = AssetEvent::query()
            ->whereIn('asset_id', $assetIds)
            ->with('actor')
            ->orderBy('occurred_at')
            ->get();

        // Tahapan ditampilkan mengikuti urutan alur nyata, bukan urutan kemunculan data.
        $sequence = [
            AssetStatus::ReturnPending,
            AssetStatus::Washing,
            AssetStatus::Packed,
            AssetStatus::Sterilizing,
            AssetStatus::Available,
        ];

        return collect($sequence)
            ->map(function (AssetStatus $status) use ($events) {
                $matching = $events->where('to_status', $status);

                if ($matching->isEmpty()) {
                    return null;
                }

                return [
                    'label' => $this->stageLabel($status),
                    'started_at' => $matching->min('occurred_at'),
                    'finished_at' => $matching->max('occurred_at'),
                    'staff' => $matching->map(fn (AssetEvent $e) => $e->actorName())->unique()->join(', '),
                    'count' => $matching->count(),
                ];
            })
            ->filter()
            ->values();
    }

    /** Nama tahap dalam bahasa formulir CSSD. */
    private function stageLabel(AssetStatus $status): string
    {
        return match ($status) {
            AssetStatus::ReturnPending => 'Penerimaan alat kotor',
            AssetStatus::Washing => 'Pencucian & dekontaminasi',
            AssetStatus::Packed => 'Pengemasan & pelabelan',
            AssetStatus::Sterilizing => 'Proses sterilisasi',
            AssetStatus::Available => 'Penyimpanan / siap distribusi',
            default => $status->label(),
        };
    }
}
