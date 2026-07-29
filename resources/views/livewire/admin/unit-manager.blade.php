<div>
    <x-page-header title="Unit / Ruangan" subtitle="Daftar unit pengirim alat ke CSSD, termasuk IBS dan CSSD sendiri.">
        <x-slot:actions>
            <button wire:click="create" class="btn-primary">+ Tambah Unit</button>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <div class="border-b border-slate-200 p-4">
            <input wire:model.live.debounce.300ms="search" type="search"
                   placeholder="Cari nama atau kode unit…"
                   class="field-input sm:max-w-xs">
        </div>

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Unit</th>
                        <th>Jenis</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($units as $unit)
                        <tr wire:key="unit-{{ $unit->id }}">
                            <td class="font-mono text-xs text-slate-500">{{ $unit->code }}</td>
                            <td class="font-medium text-slate-900">{{ $unit->name }}</td>
                            <td>{{ $unit->type->label() }}</td>
                            <td><x-status-pill :active="$unit->is_active" /></td>
                            <td>
                                <div class="flex justify-end gap-2">
                                    <button wire:click="edit({{ $unit->id }})" class="btn-secondary !px-3 !py-1.5">Ubah</button>
                                    <button wire:click="toggleActive({{ $unit->id }})"
                                            class="{{ $unit->is_active ? 'btn-danger' : 'btn-secondary !px-3 !py-1.5' }}">
                                        {{ $unit->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500">Belum ada data unit.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($units->hasPages())
            <div class="border-t border-slate-200 p-4">{{ $units->links() }}</div>
        @endif
    </div>

    <x-modal :show="$showForm" :title="$editingId ? 'Ubah Unit' : 'Tambah Unit'">
        <form wire:submit="save" id="unit-form" class="space-y-4">
            <div>
                <label class="field-label" for="unit-code">Kode Unit</label>
                <input wire:model="code" id="unit-code" type="text" class="field-input" placeholder="mis. IBS-01">
                @error('code') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="field-label" for="unit-name">Nama Unit</label>
                <input wire:model="name" id="unit-name" type="text" class="field-input" placeholder="mis. Instalasi Bedah Sentral">
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="field-label" for="unit-type">Jenis Unit</label>
                <select wire:model="type" id="unit-type" class="field-input">
                    @foreach ($typeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('type') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input wire:model="is_active" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                Unit aktif
            </label>
        </form>

        <x-slot:footer>
            <button type="button" wire:click="$dispatch('close-modal')" class="btn-secondary">Batal</button>
            <button type="submit" form="unit-form" class="btn-primary">Simpan</button>
        </x-slot:footer>
    </x-modal>
</div>
