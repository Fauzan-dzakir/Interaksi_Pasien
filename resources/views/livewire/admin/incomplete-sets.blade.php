<div>
    <x-page-header title="Set Tidak Lengkap"
                   subtitle="Set yang isinya tidak ditemukan saat pemeriksaan, bahan tindak lanjut & evaluasi SOP." />

    <div class="mb-5 grid gap-4 sm:grid-cols-2">
        <div class="card p-5">
            <div class="text-sm text-slate-500">Set Bertanda Tidak Lengkap</div>
            <div class="mt-1 text-3xl font-semibold {{ $sets->total() > 0 ? 'text-red-600' : 'text-slate-900' }}">
                {{ $sets->total() }}
            </div>
        </div>
        <div class="card p-5">
            <div class="text-sm text-slate-500">Total Catatan Alat Hilang</div>
            <div class="mt-1 text-3xl font-semibold text-slate-900">{{ $totalMissingRecords }}</div>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="border-b border-slate-200 p-4">
                <input wire:model.live.debounce.300ms="search" type="search"
                       placeholder="Cari barcode atau nama set…" class="field-input sm:max-w-xs">
            </div>

            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr><th>Barcode</th><th>Set</th><th>Batch / Unit</th><th>Status</th><th class="text-right">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($sets as $set)
                            <tr wire:key="is-{{ $set->id }}">
                                <td class="font-mono text-xs text-slate-600">{{ $set->current_code }}</td>
                                <td class="font-medium text-slate-900">{{ $set->displayName() }}</td>
                                <td class="text-xs text-slate-500">
                                    {{ $set->batch?->name ?? 'stok bebas' }}
                                    @if ($set->batch) <br>{{ $set->batch->unit->name }} @endif
                                </td>
                                <td><x-state-pill :state="$set->status" /></td>
                                <td class="text-right">
                                    <a href="{{ route('assets.show', $set->id) }}" wire:navigate
                                       class="btn-secondary !px-3 !py-1.5">Lihat Isi</a>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="5" title="Semua set lengkap"
                                           description="Tidak ada set yang ditandai kekurangan isi." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($sets->hasPages())
                <div class="border-t border-slate-200 p-4">{{ $sets->links() }}</div>
            @endif
        </div>

        <div class="space-y-5">
            <div class="card p-5">
                <h2 class="mb-1 text-sm font-semibold text-slate-900">Alat Paling Sering Hilang</h2>
                <p class="mb-3 text-xs text-slate-500">Bahan evaluasi: mungkin perlu perubahan SOP atau penambahan stok.</p>

                @forelse ($topMissing as $row)
                    <div wire:key="tm-{{ $row->item_id }}"
                         class="mb-2 flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2">
                        <span class="truncate text-sm text-slate-700">{{ $row->item->name }}</span>
                        <span class="shrink-0 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">
                            {{ $row->total }}×
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Belum ada catatan alat hilang.</p>
                @endforelse
            </div>

            <div class="card p-5">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">Catatan Terbaru</h2>

                @forelse ($recentMissing as $check)
                    <div wire:key="rm-{{ $check->id }}" class="mb-2 rounded-lg border border-red-200 bg-red-50/50 px-3 py-2">
                        <div class="text-sm font-medium text-slate-800">{{ $check->item->name }}</div>
                        <a href="{{ route('assets.show', $check->asset_id) }}" wire:navigate
                           class="font-mono text-xs text-slate-600 hover:text-brand-700">
                            {{ $check->asset->current_code }}
                        </a>
                        <div class="mt-0.5 text-xs text-slate-400">
                            {{ $check->checked_at->format('d/m H:i') }} · {{ $check->checkedBy?->name ?? 'tidak ada' }}
                        </div>
                        @if ($check->note)
                            <div class="mt-1 text-xs text-slate-500">{{ $check->note }}</div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Belum ada catatan.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
