<?php

namespace App\Livewire\Unit;

use App\Enums\PickupLocationRequestStatus;
use App\Enums\UnitType;
use App\Models\DeliveryOrder;
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

    public function mount(): void
    {
        $this->authorize('create', DeliveryOrder::class);

        $this->courier_name = auth()->user()->name;
        $this->sent_at = now()->format('Y-m-d\TH:i');
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
        ]);
    }
}
