<?php

namespace App\Livewire\Admin;

use App\Enums\ItemBatchStatus;
use App\Models\ItemBatch;
use App\Models\ItemBatchEvent;
use App\Models\Unit;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Penelusuran audit — menjawab pertanyaan yang selama ini tidak terjawab:
 * "alat ini terakhir tercatat di mana, jam berapa, dan oleh siapa?"
 */
class AuditSearch extends Component
{
    use WithPagination;

    #[Url(as: 'q', keep: false)]
    public string $search = '';

    #[Url(as: 'unit', keep: false)]
    public string $filterUnit = '';

    #[Url(as: 'status', keep: false)]
    public string $filterStatus = '';

    #[Url(as: 'dari', keep: false)]
    public string $from = '';

    #[Url(as: 'sampai', keep: false)]
    public string $to = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterUnit', 'filterStatus', 'from', 'to']);
        $this->resetPage();
    }

    public function render()
    {
        $batches = ItemBatch::query()
            ->with(['instrumentSet', 'item', 'originUnit', 'currentDeliveryOrder'])
            ->when($this->search, function ($q) {
                $term = trim($this->search);

                $q->where(function ($sub) use ($term) {
                    $sub->where('public_code', 'like', '%'.strtoupper($term).'%')
                        ->orWhereHas('instrumentSet', fn ($s) => $s->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"))
                        ->orWhereHas('item', fn ($s) => $s->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"))
                        ->orWhereHas('currentDeliveryOrder', fn ($s) => $s->where('order_number', 'like', "%{$term}%"));
                });
            })
            ->when($this->filterUnit, fn ($q) => $q->where('origin_unit_id', $this->filterUnit))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->from, fn ($q) => $q->whereDate('status_changed_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('status_changed_at', '<=', $this->to))
            ->orderByDesc('status_changed_at')
            ->paginate(20);

        return view('livewire.admin.audit-search', [
            'batches' => $batches,
            'unitOptions' => Unit::orderBy('name')->get(['id', 'name']),
            'statusOptions' => ItemBatchStatus::options(),
            'lostCount' => ItemBatch::where('status', ItemBatchStatus::Lost)->count(),
            'overrideCount' => ItemBatchEvent::where('is_admin_override', true)->count(),
            'recentOverrides' => ItemBatchEvent::query()
                ->where('is_admin_override', true)
                ->with(['actor', 'itemBatch'])
                ->latest('occurred_at')
                ->limit(8)
                ->get(),
        ]);
    }
}
