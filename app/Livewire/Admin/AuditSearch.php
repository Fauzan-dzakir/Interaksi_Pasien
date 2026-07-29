<?php

namespace App\Livewire\Admin;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\Unit;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Penelusuran audit, menjawab pertanyaan yang selama ini tidak terjawab:
 * "alat ini terakhir tercatat di mana, jam berapa, dan oleh siapa?"
 *
 * Pencarian ikut menjangkau barcode LAMA, karena barcode alat berganti tiap
 * siklus sementara komplain biasanya menyebut kode yang tertempel dulu.
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
        $assets = Asset::query()
            ->with(['instrumentSet', 'item', 'batch.unit'])
            ->when($this->search, function ($q) {
                $term = trim($this->search);
                $upper = strtoupper($term);

                $q->where(function ($sub) use ($term, $upper) {
                    $sub->where('current_code', 'like', "%{$upper}%")
                        // Barcode lama ikut dicari, inilah kunci penelusuran lintas siklus.
                        ->orWhereHas('codes', fn ($c) => $c->where('code', 'like', "%{$upper}%"))
                        ->orWhereHas('instrumentSet', fn ($s) => $s->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"))
                        ->orWhereHas('item', fn ($s) => $s->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"));
                });
            })
            ->when($this->filterUnit, fn ($q) => $q->whereHas('batch', fn ($b) => $b->where('unit_id', $this->filterUnit)))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->from, fn ($q) => $q->whereDate('status_changed_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('status_changed_at', '<=', $this->to))
            ->orderByDesc('status_changed_at')
            ->paginate(20);

        return view('livewire.admin.audit-search', [
            'assets' => $assets,
            'unitOptions' => Unit::orderBy('name')->get(['id', 'name']),
            'statusOptions' => AssetStatus::options(),
            'lostCount' => Asset::where('status', AssetStatus::Lost)->count(),
            'overrideCount' => AssetEvent::where('is_admin_override', true)->count(),
            'recentOverrides' => AssetEvent::query()
                ->where('is_admin_override', true)
                ->with(['actor', 'asset'])
                ->latest('occurred_at')
                ->limit(8)
                ->get(),
        ]);
    }
}
