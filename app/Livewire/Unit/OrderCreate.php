<?php

namespace App\Livewire\Unit;

use App\Enums\ItemBatchStatus;
use App\Enums\PickupLocationRequestStatus;
use App\Enums\ScanInputMethod;
use App\Enums\UnitType;
use App\Exceptions\InvalidTransitionException;
use App\Models\DeliveryOrder;
use App\Models\ItemBatch;
use App\Models\ItemBatchUsageMark;
use App\Models\PickupLocation;
use App\Models\PickupLocationRequest;
use App\Services\DeliveryOrderService;
use App\Services\ItemBatchTransitionService;
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

    public function mount(): void
    {
        $this->authorize('create', DeliveryOrder::class);

        $this->courier_name = auth()->user()->name;
        $this->sent_at = now()->format('Y-m-d\TH:i');
    }

    /**
     * Alat "Per Barang" yang sedang dipegang unit ini — cuma satu status untuk
     * seluruh batch, jadi tinggal dibalik antara "Sedang Dipakai" <-> "Sudah
     * Diambil" (belum dipakai). Tidak bisa dihapus dari daftar, cuma diubah
     * statusnya.
     */
    public function toggleIndividualUsage(int $batchId, ItemBatchTransitionService $transitions): void
    {
        $batch = ItemBatch::where('id', $batchId)
            ->where('origin_unit_id', auth()->user()->unit_id)
            ->firstOrFail();

        $target = $batch->status === ItemBatchStatus::InUse
            ? ItemBatchStatus::PickedUp
            : ItemBatchStatus::InUse;

        try {
            $transitions->transition(
                $batch,
                $target,
                auth()->user(),
                ScanInputMethod::Manual,
                'Tandai pemakaian dari halaman Buat Order',
            );
        } catch (InvalidTransitionException $e) {
            $this->addError('heldBatches', $e->getMessage());
        }
    }

    /**
     * Alat "Per Set" — satu QR mewakili satu set utuh, tapi unit perlu menandai
     * isinya satu per satu (mis. gunting dipakai, needle holder tidak). Status
     * batch (Sedang Dipakai / Sudah Diambil) mengikuti otomatis: sekali salah
     * satu isinya ditandai dipakai, seluruh set otomatis jadi "Sedang Dipakai".
     */
    public function toggleSetItemUsage(int $batchId, int $itemId, ItemBatchTransitionService $transitions): void
    {
        $batch = ItemBatch::where('id', $batchId)
            ->where('origin_unit_id', auth()->user()->unit_id)
            ->firstOrFail();

        $mark = ItemBatchUsageMark::firstOrNew([
            'item_batch_id' => $batch->id,
            'item_id' => $itemId,
        ]);

        $mark->is_used = ! $mark->is_used;
        $mark->marked_by_user_id = auth()->id();
        $mark->marked_at = now();
        $mark->save();

        $anyUsed = ItemBatchUsageMark::where('item_batch_id', $batch->id)->where('is_used', true)->exists();
        $target = $anyUsed ? ItemBatchStatus::InUse : ItemBatchStatus::PickedUp;

        if ($batch->status !== $target) {
            try {
                $transitions->transition(
                    $batch,
                    $target,
                    auth()->user(),
                    ScanInputMethod::Manual,
                    'Tandai pemakaian isi set dari halaman Buat Order',
                );
            } catch (InvalidTransitionException $e) {
                $this->addError('heldBatches', $e->getMessage());
            }
        }
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
            // Alat yang sudah pernah diambil dari CSSD dan masih di tangan unit —
            // kosong wajar kalau memang belum pernah ada serah terima sebelumnya.
            'heldBatches' => $unit
                ? ItemBatch::where('origin_unit_id', $unit->id)
                    ->whereIn('status', [ItemBatchStatus::PickedUp, ItemBatchStatus::InUse])
                    ->with(['instrumentSet.items', 'item', 'usageMarks'])
                    ->orderByDesc('status_changed_at')
                    ->get()
                : collect(),
        ]);
    }
}
