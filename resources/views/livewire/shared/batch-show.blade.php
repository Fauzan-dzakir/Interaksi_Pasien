<div>
    <x-page-header :title="$batch->public_code" :subtitle="$batch->displayName() . ' · ' . $batch->displayQuantity()">
        <x-slot:actions>
            @if ($canOverride)
                <button wire:click="$set('showOverride', true)" class="btn-secondary">Koreksi Admin</button>
            @endif
            @if ($canAdvance)
                <a href="{{ route('cssd.labels', ['q' => $batch->public_code]) }}" wire:navigate class="btn-secondary">Cetak Label</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @error('action')
        <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            {{-- Jejak audit: bukti "alat terakhir ada di mana, jam berapa, oleh siapa". --}}
            <div class="card">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">Riwayat Perpindahan</h2>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Tercatat permanen — tidak bisa diubah maupun dihapus.
                    </p>
                </div>

                <ol class="divide-y divide-slate-100">
                    @foreach ($batch->events->reverse() as $event)
                        <li wire:key="ev-{{ $event->id }}" class="flex gap-4 px-5 py-4">
                            <div class="flex flex-col items-center">
                                <span @class([
                                    'mt-1 h-2.5 w-2.5 shrink-0 rounded-full',
                                    'bg-red-500' => $event->is_admin_override,
                                    'bg-teal-500' => ! $event->is_admin_override,
                                ])></span>
                                @unless ($loop->last)
                                    <span class="mt-1 w-px flex-1 bg-slate-200"></span>
                                @endunless
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-state-pill :state="$event->to_status" />
                                    @if ($event->is_admin_override)
                                        <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">
                                            Koreksi Admin
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-1.5 text-sm text-slate-600">
                                    @if ($event->from_status)
                                        Dari “{{ $event->from_status->label() }}”
                                    @else
                                        Alat pertama kali tercatat di sistem
                                    @endif
                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $event->occurred_at->format('d/m/Y H:i:s') }}
                                    · <span class="font-medium text-slate-700">{{ $event->actorName() }}</span>
                                    @if ($event->station_context) · {{ $event->station_context }} @endif
                                    · via {{ $event->input_method->label() }}
                                </div>

                                @if ($event->note)
                                    <div class="mt-1.5 rounded bg-slate-50 px-2.5 py-1.5 text-xs text-slate-600">
                                        {{ $event->note }}
                                    </div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            @if ($lineage->count() > 1)
                <div class="card p-5">
                    <h2 class="text-sm font-semibold text-slate-900">Rantai Penggantian Barcode</h2>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Riwayat alat fisik ini lintas siklus, dari barcode terlama hingga yang berlaku sekarang.
                    </p>

                    <ol class="mt-3 space-y-2">
                        @foreach ($lineage as $node)
                            <li wire:key="ln-{{ $node->id }}" class="flex items-center gap-3">
                                <span class="text-xs text-slate-400">{{ $loop->iteration }}.</span>
                                <a href="{{ route('batches.show', $node->id) }}" wire:navigate
                                   @class([
                                       'font-mono text-sm',
                                       'font-bold text-teal-700' => $node->id === $batch->id,
                                       'text-slate-600 hover:text-teal-700' => $node->id !== $batch->id,
                                   ])>{{ $node->public_code }}</a>
                                <x-state-pill :state="$node->status" />
                                @if ($node->id === $batch->id)
                                    <span class="text-xs text-slate-400">(sedang dilihat)</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif

            @if ($canAdvance && count($nextOptions) > 0)
                <div class="card p-5">
                    <h2 class="text-sm font-semibold text-slate-900">Pindahkan Tahap</h2>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Untuk proses normal gunakan Stasiun Scan. Tombol di sini dipakai untuk
                        jalur kegagalan QC yang butuh penilaian petugas.
                    </p>

                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($nextOptions as $next)
                            <button wire:click="moveTo('{{ $next->value }}')" wire:key="nx-{{ $next->value }}"
                                    wire:confirm="Pindahkan {{ $batch->public_code }} ke &quot;{{ $next->label() }}&quot;?"
                                    class="btn-secondary">
                                → {{ $next->label() }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-5">
            <div class="card p-5 text-center">
                <div class="mx-auto h-[150px] w-[150px] [&>svg]:h-full [&>svg]:w-full">{!! $qrSvg !!}</div>
                <div class="mt-3 font-mono text-lg font-bold tracking-wider text-slate-900">{{ $batch->public_code }}</div>
                <div class="mt-2"><x-state-pill :state="$batch->status" /></div>
            </div>

            <div class="card p-5">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">Informasi Alat</h2>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Zona</dt>
                        <dd><x-state-pill :state="$batch->zone()" /></dd>
                    </div>
                    @foreach ([
                        'Jenis' => $batch->batch_type->label(),
                        'Nama' => $batch->displayName(),
                        'Jumlah' => $batch->displayQuantity(),
                        'Unit Pemilik' => $batch->originUnit->name,
                        'Order Asal' => $batch->currentDeliveryOrder?->order_number ?? '—',
                        'Diperbarui' => $batch->status_changed_at->format('d/m/Y H:i'),
                    ] as $label => $value)
                        <div class="flex justify-between gap-4">
                            <dt class="shrink-0 text-slate-500">{{ $label }}</dt>
                            <dd class="text-right font-medium text-slate-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            @if ($batch->assembled_photo_path)
                <div class="card p-5">
                    <h2 class="mb-2 text-sm font-semibold text-slate-900">Foto Set Terakit</h2>
                    <img src="{{ Storage::url($batch->assembled_photo_path) }}" alt="Foto set terakit"
                         class="w-full rounded-lg ring-1 ring-slate-200">
                </div>
            @endif

            @if ($batch->supersededBy)
                <div class="card border-amber-200 bg-amber-50/60 p-5">
                    <h2 class="text-sm font-semibold text-amber-900">Barcode Sudah Diganti</h2>
                    <p class="mt-1 text-sm text-amber-800">
                        Label ini digantikan oleh
                        <a href="{{ route('batches.show', $batch->supersededBy->new_item_batch_id) }}" wire:navigate
                           class="font-mono font-semibold underline">{{ $batch->supersededBy->newBatch->public_code }}</a>.
                    </p>
                    @if ($batch->supersededBy->reason)
                        <p class="mt-1 text-xs text-amber-700">{{ $batch->supersededBy->reason }}</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @if ($canOverride)
        <x-modal :show="$showOverride" title="Koreksi Admin">
            <form wire:submit="applyOverride" id="override-form" class="space-y-4">
                <p class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800 ring-1 ring-red-200">
                    Koreksi ini melompati alur normal. Gunakan hanya untuk membetulkan salah input
                    atau menyatakan alat hilang. Tindakan tercatat permanen atas nama Anda.
                </p>

                <div>
                    <label class="field-label" for="ov-status">Status Tujuan</label>
                    <select wire:model="overrideStatus" id="ov-status" class="field-input">
                        <option value="">— Pilih status —</option>
                        @foreach ($overrideOptions as $opt)
                            <option value="{{ $opt->value }}">{{ $opt->label() }}</option>
                        @endforeach
                    </select>
                    @error('overrideStatus') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="field-label" for="ov-reason">Alasan Koreksi</label>
                    <textarea wire:model="overrideReason" id="ov-reason" rows="3" class="field-input"
                              placeholder="mis. petugas salah scan di stasiun sterilisasi, dikembalikan ke tahap pengemasan"></textarea>
                    @error('overrideReason') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </form>

            <x-slot:footer>
                <button type="button" wire:click="$set('showOverride', false)" class="btn-secondary">Batal</button>
                <button type="submit" form="override-form" class="btn-primary">Simpan Koreksi</button>
            </x-slot:footer>
        </x-modal>
    @endif
</div>
