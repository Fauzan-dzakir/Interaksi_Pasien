<?php

namespace App\Livewire\Unit;

use App\Models\Batch;
use App\Models\Order;
use App\Services\OrderService;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Pesanan cuci alat, seperti order laundry.
 *
 * Unit TIDAK mendata barang satu per satu. Cukup lampirkan foto alat kotor
 * yang akan dikirim (bisa langsung dijepret dari kamera), beri catatan bila
 * perlu, dan tandai CITO untuk pasien gawat. Pendataan sesungguhnya dilakukan
 * CSSD dengan men-scan barcode tiap alat yang datang.
 */
class OrderCreate extends Component
{
    use WithFileUploads;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $photos = [];

    /** Jepretan kamera, masuk satu per satu lalu ditampung ke $photos. */
    public $cameraShot;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> Pilihan dari galeri, bisa banyak sekaligus. */
    public $galleryPicks = [];

    public bool $isCito = false;

    public string $neededAt = '';

    public string $notes = '';

    public string $batchId = '';

    public function mount(): void
    {
        $this->authorize('create', Order::class);
    }

    public function updatedCameraShot(): void
    {
        $this->validate(['cameraShot' => ['image', 'max:4096']], [], ['cameraShot' => 'foto']);

        $this->photos[] = $this->cameraShot;
        $this->cameraShot = null;
    }

    public function updatedGalleryPicks(): void
    {
        $this->validate(['galleryPicks.*' => ['image', 'max:4096']], [], ['galleryPicks.*' => 'foto']);

        foreach ($this->galleryPicks as $pick) {
            $this->photos[] = $pick;
        }

        $this->galleryPicks = [];
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function save(OrderService $service): void
    {
        $this->authorize('create', Order::class);

        $rules = [
            'photos' => ['required', 'array', 'min:1'],
            'isCito' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'batchId' => ['nullable', 'exists:batches,id'],
        ];

        // Waktu dibutuhkan hanya berlaku untuk pesanan CITO.
        if ($this->isCito) {
            $rules['neededAt'] = ['required', 'date'];
        }

        $this->validate($rules, [
            'photos.required' => 'Lampirkan minimal satu foto barang yang akan dicuci.',
            'neededAt.required' => 'Pesanan CITO wajib mencantumkan kapan alat dibutuhkan.',
        ], ['neededAt' => 'waktu dibutuhkan']);

        $paths = collect($this->photos)
            ->map(fn ($photo) => $photo->store('order-photos', 'public'))
            ->all();

        try {
            $order = $service->create(
                requester: auth()->user(),
                photoPaths: $paths,
                notes: $this->notes ?: null,
                isCito: $this->isCito,
                neededAt: $this->isCito ? $this->neededAt : null,
                batchId: $this->batchId ? (int) $this->batchId : null,
            );
        } catch (InvalidArgumentException $e) {
            $this->addError('photos', $e->getMessage());

            return;
        }

        session()->flash('status', $this->isCito
            ? "Pesanan cuci CITO {$order->order_number} terkirim. CSSD akan memprosesnya sebagai prioritas."
            : "Pesanan cuci {$order->order_number} terkirim. Kirim alatnya ke CSSD, lalu pantau progresnya di sini.");

        $this->redirectRoute('unit.orders.show', $order->id, navigate: true);
    }

    public function render()
    {
        return view('livewire.unit.order-create', [
            'batchOptions' => Batch::forUnit(auth()->user()->unit_id)->active()->get(),
        ]);
    }
}
