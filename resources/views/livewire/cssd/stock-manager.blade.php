<div>
    <x-page-header title="Gudang Steril" subtitle="Stok alat tersedia dan pendaftaran aset baru ke inventaris.">
        <x-slot:actions>
            <a href="{{ route('cssd.barcodes') }}" wire:navigate class="btn-secondary">Cetak Barcode</a>
            <button wire:click="$set('showRegister', true)" class="btn-primary">+ Daftarkan Aset</button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-5 grid gap-5 lg:grid-cols-2">
        <div class="card p-5">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Set Tersedia</h2>
            <p class="mb-3 text-xs text-slate-500">Tiap set punya identitas sendiri karena isinya diperiksa satu per satu.</p>

            @forelse ($availableSets as $group)
                <div wire:key="as-{{ $group->first()->instrument_set_id }}"
                     class="mb-2 flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2">
                    <span class="truncate text-sm text-slate-700">{{ $group->first()->instrumentSet->name }}</span>
                    <span class="shrink-0 rounded-full bg-leaf-100 px-2 py-0.5 text-xs font-semibold text-leaf-800">
                        {{ $group->count() }} set
                    </span>
                </div>
            @empty
                <p class="text-sm text-slate-400">Belum ada set tersedia di gudang.</p>
            @endforelse
        </div>

        <div class="card p-5">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Alat Satuan Tersedia</h2>
            <p class="mb-3 text-xs text-slate-500">Diringkas per jenis, sesuai cara unit memesannya.</p>

            @forelse ($availableItems as $row)
                <div wire:key="ai-{{ $row->item_id }}"
                     class="mb-2 flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2">
                    <span class="truncate text-sm text-slate-700">{{ $row->item->name }}</span>
                    <span class="shrink-0 rounded-full bg-leaf-100 px-2 py-0.5 text-xs font-semibold text-leaf-800">
                        {{ $row->total }} pcs
                    </span>
                </div>
            @empty
                <p class="text-sm text-slate-400">Belum ada alat satuan tersedia di gudang.</p>
            @endforelse
        </div>
    </div>

    <div class="card">
        <div class="flex flex-wrap gap-3 border-b border-slate-200 p-4">
            <input wire:model.live.debounce.300ms="search" type="search"
                   placeholder="Cari barcode atau nama alat…" class="field-input sm:max-w-xs">

            <select wire:model.live="filterType" class="field-input sm:max-w-[12rem]">
                <option value="">Semua jenis</option>
                @foreach ($typeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterStatus" class="field-input sm:max-w-[16rem]">
                <option value="">Semua status</option>
                @foreach ($statusOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Barcode</th>
                        <th>Alat / Set</th>
                        <th>Jenis</th>
                        <th>Batch / Pemilik</th>
                        <th>Status</th>
                        <th>Siklus</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assets as $asset)
                        <tr wire:key="a-{{ $asset->id }}">
                            <td class="font-mono text-xs text-slate-600">{{ $asset->current_code }}</td>
                            <td>
                                <div class="font-medium text-slate-900">{{ $asset->displayName() }}</div>
                                @unless ($asset->is_complete)
                                    <span class="text-xs font-medium text-red-600">Set tidak lengkap</span>
                                @endunless
                            </td>
                            <td>{{ $asset->asset_type->label() }}</td>
                            <td>
                                @if ($asset->batch)
                                    <div class="text-slate-700">{{ $asset->batch->name }}</div>
                                    <div class="text-xs text-slate-400">{{ $asset->batch->unit->name }}</div>
                                @else
                                    <span class="text-xs text-slate-400">Stok bebas</span>
                                @endif
                            </td>
                            <td><x-state-pill :state="$asset->status" /></td>
                            <td class="text-xs text-slate-500">{{ $asset->cycle_count }}×</td>
                            <td class="text-right">
                                <a href="{{ route('assets.show', $asset->id) }}" wire:navigate
                                   class="btn-secondary !px-3 !py-1.5">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="7" title="Belum ada aset terdaftar"
                                       description="Klik “Daftarkan Aset” untuk memasukkan alat ke inventaris." />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($assets->hasPages())
            <div class="border-t border-slate-200 p-4">{{ $assets->links() }}</div>
        @endif
    </div>

    <x-modal :show="$showRegister" title="Daftarkan Aset Baru">
        <form wire:submit="register" id="register-form" class="space-y-4">
            <p class="rounded-lg bg-sky-50 px-3 py-2 text-sm text-sky-800 ring-1 ring-sky-200">
                Aset yang didaftarkan langsung berstatus tersedia di gudang steril dan
                mendapat barcode otomatis. Cetak labelnya di menu Buat Barcode.
            </p>

            <div>
                <label class="field-label" for="reg-type">Jenis Aset</label>
                <select wire:model.live="newType" id="reg-type" class="field-input">
                    <option value="set">Set Alat (satu barcode untuk satu set)</option>
                    <option value="item">Alat Satuan (satu barcode untuk satu alat)</option>
                </select>
                @error('newType') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            @if ($newType === 'set')
                <div>
                    <label class="field-label" for="reg-set">Jenis Set</label>
                    <select wire:model="instrument_set_id" id="reg-set" class="field-input">
                        <option value="">Pilih set</option>
                        @foreach ($setOptions as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->code }}, {{ $opt->name }}</option>
                        @endforeach
                    </select>
                    @error('instrument_set_id') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            @else
                <div>
                    <label class="field-label" for="reg-item">Jenis Alat</label>
                    <select wire:model="item_id" id="reg-item" class="field-input">
                        <option value="">Pilih alat</option>
                        @foreach ($itemOptions as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->code }}, {{ $opt->name }}</option>
                        @endforeach
                    </select>
                    @error('item_id') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            @endif

            <div>
                <label class="field-label" for="reg-qty">Jumlah Aset Fisik</label>
                <input wire:model="registerQuantity" id="reg-qty" type="number" min="1" max="50" class="field-input">
                <p class="mt-1 text-xs text-slate-400">Tiap aset mendapat barcode sendiri-sendiri.</p>
                @error('registerQuantity') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </form>

        <x-slot:footer>
            <button type="button" wire:click="$set('showRegister', false)" class="btn-secondary">Batal</button>
            <button type="submit" form="register-form" class="btn-primary">Daftarkan</button>
        </x-slot:footer>
    </x-modal>
</div>
