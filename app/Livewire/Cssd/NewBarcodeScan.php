<?php

namespace App\Livewire\Cssd;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Services\AssetRegistrationService;
use App\Services\PublicCodeGenerator;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Memasang barcode baru pada alat yang selesai dekontaminasi & pengemasan.
 *
 * Perbedaan penting antara dua jenis aset, sesuai alur di lapangan:
 *  - Alat satuan : cukup scan barcode lama lalu masukkan barcode baru, langsung terupdate.
 *  - Set alat    : wajib foto rakitan + checklist isi (dicentang = ada, disilang = hilang),
 *                  karena isi set rawan tertinggal saat pencucian atau di ruang unit.
 *
 * Set yang isinya tidak lengkap TETAP boleh lanjut diproses; sistem hanya menandai
 * dan melaporkannya, keputusan menahan diserahkan pada manusia.
 */
class NewBarcodeScan extends Component
{
    use WithFileUploads;

    public string $lookupCode = '';

    public ?int $assetId = null;

    public string $newCode = '';

    public $photo;

    /** @var array<int, array{item_id: int, name: string, code: string, quantity: int, is_present: bool, note: string}> */
    public array $checklist = [];

    public string $feedback = '';

    public string $feedbackType = '';

    /** Mencari alat lewat barcode LAMA yang masih berlaku selama pencucian. */
    public function lookup(AssetRegistrationService $service): void
    {
        $this->authorize('advanceStage', Asset::class);

        $code = PublicCodeGenerator::normalize($this->lookupCode);
        $this->lookupCode = '';
        $this->reset(['assetId', 'newCode', 'photo', 'checklist']);

        if ($code === '') {
            return;
        }

        $asset = Asset::where('current_code', $code)->with('instrumentSet')->first();

        if (! $asset) {
            $this->flash('error', "Barcode {$code} tidak dikenal.");

            return;
        }

        if ($asset->status !== AssetStatus::Washing) {
            $this->flash('error', "{$code} berstatus \"{$asset->status->label()}\", barcode baru hanya dipasang setelah dekontaminasi selesai.");

            return;
        }

        $this->assetId = $asset->id;
        $this->flash('success', "{$asset->displayName()} siap diberi barcode baru.");

        if ($asset->asset_type === AssetType::Set) {
            $this->checklist = $service->expectedContents($asset)
                ->map(fn (array $row) => [
                    'item_id' => $row['item_id'],
                    'name' => $row['name'],
                    'code' => $row['code'],
                    'quantity' => $row['quantity'],
                    'is_present' => true,
                    'note' => '',
                ])
                ->all();
        }
    }

    public function toggleContent(int $index): void
    {
        $this->checklist[$index]['is_present'] = ! $this->checklist[$index]['is_present'];
    }

    public function save(AssetRegistrationService $service): void
    {
        $this->authorize('advanceStage', Asset::class);

        $asset = Asset::with('instrumentSet')->findOrFail($this->assetId);

        $rules = ['newCode' => ['required', 'string', 'max:32']];

        // Foto set wajib: jadi bukti visual isi set saat terjadi selisih di kemudian hari.
        if ($asset->asset_type === AssetType::Set) {
            $rules['photo'] = ['required', 'image', 'max:4096'];
        } else {
            $rules['photo'] = ['nullable', 'image', 'max:4096'];
        }

        $this->validate($rules, [
            'photo.required' => 'Foto set terakit wajib diunggah.',
        ], ['newCode' => 'barcode baru']);

        $photoPath = $this->photo?->store('assembled-sets', 'public');

        try {
            $service->issueNewCode(
                asset: $asset,
                newCode: $this->newCode,
                actor: auth()->user(),
                photoPath: $photoPath,
                contentChecks: collect($this->checklist)->map(fn (array $row) => [
                    'item_id' => $row['item_id'],
                    'expected_quantity' => $row['quantity'],
                    'is_present' => $row['is_present'],
                    'note' => $row['note'] ?: null,
                ])->all(),
            );
        } catch (InvalidArgumentException $e) {
            $this->addError('newCode', $e->getMessage());

            return;
        }

        $missing = collect($this->checklist)->where('is_present', false)->count();

        $this->reset(['assetId', 'newCode', 'photo', 'checklist']);

        session()->flash('status', $missing > 0
            ? "Barcode baru terpasang. Perhatian: {$missing} isi set tidak ditemukan dan set ditandai tidak lengkap."
            : 'Barcode baru terpasang, alat siap masuk sterilisasi.');
    }

    private function flash(string $type, string $message): void
    {
        $this->feedbackType = $type;
        $this->feedback = $message;
    }

    public function render()
    {
        $asset = $this->assetId
            ? Asset::with(['instrumentSet', 'item', 'batch.unit'])->find($this->assetId)
            : null;

        return view('livewire.cssd.new-barcode-scan', [
            'asset' => $asset,
            'missingCount' => collect($this->checklist)->where('is_present', false)->count(),
            'washingQueue' => Asset::query()
                ->where('status', AssetStatus::Washing)
                ->with(['instrumentSet', 'item', 'batch.unit'])
                ->orderBy('status_changed_at')
                ->limit(30)
                ->get(),
        ]);
    }
}
