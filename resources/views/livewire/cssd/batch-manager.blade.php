<div>
    <x-page-header title="Kelola Batch"
                   subtitle="Kumpulan alat langganan tiap unit yang menetap lintas siklus.">
        <x-slot:actions>
            <button wire:click="create" class="btn-primary">+ Buat Batch</button>
        </x-slot:actions>
    </x-page-header>

    {{-- Penjelasan singkat, karena istilah batch mudah tertukar dengan pesanan. --}}
    <div class="card mb-5 border-brand-200 bg-brand-50/50 p-5">
        <h2 class="text-sm font-semibold text-brand-900">Apa itu batch?</h2>
        <div class="mt-2 grid gap-4 text-sm text-brand-800 sm:grid-cols-3">
            <div>
                <div class="font-medium">1. Unit memesan</div>
                <p class="mt-0.5 text-xs">Alat yang dipesan lewat batch akan menempel ke batch unit tersebut.</p>
            </div>
            <div>
                <div class="font-medium">2. Alat kembali dicuci</div>
                <p class="mt-0.5 text-xs">Selama siklus pencucian dan sterilisasi, alat tetap tercatat milik batch itu.</p>
            </div>
            <div>
                <div class="font-medium">3. Dipesan ulang atau dilepas</div>
                <p class="mt-0.5 text-xs">Kalau unit tidak memesan ulang, alat otomatis keluar ke stok bebas.</p>
            </div>
        </div>
    </div>

    <div class="mb-5 grid gap-4 sm:grid-cols-3">
        <div class="card p-5">
            <div class="text-sm text-slate-500">Batch Aktif</div>
            <div class="mt-1 text-3xl font-semibold text-slate-900">{{ $activeCount }}</div>
        </div>
        <div class="card p-5">
            <div class="text-sm text-slate-500">Alat Terikat Batch</div>
            <div class="mt-1 text-3xl font-semibold text-slate-900">{{ $inBatchCount }}</div>
        </div>
        <div class="card p-5">
            <div class="text-sm text-slate-500">Alat Stok Bebas</div>
            <div class="mt-1 text-3xl font-semibold text-leaf-700">{{ $freeStockCount }}</div>
        </div>
    </div>

    <div class="card mb-5 flex flex-wrap gap-3 p-4">
        <input wire:model.live.debounce.300ms="search" type="search"
               placeholder="Cari nama atau kode batch..." class="field-input sm:max-w-xs">

        <select wire:model.live="filterUnit" class="field-input sm:max-w-xs">
            <option value="">Semua unit</option>
            @foreach ($unitOptions as $unit)
                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="space-y-4">
        @forelse ($batches as $batch)
            @php
                $sets = $batch->assets->where('asset_type', \App\Enums\AssetType::Set);
                $items = $batch->assets->where('asset_type', \App\Enums\AssetType::Item);
            @endphp

            <div wire:key="batch-{{ $batch->id }}" class="card overflow-hidden">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 bg-slate-50/70 px-5 py-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-semibold text-slate-900">{{ $batch->name }}</h2>
                            <x-status-pill :active="$batch->is_active" />
                        </div>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $batch->unit->name }}
                            <span class="font-mono text-xs text-slate-400">({{ $batch->code }})</span>
                        </p>
                        @if ($batch->notes)
                            <p class="mt-1 text-xs text-slate-400">{{ $batch->notes }}</p>
                        @endif
                    </div>

                    <div class="flex shrink-0 items-center gap-4">
                        <div class="text-right">
                            <div class="text-2xl font-semibold text-slate-900">{{ $batch->assets_count }}</div>
                            <div class="text-xs text-slate-500">{{ $batch->summary() }}</div>
                        </div>
                        <button wire:click="edit({{ $batch->id }})" class="btn-secondary !px-3 !py-1.5">Ubah</button>
                    </div>
                </div>

                @if ($batch->assets->isNotEmpty())
                    <div class="grid gap-px bg-slate-200 sm:grid-cols-2">
                        <div class="bg-white p-4">
                            <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Set Alat ({{ $sets->count() }})
                            </h3>
                            @forelse ($sets as $asset)
                                <div wire:key="bs-{{ $asset->id }}"
                                     class="mb-1.5 flex items-center gap-2 rounded-lg border border-slate-200 p-2">
                                    <x-item-thumb :photo="$asset->instrumentSet?->photo_path"
                                                  :name="$asset->displayName()" size="sm" />
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-sm font-medium text-slate-800">{{ $asset->displayName() }}</div>
                                        <div class="font-mono text-xs text-slate-400">{{ $asset->current_code }}</div>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-1.5">
                                        <x-state-pill :state="$asset->status" />
                                        <button wire:click="releaseAsset({{ $asset->id }})"
                                                wire:confirm="Lepas {{ $asset->current_code }} ke stok bebas?"
                                                class="rounded p-1 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                                title="Lepas ke stok bebas">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <p class="py-3 text-center text-xs text-slate-400">Tidak ada set.</p>
                            @endforelse
                        </div>

                        <div class="bg-white p-4">
                            <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Alat Satuan ({{ $items->count() }})
                            </h3>
                            @forelse ($items as $asset)
                                <div wire:key="bi-{{ $asset->id }}"
                                     class="mb-1.5 flex items-center gap-2 rounded-lg border border-slate-200 p-2">
                                    <x-item-thumb :photo="$asset->item?->photo_path"
                                                  :name="$asset->displayName()" size="sm" />
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-sm font-medium text-slate-800">{{ $asset->displayName() }}</div>
                                        <div class="font-mono text-xs text-slate-400">{{ $asset->current_code }}</div>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-1.5">
                                        <x-state-pill :state="$asset->status" />
                                        <button wire:click="releaseAsset({{ $asset->id }})"
                                                wire:confirm="Lepas {{ $asset->current_code }} ke stok bebas?"
                                                class="rounded p-1 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                                title="Lepas ke stok bebas">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <p class="py-3 text-center text-xs text-slate-400">Tidak ada alat satuan.</p>
                            @endforelse
                        </div>
                    </div>
                @else
                    <p class="px-5 py-8 text-center text-sm text-slate-400">
                        Batch masih kosong. Alat masuk ke batch ini saat unit memesannya lewat menu Pesan Ulang Batch.
                    </p>
                @endif
            </div>
        @empty
            <x-empty-state title="Belum ada batch"
                           description="Buat batch untuk unit yang memakai alat yang sama berulang kali." />
        @endforelse
    </div>

    @if ($batches->hasPages())
        <div class="mt-4">{{ $batches->links() }}</div>
    @endif

    <x-modal :show="$showForm" :title="$editingId ? 'Ubah Batch' : 'Buat Batch'">
        <form wire:submit="save" id="batch-form" class="space-y-4">
            <div>
                <label class="field-label" for="b-name">Nama Batch</label>
                <input wire:model="name" id="b-name" type="text" class="field-input" placeholder="mis. IBS OK 1">
                <p class="mt-1 text-xs text-slate-400">Beri nama yang mudah dikenali unit, biasanya nama ruangan.</p>
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="field-label" for="b-unit">Unit Pemilik</label>
                <select wire:model="unit_id" id="b-unit" class="field-input" @disabled($editingId !== null)>
                    <option value="">Pilih unit</option>
                    @foreach ($unitOptions as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
                @error('unit_id') <p class="field-error">{{ $message }}</p> @enderror
                @if ($editingId)
                    <p class="mt-1 text-xs text-slate-400">Unit pemilik tidak bisa diubah agar riwayat batch tetap konsisten.</p>
                @endif
            </div>

            <div>
                <label class="field-label" for="b-notes">Catatan</label>
                <textarea wire:model="notes" id="b-notes" rows="2" class="field-input" placeholder="Opsional"></textarea>
                @error('notes') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input wire:model="is_active" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                Batch aktif
            </label>
        </form>

        <x-slot:footer>
            <button type="button" wire:click="$dispatch('close-modal')" class="btn-secondary">Batal</button>
            <button type="submit" form="batch-form" class="btn-primary">Simpan</button>
        </x-slot:footer>
    </x-modal>
</div>
