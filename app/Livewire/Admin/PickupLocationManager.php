<?php

namespace App\Livewire\Admin;

use App\Enums\PickupLocationRequestStatus;
use App\Models\PickupLocation;
use App\Models\PickupLocationRequest;
use App\Models\Unit;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Master data ruangan/lokasi per unit — dipakai sebagai pilihan dropdown saat
 * unit membuat order pengiriman, supaya lokasi asal alat tercatat konsisten.
 *
 * Termasuk antrean persetujuan saat unit mengajukan lokasi baru yang belum
 * ada di daftar mereka.
 */
class PickupLocationManager extends Component
{
    use WithPagination;

    #[Url(as: 'unit', keep: false)]
    public string $filterUnit = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $unit_id = '';

    public string $name = '';

    public bool $is_active = true;

    public function updatedFilterUnit(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $location = PickupLocation::findOrFail($id);

        $this->editingId = $location->id;
        $this->unit_id = (string) $location->unit_id;
        $this->name = $location->name;
        $this->is_active = $location->is_active;

        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'unit_id' => ['required', 'exists:units,id'],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('pickup_locations', 'name')->where('unit_id', $this->unit_id)->ignore($this->editingId),
            ],
            'is_active' => ['boolean'],
        ], [], [
            'unit_id' => 'unit',
            'name' => 'nama lokasi',
        ]);

        PickupLocation::updateOrCreate(['id' => $this->editingId], $data);

        $this->showForm = false;
        $this->resetForm();

        session()->flash('status', 'Lokasi berhasil disimpan.');
    }

    /** Master data tidak dihapus permanen — hanya dinonaktifkan. */
    public function toggleActive(int $id): void
    {
        $location = PickupLocation::findOrFail($id);
        $location->update(['is_active' => ! $location->is_active]);

        session()->flash('status', "Lokasi \"{$location->name}\" kini ".($location->is_active ? 'aktif' : 'nonaktif').'.');
    }

    /** Menyetujui permintaan unit — otomatis membuat/mengaktifkan lokasi di master data. */
    public function approveRequest(int $id): void
    {
        $request = PickupLocationRequest::findOrFail($id);

        PickupLocation::updateOrCreate(
            ['unit_id' => $request->unit_id, 'name' => $request->name],
            ['is_active' => true],
        );

        $request->update([
            'status' => PickupLocationRequestStatus::Approved,
            'reviewed_by_user_id' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        session()->flash('status', "Lokasi \"{$request->name}\" ditambahkan ke master data.");
    }

    public function rejectRequest(int $id): void
    {
        $request = PickupLocationRequest::findOrFail($id);

        $request->update([
            'status' => PickupLocationRequestStatus::Rejected,
            'reviewed_by_user_id' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        session()->flash('status', "Permintaan lokasi \"{$request->name}\" ditolak.");
    }

    #[On('close-modal')]
    public function closeModal(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'unit_id', 'name']);
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $locations = PickupLocation::query()
            ->with('unit')
            ->when($this->filterUnit, fn ($q) => $q->where('unit_id', $this->filterUnit))
            ->orderBy('unit_id')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.pickup-location-manager', [
            'locations' => $locations,
            'unitOptions' => Unit::active()->orderBy('name')->get(['id', 'name']),
            'pendingRequests' => PickupLocationRequest::query()
                ->pending()
                ->with(['unit', 'requestedBy'])
                ->latest()
                ->get(),
        ]);
    }
}
