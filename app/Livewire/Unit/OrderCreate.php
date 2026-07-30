<?php

namespace App\Livewire\Unit;

use App\Enums\ItemBatchStatus;
use App\Enums\PickupLocationRequestStatus;
use App\Enums\UnitType;
use App\Models\DeliveryOrder;
use App\Models\ItemBatch;
use App\Models\PickupLocation;
use App\Models\PickupLocationRequest;
use App\Services\DeliveryOrderService;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Form order pengiriman alat kotor ke CSSD.
 *
 * Sesuai alur yang disepakati, unit HANYA mengisi informasi dasar pengiriman.
 * Tidak ada form rincian alat di sini — pendataan isi kiriman adalah tugas dan
 * tanggung jawab CSSD saat menerima fisik barangnya.
 */
class OrderCreate extends Component
{
    use WithFileUploads;

    public string $courier_name = '';

    public string $sent_at = '';

    public int $box_count = 1;

    public bool $is_cito = false;

    public string $needed_at = '';

    public string $pickup_location_id = '';

    public string $new_location_name = '';

    public string $notes = '';

    public $photos = [];

    /**
     * Alat yang dideklarasikan unit sebagai isi kiriman ini — satu baris per alat,
     * sama pola dengan pendataan CSSD (baris + dropdown), tapi dropdown-nya HANYA
     * berisi alat yang SUDAH ditandai dipakai (lewat menu Pendataan Alat di Unit),
     * bukan seluruh katalog. Ini rujukan pembanding saja; pendataan resmi tetap
     * dilakukan CSSD secara independen saat menerima fisik barangnya.
     *
     * @var array<int, array{item_batch_id: string}>
     */
    public array $declaredLines = [];

    public function mount(): void
    {
        $this->authorize('create', DeliveryOrder::class);

        $this->courier_name = auth()->user()->name;
        $this->sent_at = now()->format('Y-m-d\TH:i');
        $this->addDeclaredLine();
    }

    public function addDeclaredLine(): void
    {
        $this->declaredLines[] = ['item_batch_id' => ''];
    }

    public function removeDeclaredLine(int $index): void
    {
        unset($this->declaredLines[$index]);
        $this->declaredLines = array_values($this->declaredLines);
    }

    /** Hapus satu foto dari daftar unggahan sebelum order dikirim (mis. foto ngeblur). */
    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function save(DeliveryOrderService $service): void
    {
        $this->authorize('create', DeliveryOrder::class);

        $unit = auth()->user()->unit;

        $data = $this->validate([
            'courier_name' => ['required', 'string', 'max:255'],
            'sent_at' => ['required', 'date'],
            'box_count' => ['required', 'integer', 'min:1', 'max:99'],
            'is_cito' => ['boolean'],
            'needed_at' => ['nullable', 'required_if:is_cito,true', 'date_format:H:i'],
            'pickup_location_id' => ['nullable', 'string'],
            'new_location_name' => ['nullable', 'required_if:pickup_location_id,other', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['image', 'max:5120'], // 5MB max per image
        ], [], [
            'courier_name' => 'nama petugas pengantar',
            'sent_at' => 'tanggal/jam kirim',
            'box_count' => 'jumlah box',
            'needed_at' => 'jam dibutuhkan',
            'pickup_location_id' => 'lokasi pengambilan',
            'new_location_name' => 'nama lokasi baru',
            'photos' => 'foto kondisi alat',
            'photos.*' => 'foto',
        ]);

        // Ambil ID yang benar-benar dipilih (baris kosong diabaikan), dedup baris ganda.
        $submittedIds = array_unique(array_filter(
            array_column($this->declaredLines, 'item_batch_id')
        ));

        if ($submittedIds === []) {
            $this->addError('declaredLines', 'Pilih minimal satu alat yang akan dikirim.');

            return;
        }

        // Jangan percaya ID kiriman klien mentah-mentah — pastikan hanya alat
        // milik unit ini yang benar-benar berstatus "sedang dipakai" yang boleh
        // dideklarasikan, supaya tidak bisa dipalsukan lewat DevTools/replay.
        $declaredBatchIds = ItemBatch::where('origin_unit_id', $unit->id)
            ->where('status', ItemBatchStatus::InUse)
            ->whereIn('id', $submittedIds)
            ->pluck('id')
            ->all();

        if ($declaredBatchIds === []) {
            $this->addError('declaredLines', 'Pilih minimal satu alat yang akan dikirim.');

            return;
        }

        // needed_at diisi sebagai jam saja (24 jam) — sistem menentukan sendiri
        // tanggalnya: hari ini kalau jamnya belum lewat, besok kalau sudah lewat,
        // supaya "dibutuhkan pada" selalu berada dalam 24 jam ke depan.
        if ($this->is_cito && $this->needed_at !== '') {
            $neededAt = now()->setTimeFromTimeString($this->needed_at);

            if ($neededAt->lessThanOrEqualTo(now())) {
                $neededAt->addDay();
            }

            $data['needed_at'] = $neededAt;
        } else {
            $data['needed_at'] = null;
        }

        if ($this->pickup_location_id === 'other') {
            $data['pickup_location'] = trim($this->new_location_name);

            PickupLocationRequest::create([
                'unit_id' => $unit->id,
                'name' => $data['pickup_location'],
                'requested_by_user_id' => auth()->id(),
                'status' => PickupLocationRequestStatus::Pending,
            ]);
        } elseif ($this->pickup_location_id !== '') {
            $data['pickup_location'] = PickupLocation::where('id', $this->pickup_location_id)
                ->where('unit_id', $unit->id)
                ->value('name');
        } else {
            $data['pickup_location'] = null;
        }

        unset($data['pickup_location_id'], $data['new_location_name']);

        if ($this->photos) {
            $paths = [];
            foreach ($this->photos as $photo) {
                $paths[] = $photo->store('orders', 'public');
            }
            $data['photos'] = json_encode($paths);
        }

        $order = $service->create(auth()->user(), $data);

        $order->declaredBatches()->attach($declaredBatchIds);

        $message = $this->pickup_location_id === 'other'
            ? "Order {$order->order_number} terkirim. Lokasi baru yang Anda tulis juga sudah diajukan ke Admin untuk ditambahkan ke daftar."
            : "Order {$order->order_number} terkirim. CSSD akan mendata isinya saat barang diterima.";

        session()->flash('status', $message);

        $this->redirectRoute('unit.orders.show', $order->id, navigate: true);
    }

    public function render()
    {
        $unit = auth()->user()->unit;

        return view('livewire.unit.order-create', [
            'locationOptions' => $unit
                ? PickupLocation::active()->forUnit($unit->id)->orderBy('name')->get()
                : collect(),
            'isIbs' => $unit?->type === UnitType::IbsOk,
            // Alat yang SUDAH ditandai dipakai (lewat menu "Pendataan Alat di Unit") —
            // ini alat kotor yang akan ikut dikirim balik lewat order ini. Tampilan saja,
            // tidak bisa diubah statusnya dari sini lagi (lihat UnitInventory untuk itu).
            'heldBatches' => $unit
                ? ItemBatch::where('origin_unit_id', $unit->id)
                    ->where('status', ItemBatchStatus::InUse)
                    ->with(['instrumentSet.items', 'item', 'usageMarks'])
                    ->orderByDesc('status_changed_at')
                    ->get()
                : collect(),
        ]);
    }
}
