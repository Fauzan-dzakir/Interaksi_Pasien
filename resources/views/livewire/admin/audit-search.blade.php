<div>
    <x-page-header title="Telusur Alat"
                   subtitle="Cari posisi terakhir alat beserta bukti waktu dan petugasnya." />

    <div class="mb-5 grid gap-4 sm:grid-cols-3">
        <div class="card p-5">
            <div class="text-sm text-slate-500">Alat Ditandai Hilang</div>
            <div class="mt-1 text-3xl font-semibold {{ $lostCount > 0 ? 'text-red-600' : 'text-slate-900' }}">
                {{ $lostCount }}
            </div>
        </div>
        <div class="card p-5">
            <div class="text-sm text-slate-500">Koreksi Admin Tercatat</div>
            <div class="mt-1 text-3xl font-semibold text-slate-900">{{ $overrideCount }}</div>
        </div>
        <div class="card p-5">
            <div class="text-sm text-slate-500">Hasil Pencarian</div>
            <div class="mt-1 text-3xl font-semibold text-slate-900">{{ $batches->total() }}</div>
        </div>
    </div>

    <div class="card mb-5">
        <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <label class="field-label" for="q">Kode label / nama alat / nomor order</label>
                <input wire:model.live.debounce.300ms="search" id="q" type="search"
                       class="field-input" placeholder="mis. CSSD-7F3K9M2P atau Set Bedah Minor">
            </div>

            <div>
                <label class="field-label" for="f-unit">Unit</label>
                <select wire:model.live="filterUnit" id="f-unit" class="field-input">
                    <option value="">Semua unit</option>
                    @foreach ($unitOptions as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="field-label" for="f-from">Dari tanggal</label>
                <input wire:model.live="from" id="f-from" type="date" class="field-input">
            </div>

            <div>
                <label class="field-label" for="f-to">Sampai tanggal</label>
                <input wire:model.live="to" id="f-to" type="date" class="field-input">
            </div>
        </div>

        <div class="flex flex-wrap items-end gap-3 border-t border-slate-200 p-4">
            <div class="flex-1 sm:max-w-xs">
                <label class="field-label" for="f-status">Status</label>
                <select wire:model.live="filterStatus" id="f-status" class="field-input">
                    <option value="">Semua status</option>
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button wire:click="resetFilters" class="btn-secondary">Reset Filter</button>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th>Kode Label</th>
                            <th>Alat / Set</th>
                            <th>Unit</th>
                            <th>Status Terakhir</th>
                            <th>Waktu</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($batches as $batch)
                            <tr wire:key="ab-{{ $batch->id }}">
                                <td class="font-mono text-xs text-slate-600">{{ $batch->public_code }}</td>
                                <td>
                                    <div class="font-medium text-slate-900">{{ $batch->displayName() }}</div>
                                    <div class="text-xs text-slate-400">{{ $batch->displayQuantity() }}</div>
                                </td>
                                <td>{{ $batch->originUnit->name }}</td>
                                <td><x-state-pill :state="$batch->status" /></td>
                                <td class="text-xs text-slate-500">{{ $batch->status_changed_at->format('d/m/Y H:i') }}</td>
                                <td class="text-right">
                                    <a href="{{ route('batches.show', $batch->id) }}" wire:navigate
                                       class="btn-secondary !px-3 !py-1.5">Riwayat</a>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="6" title="Tidak ada alat yang cocok"
                                           description="Coba ubah kata kunci atau reset filter." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($batches->hasPages())
                <div class="border-t border-slate-200 p-4">{{ $batches->links() }}</div>
            @endif
        </div>

        <div class="card p-5">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Koreksi Admin Terakhir</h2>
            <p class="mb-3 text-xs text-slate-500">
                Setiap koreksi yang melompati alur normal tercatat di sini.
            </p>

            @forelse ($recentOverrides as $event)
                <div wire:key="ov-{{ $event->id }}" class="mb-2 rounded-lg border border-red-200 bg-red-50/50 px-3 py-2">
                    <a href="{{ route('batches.show', $event->item_batch_id) }}" wire:navigate
                       class="font-mono text-xs font-medium text-slate-700 hover:text-teal-700">
                        {{ $event->itemBatch->public_code }}
                    </a>
                    <div class="mt-0.5 text-xs text-slate-600">→ {{ $event->to_status->label() }}</div>
                    <div class="mt-0.5 text-xs text-slate-400">
                        {{ $event->occurred_at->format('d/m H:i') }} · {{ $event->actorName() }}
                    </div>
                    @if ($event->note)
                        <div class="mt-1 text-xs text-slate-500">{{ $event->note }}</div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-400">Belum ada koreksi manual.</p>
            @endforelse
        </div>
    </div>
</div>
