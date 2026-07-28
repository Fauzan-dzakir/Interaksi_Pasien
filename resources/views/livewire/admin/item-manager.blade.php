<div>
    <x-page-header title="Katalog Alat"
                   subtitle="Jenis alat medis yang diproses CSSD, beserta kategori sensitivitas bahannya.">
        <x-slot:actions>
            <button wire:click="create" class="btn-primary">+ Tambah Alat</button>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <div class="flex flex-wrap gap-3 border-b border-slate-200 p-4">
            <input wire:model.live.debounce.300ms="search" type="search"
                   placeholder="Cari nama, kode, atau kategori…"
                   class="field-input sm:max-w-xs">

            <select wire:model.live="filterSensitivity" class="field-input sm:max-w-xs">
                <option value="">Semua sensitivitas</option>
                @foreach ($sensitivityOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Alat</th>
                        <th>Kategori</th>
                        <th>Sensitivitas Bahan</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr wire:key="item-{{ $item->id }}">
                            <td class="font-mono text-xs text-slate-500">{{ $item->code }}</td>
                            <td class="font-medium text-slate-900">{{ $item->name }}</td>
                            <td>{{ $item->category ?? '—' }}</td>
                            <td>
                                <div class="text-slate-700">{{ $item->material_sensitivity->label() }}</div>
                                <div class="text-xs text-slate-400">{{ $item->material_sensitivity->cleaningMethod() }}</div>
                            </td>
                            <td><x-status-pill :active="$item->is_active" /></td>
                            <td>
                                <div class="flex justify-end gap-2">
                                    <button wire:click="edit({{ $item->id }})" class="btn-secondary !px-3 !py-1.5">Ubah</button>
                                    <button wire:click="toggleActive({{ $item->id }})"
                                            class="{{ $item->is_active ? 'btn-danger' : 'btn-secondary !px-3 !py-1.5' }}">
                                        {{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-slate-500">Belum ada data alat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($items->hasPages())
            <div class="border-t border-slate-200 p-4">{{ $items->links() }}</div>
        @endif
    </div>

    <x-modal :show="$showForm" :title="$editingId ? 'Ubah Alat' : 'Tambah Alat'">
        <form wire:submit="save" id="item-form" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="field-label" for="item-code">Kode Alat</label>
                    <input wire:model="code" id="item-code" type="text" class="field-input" placeholder="mis. ALT-017">
                    @error('code') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="field-label" for="item-category">Kategori</label>
                    <input wire:model="category" id="item-category" type="text" class="field-input" placeholder="mis. Klem">
                    @error('category') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="field-label" for="item-name">Nama Alat</label>
                <input wire:model="name" id="item-name" type="text" class="field-input" placeholder="mis. Klem Pean Lurus 16 cm">
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="field-label" for="item-sens">Sensitivitas Bahan</label>
                <select wire:model.live="material_sensitivity" id="item-sens" class="field-input">
                    @foreach ($sensitivityOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('material_sensitivity') <p class="field-error">{{ $message }}</p> @enderror

                @if ($selectedSensitivity)
                    <div class="mt-2 rounded-lg bg-slate-50 p-3 text-xs text-slate-600 ring-1 ring-slate-200">
                        <div><span class="font-medium text-slate-700">Pembersihan:</span> {{ $selectedSensitivity->cleaningMethod() }}</div>
                        <div class="mt-0.5"><span class="font-medium text-slate-700">Pengeringan:</span> {{ $selectedSensitivity->dryingMethod() }}</div>
                        <div class="mt-1.5 text-slate-400">Panduan SOP — sistem tidak mengunci metode, keputusan tetap di petugas CSSD.</div>
                    </div>
                @endif
            </div>

            <div>
                <label class="field-label" for="item-notes">Catatan</label>
                <textarea wire:model="notes" id="item-notes" rows="2" class="field-input" placeholder="Opsional"></textarea>
                @error('notes') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input wire:model="is_active" type="checkbox" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                Alat aktif
            </label>
        </form>

        <x-slot:footer>
            <button type="button" wire:click="$dispatch('close-modal')" class="btn-secondary">Batal</button>
            <button type="submit" form="item-form" class="btn-primary">Simpan</button>
        </x-slot:footer>
    </x-modal>
</div>
