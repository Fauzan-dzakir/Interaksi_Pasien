<?php

namespace App\Livewire\Unit;

use App\Enums\OrderStatus;
use App\Models\Order;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class OrderList extends Component
{
    use WithPagination;

    #[Url(as: 'q', keep: false)]
    public string $search = '';

    #[Url(as: 'status', keep: false)]
    public string $filterStatus = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $orders = Order::query()
            ->forUnit(auth()->user()->unit_id)
            ->with(['requestedBy', 'batch', 'photos'])
            ->withCount('assets')
            ->when($this->search, fn ($q) => $q->where('order_number', 'like', "%{$this->search}%"))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate(12);

        return view('livewire.unit.order-list', [
            'orders' => $orders,
            'statusOptions' => OrderStatus::options(),
        ]);
    }
}
