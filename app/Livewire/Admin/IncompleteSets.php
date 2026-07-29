<?php

namespace App\Livewire\Admin;

use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\SetContentCheck;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Laporan set yang isinya tidak lengkap.
 *
 * Sistem sengaja TIDAK menahan set tidak lengkap agar pekerjaan CSSD tidak
 * terhenti; sebagai gantinya semuanya dikumpulkan di sini supaya Admin bisa
 * menindaklanjuti, inilah yang dulu tidak terlihat sama sekali di sistem kertas.
 */
class IncompleteSets extends Component
{
    use WithPagination;

    #[Url(as: 'q', keep: false)]
    public string $search = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $sets = Asset::query()
            ->where('asset_type', AssetType::Set)
            ->where('is_complete', false)
            ->with(['instrumentSet', 'batch.unit'])
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('current_code', 'like', '%'.strtoupper($this->search).'%')
                    ->orWhereHas('instrumentSet', fn ($s) => $s->where('name', 'like', "%{$this->search}%"));
            }))
            ->orderByDesc('status_changed_at')
            ->paginate(15);

        // Alat mana yang paling sering hilang, jadi bahan evaluasi SOP, bukan sekadar daftar.
        $topMissing = SetContentCheck::query()
            ->missing()
            ->selectRaw('item_id, count(*) as total')
            ->groupBy('item_id')
            ->orderByDesc('total')
            ->with('item')
            ->limit(10)
            ->get();

        return view('livewire.admin.incomplete-sets', [
            'sets' => $sets,
            'topMissing' => $topMissing,
            'totalMissingRecords' => SetContentCheck::missing()->count(),
            'recentMissing' => SetContentCheck::query()
                ->missing()
                ->with(['item', 'asset.instrumentSet', 'checkedBy'])
                ->latest('checked_at')
                ->limit(12)
                ->get(),
        ]);
    }
}
