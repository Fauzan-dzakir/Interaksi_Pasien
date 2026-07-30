<?php

namespace App\Livewire\Cssd;

use App\Enums\DeliveryOrderStatus;
use App\Models\DeliveryOrder;
use App\Models\Unit;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Antrian order masuk dari seluruh unit. Order yang belum didata
 * ditampilkan paling atas karena itulah pekerjaan yang menahan alur.
 */
class OrderQueue extends Component
{
    use WithPagination;

    #[Url(as: 'q', keep: false)]
    public string $search = '';

    #[Url(as: 'status', keep: false)]
    public string $filterStatus = '';

    #[Url(as: 'unit', keep: false)]
    public string $filterUnit = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterUnit(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $orders = DeliveryOrder::query()
            ->with(['originUnit', 'submittedBy'])
            ->withCount(['lines', 'itemBatches'])
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('order_number', 'like', "%{$this->search}%")
                    ->orWhere('courier_name', 'like', "%{$this->search}%");
            }))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterUnit, fn ($q) => $q->where('origin_unit_id', $this->filterUnit))
            // Yang belum didata naik ke atas (itu antrian kerja yang menahan alur),
            // dan di dalamnya order CITO didahulukan agar cepat diproses. Sisanya
            // diurutkan dari yang paling lama dikirim supaya petugas tahu mana yang
            // harus dikerjakan lebih dulu (kiriman baru turun ke bawah antrian).
            ->orderByRaw("CASE WHEN status = ? THEN 0 ELSE 1 END", [DeliveryOrderStatus::PendingCssdIntake->value])
            ->orderByDesc('is_cito')
            ->oldest('sent_at')
            ->paginate(15);

        return view('livewire.cssd.order-queue', [
            'orders' => $orders,
            'statusOptions' => DeliveryOrderStatus::options(),
            'unitOptions' => Unit::active()->orderBy('name')->get(['id', 'name']),
            'pendingCount' => DeliveryOrder::awaitingIntake()->count(),
        ]);
    }
}
