<div>
    <x-page-header :title="$asset->current_code" :subtitle="$asset->displayName() . ' · ' . $asset->asset_type->label()">
        <x-slot:actions>
            @if ($canOverride)
                <button wire:click="$set('showOverride', true)" class="btn-secondary">Koreksi Admin</button>
            @endif
            @if ($canAdvance)
                <a href="{{ route('cssd.barcodes', ['q' => $asset->current_code]) }}" wire:navigate class="btn-secondary">Cetak Label</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @error('action')
        <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    @unless ($asset->is_complete)
        <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3">
            <p class="text-sm font-semibold text-red-900">Set ini ditandai tidak lengkap</p>
            <p class="mt-0.5 text-sm text-red-800">
                Ada isi yang tidak ditemukan pada pemeriksaan terakhir. Rinciannya di panel “Pemeriksaan Isi Set”.
            </p>
        </div>
    @endunless

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="card">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">Riwayat Perpindahan</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Tercatat permanen, tidak bisa diubah maupun dihapus.</p>
                </div>

                <ol class="divide-y divide-slate-100">
                    @foreach ($asset->events->reverse() as $event)
                        <li wire:key="ev-{{ $event->id }}" class="flex gap-4 px-5 py-4">
                            <div class="flex flex-col items-center">
                                <span @class([
                                    'mt-1 h-2.5 w-2.5 shrink-0 rounded-full',
                                    'bg-red-500' => $event->is_admin_override,
                                    'bg-brand-500' => ! $event->is_admin_override,
                                ])></span>
                                @unless ($loop->last)
                                    <span class="mt-1 w-px flex-1 bg-slate-200"></span>
                                @endunless
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-state-pill :state="$event->to_status" />
                                    @if ($event->is_admin_override)
                                        <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">Koreksi Admin</span>
                                    @endif
                                </div>

                                <div class="mt-1.5 text-sm text-slate-600">
                                    @if ($event->from_status === null)
                                        Aset pertama kali tercatat di sistem
                                    @elseif ($event->from_status === $event->to_status)
                                        Catatan pada tahap ini
                                    @else
                                        Dari “{{ $event->from_status->label() }}”
                                    @endif
                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $event->occurred_at->format('d/m/Y H:i:s') }}
                                    · <span class="font-medium text-slate-700">{{ $event->actorName() }}</span>
                                    @if ($event->station_context) · {{ $event->station_context }} @endif
                                    · via {{ $event->input_method->label() }}
                                </div>

                                @if ($event->note)
                                    <div class="mt-1.5 rounded bg-slate-50 px-2.5 py-1.5 text-xs text-slate-600">{{ $event->note }}</div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            @if ($latestChecks->isNotEmpty())
                <div class="card">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-sm font-semibold text-slate-900">Pemeriksaan Isi Set Terakhir</h2>
                        <p class="mt-0.5 text-xs text-slate-500">
                            {{ $latestChecks->first()->checked_at->format('d/m/Y H:i') }}
                            · oleh {{ $latestChecks->first()->checkedBy?->name ?? 'tidak ada' }}
                        </p>
                    </div>

                    <ul class="divide-y divide-slate-100">
                        @foreach ($latestChecks as $check)
                            <li wire:key="cc-{{ $check->id }}" class="flex items-center gap-3 px-5 py-3">
                                <span @class([
                                    'flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white',
                                    'bg-leaf-600' => $check->is_present,
                                    'bg-red-500' => ! $check->is_present,
                                ])>{{ $check->is_present ? '✓' : '✗' }}</span>

                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-slate-800">{{ $check->item->name }}</div>
                                    <div class="font-mono text-xs text-slate-400">
                                        {{ $check->item->code }} · {{ $check->expected_quantity }} pcs
                                    </div>
                                </div>

                                @unless ($check->is_present)
                                    <span class="shrink-0 text-xs text-red-600">
                                        {{ $check->note ?: 'Tidak ditemukan' }}
                                    </span>
                                @endunless
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($asset->codes->count() > 1)
                <div class="card p-5">
                    <h2 class="text-sm font-semibold text-slate-900">Riwayat Barcode</h2>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Barcode berganti tiap siklus, tapi identitas alatnya tetap, kode lama tetap bisa ditelusuri.
                    </p>

                    <ul class="mt-3 space-y-2">
                        @foreach ($asset->codes as $code)
                            <li wire:key="code-{{ $code->id }}" class="flex flex-wrap items-center gap-3 text-sm">
                                <span @class([
                                    'font-mono',
                                    'font-bold text-brand-700' => $code->isActive(),
                                    'text-slate-500 line-through' => ! $code->isActive(),
                                ])>{{ $code->code }}</span>

                                @if ($code->isActive())
                                    <span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700 ring-1 ring-brand-200">
                                        Berlaku
                                    </span>
                                @endif

                                <span class="text-xs text-slate-400">
                                    dipasang {{ $code->issued_at->format('d/m/Y H:i') }}
                                    @if ($code->issuedBy) oleh {{ $code->issuedBy->name }} @endif
                                    @if ($code->retired_at) · diganti {{ $code->retired_at->format('d/m/Y H:i') }} @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($canAdvance && count($nextOptions) > 0)
                <div class="card p-5">
                    <h2 class="text-sm font-semibold text-slate-900">Pindahkan Tahap</h2>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Untuk proses normal gunakan Stasiun Scan. Tombol di sini untuk kasus khusus.
                    </p>

                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($nextOptions as $next)
                            <button wire:click="moveTo('{{ $next->value }}')" wire:key="nx-{{ $next->value }}"
                                    wire:confirm="Pindahkan {{ $asset->current_code }} ke &quot;{{ $next->label() }}&quot;?"
                                    class="btn-secondary">→ {{ $next->label() }}</button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-5">
            <div class="card p-5 text-center">
                <div class="mx-auto h-[150px] w-[150px] [&>svg]:h-full [&>svg]:w-full">{!! $qrSvg !!}</div>
                <div class="mt-3 font-mono text-lg font-bold tracking-wider text-slate-900">{{ $asset->current_code }}</div>
                <div class="mt-2"><x-state-pill :state="$asset->status" /></div>
            </div>

            <div class="card p-5">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">Informasi Aset</h2>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Zona</dt>
                        <dd><x-state-pill :state="$asset->zone()" /></dd>
                    </div>
                    @foreach ([
                        'Jenis' => $asset->asset_type->label(),
                        'Nama' => $asset->displayName(),
                        'Batch' => $asset->batch?->name ?? 'Stok bebas',
                        'Unit Pemilik' => $asset->batch?->unit->name ?? 'tidak ada',
                        'Metode Sterilisasi' => $asset->sterilization_method?->label() ?? 'tidak ada',
                        'Jumlah Siklus' => $asset->cycle_count . '×',
                        'Diperbarui' => $asset->status_changed_at->format('d/m/Y H:i'),
                    ] as $label => $value)
                        <div class="flex justify-between gap-4">
                            <dt class="shrink-0 text-slate-500">{{ $label }}</dt>
                            <dd class="text-right font-medium text-slate-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            @if ($asset->assembled_photo_path)
                <div class="card p-5">
                    <h2 class="mb-2 text-sm font-semibold text-slate-900">Foto Terakhir</h2>
                    <img src="{{ Storage::url($asset->assembled_photo_path) }}" alt="Foto aset"
                         class="w-full rounded-lg ring-1 ring-slate-200">
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
                        <option value="">Pilih status</option>
                        @foreach ($overrideOptions as $opt)
                            <option value="{{ $opt->value }}">{{ $opt->label() }}</option>
                        @endforeach
                    </select>
                    @error('overrideStatus') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="field-label" for="ov-reason">Alasan Koreksi</label>
                    <textarea wire:model="overrideReason" id="ov-reason" rows="3" class="field-input"
                              placeholder="mis. alat tidak ditemukan saat stok opname bulanan"></textarea>
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
