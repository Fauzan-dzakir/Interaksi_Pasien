<?php

namespace App\Livewire\Unit;

use App\Enums\DeliveryOrderStatus;
use App\Models\DeliveryOrder;
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $orders = DeliveryOrder::query()
            ->forUnit(auth()->user()->unit_id)
            ->with(['submittedBy', 'intakeRecordedBy'])
            ->withCount(['lines', 'itemBatches'])
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('order_number', 'like', "%{$this->search}%")
                    ->orWhere('courier_name', 'like', "%{$this->search}%");
            }))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->latest('sent_at')
            ->paginate(12);

        return view('livewire.unit.order-list', [
            'orders' => $orders,
            'statusOptions' => DeliveryOrderStatus::options(),
        ]);
    }
}
