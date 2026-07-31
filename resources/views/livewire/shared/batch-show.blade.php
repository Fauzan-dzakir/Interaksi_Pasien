<div wire:poll.15s>
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
            @if ($canAdvance && $qcStage)
                {{--
                    Checklist QC opsional — dokumentasi/pembuktian tambahan per tahap.
                    BUKAN pengganti Stasiun Scan: alat tetap bisa berpindah tahap lewat
                    scan biasa tanpa checklist ini sama sekali.
                --}}
                <div class="card p-5">
                    <h2 class="text-sm font-semibold text-slate-900">Checklist QC — {{ $qcStage->label() }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Dokumentasi tambahan, opsional. Untuk proses cepat sehari-hari gunakan Stasiun Scan —
                        checklist ini tidak wajib diisi agar alat bisa berpindah tahap.
                    </p>

                    @error('checklist') <p class="field-error mt-2">{{ $message }}</p> @enderror

                    <form wire:submit="submitChecklist" class="mt-3 space-y-2.5">
                        @foreach ($checklist as $ci => $row)
                            <div wire:key="chk-{{ $batch->id }}-{{ $row['key'] }}"
                                 class="rounded-lg border border-slate-200 bg-slate-50/50 p-2.5">
                                <label class="flex items-center gap-2 text-sm text-slate-800">
                                    <input type="checkbox" wire:model.live="checklist.{{ $ci }}.is_present"
                                           class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                    {{ $row['label'] }}
                                </label>

                                @if (! $row['is_present'])
                                    <input type="text" wire:model="checklist.{{ $ci }}.note"
                                           class="field-input mt-2 !bg-white text-xs"
                                           placeholder="Catatan kenapa tidak sesuai (wajib)">
                                @endif
                            </div>
                        @endforeach

                        @php $failCount = collect($checklist)->where('is_present', false)->count(); @endphp

                        @if ($failCount > 0 && count($qcStage->failTargets()) > 1)
                            <div>
                                <label class="field-label" for="checklist-fail-target">Ada item tidak sesuai — kembalikan alat ke tahap:</label>
                                <select wire:model="checklistFailTarget" id="checklist-fail-target" class="field-input">
                                    @foreach ($qcStage->failTargets() as $opt)
                                        <option value="{{ $opt->value }}">{{ $opt->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <button type="submit"
                                class="{{ $failCount > 0 ? 'btn-danger w-full !py-2' : 'btn-primary w-full' }}"
                                wire:loading.attr="disabled" wire:target="submitChecklist">
                            @if ($failCount > 0)
                                Tandai Tidak Sesuai &amp; Kembalikan Tahap
                            @else
                                Semua Sesuai — Lanjutkan ke Tahap Berikutnya
                            @endif
                        </button>
                    </form>
                </div>
            @endif

            {{--
                Kasus khusus: dari "Sedang Dipakai" (InUse), satu-satunya nextOptions adalah
                "Sudah Diambil Unit" (PickedUp) — tapi ini BUKAN langkah maju dalam alur, itu
                cuma tombol BATALKAN untuk salah tandai/scan di unit. Alat yang benar-benar
                sudah dipakai TIDAK pernah balik jadi "returned_dirty" lewat barcode yang sama;
                begitu unit mengirim baliknya, CSSD mendata ulang dan barcode ini otomatis
                diganti baru (lihat Ganti Barcode / recordIntake). Makanya ditampilkan terpisah
                dengan label & penjelasan yang jelas beda dari kartu "Pindahkan Tahap" biasa,
                supaya tidak terlihat seperti alur maju yang muter balik.
            --}}
            @if ($canAdvance && $batch->status === \App\Enums\ItemBatchStatus::InUse && count($nextOptions) > 0)
                <div class="card border-amber-200 p-5">
                    <h2 class="text-sm font-semibold text-amber-900">↩ Batalkan Tanda "Sedang Dipakai"</h2>
                    <p class="mt-0.5 text-xs text-amber-800">
                        Ini BUKAN langkah maju — cuma untuk membatalkan kalau salah tandai/scan di unit.
                        Alat yang benar-benar sudah dipakai tidak kembali lewat tombol ini; begitu
                        alatnya benar-benar dikirim balik, CSSD akan mendata ulang dari awal di
                        Zona Kotor dan barcode ini otomatis diganti yang baru.
                    </p>

                    <div class="mt-3">
                        <button wire:click="moveTo('{{ \App\Enums\ItemBatchStatus::PickedUp->value }}')"
                                wire:confirm="Batalkan tanda 'Sedang Dipakai' pada {{ $batch->public_code }}?"
                                class="btn-secondary">
                            ↩ Batalkan, kembalikan ke "Sudah Diambil Unit"
                        </button>
                    </div>
                </div>
            @elseif ($canAdvance && count($nextOptions) > 0 && ! $qcStage)
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

            @if ($batch->stageChecks->isNotEmpty())
                <div class="card">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-sm font-semibold text-slate-900">Riwayat Checklist QC</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Dokumentasi tambahan per tahap, di luar jejak perpindahan di atas.</p>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @foreach ($checkedHistoryGroups as $group)
                            @php $first = $group->first(); @endphp
                            <div class="px-5 py-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="text-sm font-medium text-slate-800">
                                        {{ \App\Enums\BatchQcStage::from($first->stage)->label() }}
                                    </span>
                                    <span class="text-xs text-slate-400">
                                        {{ $first->recorded_at->format('d/m/Y H:i') }} · {{ $first->recordedBy?->name ?? '—' }}
                                    </span>
                                </div>
                                <ul class="mt-2 space-y-1">
                                    @foreach ($group as $check)
                                        <li class="flex items-start gap-2 text-xs">
                                            @if ($check->is_present)
                                                <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                                    <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                                    </svg>
                                                </span>
                                            @else
                                                <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                                                    <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </span>
                                            @endif
                                            <span class="{{ $check->is_present ? 'text-slate-700' : 'font-medium text-red-700' }}">
                                                {{ $check->item_label }}
                                            </span>
                                            @if ($check->note)
                                                <span class="text-slate-400">— {{ $check->note }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
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

                    @if ($batch->sterilization_method)
                        <div class="flex justify-between gap-4">
                            <dt class="shrink-0 text-slate-500">Metode Sterilisasi</dt>
                            <dd class="text-right font-medium text-slate-900">{{ $batch->sterilization_method->label() }}</dd>
                        </div>
                    @endif

                    @if ($batch->sterilization_expired_at)
                        @php $isExpired = $batch->sterilization_expired_at->isPast(); @endphp
                        <div class="flex justify-between gap-4">
                            <dt class="shrink-0 text-slate-500">Kedaluwarsa Steril</dt>
                            <dd @class([
                                'text-right font-medium',
                                'text-red-600' => $isExpired,
                                'text-amber-600' => ! $isExpired && $batch->sterilization_expired_at->diffInDays(now()) <= 30,
                                'text-slate-900' => ! $isExpired && $batch->sterilization_expired_at->diffInDays(now()) > 30,
                            ])>
                                {{ $batch->sterilization_expired_at->format('d/m/Y H:i') }}
                                @if ($isExpired) <span class="block text-xs font-semibold">Kedaluwarsa — sterilisasi ulang</span> @endif
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            @if ($batch->assembled_photo_path)
                <div class="card p-5">
                    <h2 class="mb-2 text-sm font-semibold text-slate-900">Foto Set Terakit</h2>
                    <img src="{{ Storage::url($batch->assembled_photo_path) }}" alt="Foto set terakit"
                         class="w-full rounded-lg ring-1 ring-slate-200">
                </div>
            @endif

            <div class="card p-5">
                <h2 class="mb-2 text-sm font-semibold text-slate-900">Foto Dokumentasi Sterilisasi</h2>
                @if ($batch->sterilization_photos)
                    <div class="grid grid-cols-2 gap-2 mt-2 mb-4">
                        @foreach (json_decode($batch->sterilization_photos, true) as $path)
                            <img src="{{ Storage::url($path) }}" alt="Foto Sterilisasi" class="w-full h-24 object-cover rounded-lg ring-1 ring-slate-200">
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-500 mb-4">Belum ada foto dokumentasi.</p>
                @endif
                
                @if ($canAdvance)
                    <form wire:submit="uploadSterilizationPhotos">
                        <input wire:model="sterilizationPhotos" type="file" multiple accept="image/*" class="field-input text-xs mb-2">
                        <div wire:loading wire:target="sterilizationPhotos" class="text-xs text-slate-500 mb-2">Mengunggah file...</div>
                        @error('sterilizationPhotos.*') <p class="field-error mb-2">{{ $message }}</p> @enderror
                        <button type="submit" class="btn-secondary !text-xs !px-2 !py-1 w-full" wire:loading.attr="disabled" wire:target="uploadSterilizationPhotos">
                            <span wire:loading.remove wire:target="uploadSterilizationPhotos">Simpan Foto Baru</span>
                            <span wire:loading wire:target="uploadSterilizationPhotos">Menyimpan...</span>
                        </button>
                    </form>
                @endif
            </div>

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
