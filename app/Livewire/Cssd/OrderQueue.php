<?php

namespace App\Livewire\Cssd;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Unit;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class OrderQueue extends Component
{
    use WithPagination;

    #[Url(as: 'q', keep: false)]
    public string $search = '';

    #[Url(as: 'status', keep: false)]
    public string $filterStatus = '';

    #[Url(as: 'unit', keep: false)]
    public string $filterUnit = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $orders = Order::query()
            ->with(['unit', 'requestedBy', 'batch', 'photos'])
            ->withCount('assets')
            ->when($this->search, fn ($q) => $q->where('order_number', 'like', "%{$this->search}%"))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterUnit, fn ($q) => $q->where('unit_id', $this->filterUnit))
            // Urutan antrian: CITO paling atas (pasien gawat), lalu yang belum disiapkan.
            ->orderByDesc('is_cito')
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [OrderStatus::Pending->value])
            ->latest()
            ->paginate(15);

        return view('livewire.cssd.order-queue', [
            'orders' => $orders,
            'statusOptions' => OrderStatus::options(),
            'unitOptions' => Unit::active()->orderBy('name')->get(['id', 'name']),
            'pendingCount' => Order::awaitingPreparation()->count(),
            'citoCount' => Order::open()->where('is_cito', true)->count(),
        ]);
    }
}
