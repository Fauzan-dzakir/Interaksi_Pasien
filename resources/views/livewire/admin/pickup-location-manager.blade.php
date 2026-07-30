<div>
    <x-page-header title="Lokasi Pengambilan"
                   subtitle="Daftar ruangan per unit yang muncul sebagai pilihan saat unit membuat order.">
        <x-slot:actions>
            <button wire:click="create" class="btn-primary">+ Tambah Lokasi</button>
        </x-slot:actions>
    </x-page-header>

    @if ($pendingRequests->isNotEmpty())
        <div class="mb-5 card border-amber-300 bg-amber-50/70">
            <div class="border-b border-amber-200 px-5 py-3">
                <h2 class="text-sm font-semibold text-amber-900">
                    {{ $pendingRequests->count() }} permintaan lokasi baru menunggu persetujuan
                </h2>
                <p class="mt-0.5 text-xs text-amber-800">Diajukan unit karena lokasi yang mereka butuhkan belum ada di daftar.</p>
            </div>
            <ul class="divide-y divide-amber-100">
                @foreach ($pendingRequests as $request)
                    <li wire:key="req-{{ $request->id }}" class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                        <div>
                            <div class="text-sm font-medium text-slate-900">{{ $request->name }}</div>
                            <div class="text-xs text-slate-500">
                                {{ $request->unit->name }} · diajukan oleh {{ $request->requestedBy->name }}
                                · {{ $request->created_at->format('d/m/Y H:i') }}
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button wire:click="rejectRequest({{ $request->id }})" class="btn-secondary !px-3 !py-1.5">Tolak</button>
                            <button wire:click="approveRequest({{ $request->id }})" class="btn-primary !px-3 !py-1.5">Setujui &amp; Tambahkan</button>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="flex flex-wrap gap-3 border-b border-slate-200 p-4">
            <select wire:model.live="filterUnit" class="field-input sm:max-w-xs">
                <option value="">Semua unit</option>
                @foreach ($unitOptions as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Nama Lokasi</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($locations as $location)
                        <tr wire:key="loc-{{ $location->id }}">
                            <td class="text-slate-600">{{ $location->unit->name }}</td>
                            <td class="font-medium text-slate-900">{{ $location->name }}</td>
                            <td><x-status-pill :active="$location->is_active" /></td>
                            <td>
                                <div class="flex justify-end gap-2">
                                    <button wire:click="edit({{ $location->id }})" class="btn-secondary !px-3 !py-1.5">Ubah</button>
                                    <button wire:click="toggleActive({{ $location->id }})"
                                            class="{{ $location->is_active ? 'btn-danger' : 'btn-secondary !px-3 !py-1.5' }}">
                                        {{ $location->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-slate-500">Belum ada data lokasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($locations->hasPages())
            <div class="border-t border-slate-200 p-4">{{ $locations->links() }}</div>
        @endif
    </div>

    <x-modal :show="$showForm" :title="$editingId ? 'Ubah Lokasi' : 'Tambah Lokasi'">
        <form wire:submit="save" id="location-form" class="space-y-4">
            <div>
                <label class="field-label" for="loc-unit">Unit</label>
                <select wire:model="unit_id" id="loc-unit" class="field-input">
                    <option value="">— Pilih unit —</option>
                    @foreach ($unitOptions as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
                @error('unit_id') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="field-label" for="loc-name">Nama Lokasi</label>
                <input wire:model="name" id="loc-name" type="text" class="field-input" placeholder="mis. Ruang Operasi 1">
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input wire:model="is_active" type="checkbox" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                Lokasi aktif
            </label>
        </form>

        <x-slot:footer>
            <button type="button" wire:click="$dispatch('close-modal')" class="btn-secondary">Batal</button>
            <button type="submit" form="location-form" class="btn-primary">Simpan</button>
        </x-slot:footer>
    </x-modal>
</div>
