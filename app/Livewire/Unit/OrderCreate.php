<?php

namespace App\Livewire\Unit;

use App\Models\DeliveryOrder;
use App\Services\DeliveryOrderService;
use Livewire\Component;

/**
 * Form order pengiriman alat kotor ke CSSD.
 *
 * Sesuai alur yang disepakati, unit HANYA mengisi informasi dasar pengiriman.
 * Tidak ada form rincian alat di sini — pendataan isi kiriman adalah tugas dan
 * tanggung jawab CSSD saat menerima fisik barangnya.
 */
class OrderCreate extends Component
{
    public string $courier_name = '';

    public string $sent_at = '';

    public int $box_count = 1;

    public string $notes = '';

    public function mount(): void
    {
        $this->authorize('create', DeliveryOrder::class);

        $this->courier_name = auth()->user()->name;
        $this->sent_at = now()->format('Y-m-d\TH:i');
    }

    public function save(DeliveryOrderService $service): void
    {
        $this->authorize('create', DeliveryOrder::class);

        $data = $this->validate([
            'courier_name' => ['required', 'string', 'max:255'],
            'sent_at' => ['required', 'date'],
            'box_count' => ['required', 'integer', 'min:1', 'max:99'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'courier_name' => 'nama petugas pengantar',
            'sent_at' => 'tanggal/jam kirim',
            'box_count' => 'jumlah box',
        ]);

        $order = $service->create(auth()->user(), $data);

        session()->flash('status', "Order {$order->order_number} terkirim. CSSD akan mendata isinya saat barang diterima.");

        $this->redirectRoute('unit.orders.show', $order->id, navigate: true);
    }

    public function render()
    {
        return view('livewire.unit.order-create');
    }
}
