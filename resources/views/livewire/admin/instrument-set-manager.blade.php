<div>
    <x-page-header title="Set Alat"
                   subtitle="Paket alat siap pakai (mis. Set Bedah Minor) yang didata CSSD lewat jalur “Per Set”.">
        <x-slot:actions>
            <button wire:click="create" class="btn-primary">+ Tambah Set</button>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <div class="border-b border-slate-200 p-4">
            <input wire:model.live.debounce.300ms="search" type="search"
                   placeholder="Cari nama atau kode set…"
                   class="field-input sm:max-w-xs">
        </div>

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Foto</th>
                        <th>Nama Set</th>
                        <th>Isi Set</th>
                        <th>Total Alat</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sets as $set)
                        <tr wire:key="set-{{ $set->id }}">
                            <td class="font-mono text-xs text-slate-500">{{ $set->code }}</td>
                            <td><x-item-thumb :photo="$set->photo_path" :name="$set->name" /></td>
                            <td>
                                <div class="font-medium text-slate-900">{{ $set->name }}</div>
                                @if ($set->description)
                                    <div class="text-xs text-slate-400">{{ $set->description }}</div>
                                @endif
                            </td>
                            <td class="min-w-[16rem]">
                                <x-set-contents :set="$set" />
                            </td>
                            <td>{{ $set->totalItemCount() }} pcs</td>
                            <td><x-status-pill :active="$set->is_active" /></td>
                            <td>
                                <div class="flex justify-end gap-2">
                                    <button wire:click="edit({{ $set->id }})" class="btn-secondary !px-3 !py-1.5">Ubah</button>
                                    <button wire:click="toggleActive({{ $set->id }})"
                                            class="{{ $set->is_active ? 'btn-danger' : 'btn-secondary !px-3 !py-1.5' }}">
                                        {{ $set->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">Belum ada data set alat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($sets->hasPages())
            <div class="border-t border-slate-200 p-4">{{ $sets->links() }}</div>
        @endif
    </div>

    <x-modal :show="$showForm" :title="$editingId ? 'Ubah Set Alat' : 'Tambah Set Alat'" max-width="max-w-2xl">
        <form wire:submit="save" id="set-form" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="field-label" for="set-code">Kode Set</label>
                    <input wire:model="code" id="set-code" type="text" class="field-input" placeholder="mis. SET-THT">
                    @error('code') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="field-label" for="set-name">Nama Set</label>
                    <input wire:model="name" id="set-name" type="text" class="field-input" placeholder="mis. Set Bedah THT">
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="field-label" for="set-photo">Foto Contoh Set</label>
                <div class="flex items-start gap-3">
                    <div class="h-24 w-24 shrink-0">
                        @if ($photo)
                            <img src="{{ $photo->temporaryUrl() }}" alt="Pratinjau"
                                 class="h-24 w-24 rounded-lg object-cover ring-1 ring-slate-200">
                        @else
                            <x-item-thumb :photo="$existingPhoto" :name="$name" class="!h-24 !w-24" />
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <input wire:model="photo" id="set-photo" type="file" accept="image/*" class="field-input !py-1.5">
                        <p class="mt-1 text-xs text-slate-400">Jadi acuan visual saat set dirakit ulang.</p>
                        @error('photo') <p class="field-error">{{ $message }}</p> @enderror

                        <div wire:loading wire:target="photo" class="mt-1 text-xs text-slate-500">Mengunggah foto...</div>

                        @if ($existingPhoto && ! $photo)
                            <button type="button" wire:click="removePhoto"
                                    class="mt-1.5 text-xs text-red-600 hover:text-red-700">Hapus foto</button>
                        @endif
                    </div>
                </div>
            </div>

            <div>
                <label class="field-label" for="set-desc">Keterangan</label>
                <textarea wire:model="description" id="set-desc" rows="2" class="field-input" placeholder="Opsional"></textarea>
                @error('description') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between">
                    <span class="field-label !mb-0">Isi Set</span>
                    <button type="button" wire:click="addRow" class="btn-secondary !px-3 !py-1">+ Tambah Baris</button>
                </div>

                @error('setItems') <p class="field-error mb-2">{{ $message }}</p> @enderror

                @if (empty($setItems))
                    <p class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-4 text-center text-sm text-slate-500">
                        Belum ada isi. Klik “Tambah Baris” untuk menentukan alat di dalam set ini.
                    </p>
                @else
                    <div class="space-y-2">
                        @foreach ($setItems as $index => $row)
                            <div wire:key="row-{{ $index }}" class="flex items-start gap-2">
                                <div class="flex-1">
                                    <select wire:model="setItems.{{ $index }}.item_id" class="field-input">
                                        <option value="">Pilih alat</option>
                                        @foreach ($itemOptions as $opt)
                                            <option value="{{ $opt->id }}">{{ $opt->code }}, {{ $opt->name }}</option>
                                        @endforeach
                                    </select>
                                    @error("setItems.{$index}.item_id") <p class="field-error">{{ $message }}</p> @enderror
                                </div>

                                <div class="w-24">
                                    <input wire:model="setItems.{{ $index }}.quantity" type="number" min="1"
                                           class="field-input" placeholder="Qty">
                                    @error("setItems.{$index}.quantity") <p class="field-error">{{ $message }}</p> @enderror
                                </div>

                                <button type="button" wire:click="removeRow({{ $index }})"
                                        class="mt-1 rounded-md p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                        title="Hapus baris">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input wire:model="is_active" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                Set aktif
            </label>
        </form>

        <x-slot:footer>
            <button type="button" wire:click="$dispatch('close-modal')" class="btn-secondary">Batal</button>
            <button type="submit" form="set-form" class="btn-primary">Simpan</button>
        </x-slot:footer>
    </x-modal>
</div>
