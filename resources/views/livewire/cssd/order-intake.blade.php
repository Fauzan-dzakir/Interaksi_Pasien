<div>
    <x-page-header :title="'Order ' . $order->order_number"
                   :subtitle="$order->originUnit->name . ' · ' . $order->box_count . ' box · dikirim ' . $order->sent_at->format('d/m/Y H:i')">
        <x-slot:actions>
            <a href="{{ route('cssd.orders') }}" wire:navigate class="btn-secondary">Kembali</a>
            @if ($batches->isNotEmpty())
                <a href="{{ route('cssd.labels', ['order' => $order->id]) }}" wire:navigate class="btn-primary">Cetak Label</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($order->is_cito)
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 flex items-center gap-3 shadow-sm">
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 text-red-600 font-bold text-lg">!</span>
            <div>
                <h3 class="text-sm font-bold text-red-900">URGENSI CITO</h3>
                <p class="text-sm text-red-800">
                    Order ini bersifat CITO dan harus didahulukan. 
                    @if ($order->needed_at)
                        Alat dibutuhkan maksimal pada <strong>{{ $order->needed_at->format('d/m/Y H:i') }}</strong>.
                    @endif
                </p>
            </div>
        </div>
    @endif

    @if ($declaredBatches->isNotEmpty())
        <div class="mb-5 card border-sky-200 bg-sky-50/60 p-5">
            <h2 class="text-sm font-semibold text-sky-900">Deklarasi Unit Saat Kirim</h2>
            <p class="mt-1 text-xs text-sky-800">
                Alat yang dicentang unit saat membuat order ini — rujukan pembanding saja,
                <strong>tetap hitung fisik sendiri</strong> di bawah, jangan disalin mentah-mentah.
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($declaredBatches as $batch)
                    <span class="rounded-lg bg-white px-3 py-1.5 text-xs ring-1 ring-sky-200">
                        <span class="font-medium text-slate-800">{{ $batch->displayName() }}</span>
                        <span class="ml-1 font-mono text-slate-400">{{ $batch->public_code }}</span>
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            @if ($canRecord)
                <div class="card">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-sm font-semibold text-slate-900">Pendataan Barang</h2>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Hitung fisik isi kiriman, lalu catat per set atau per barang.
                        </p>
                    </div>

                    <form wire:submit="save">
                        <div class="space-y-3 p-5">
                            @error('lines') <p class="field-error">{{ $message }}</p> @enderror

                            @foreach ($lines as $index => $row)
                                <div wire:key="line-{{ $index }}" class="rounded-lg border border-slate-200 bg-slate-50/50 p-3">
                                    <div class="flex items-start gap-2">
                                        <div class="w-32 shrink-0">
                                            <select wire:model.live="lines.{{ $index }}.line_type" class="field-input !bg-white">
                                                <option value="set">Per Set</option>
                                                <option value="individual">Per Barang</option>
                                            </select>
                                        </div>

                                        <div class="flex-1">
                                            @if ($row['line_type'] === 'set')
                                                <div wire:ignore wire:key="set-select-{{ $index }}" x-data="{
                                                    init() {
                                                        let ts = new TomSelect(this.$refs.select, { create: false, placeholder: '— Pilih set alat —' });
                                                        ts.on('change', (val) => { $wire.set('lines.{{ $index }}.instrument_set_id', val); });
                                                    }
                                                }">
                                                    <select x-ref="select" class="field-input">
                                                        <option value="">— Pilih set alat —</option>
                                                        @foreach ($setOptions as $opt)
                                                            <option value="{{ $opt->id }}">{{ $opt->code }} — {{ $opt->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                @error("lines.{$index}.instrument_set_id") <p class="field-error">{{ $message }}</p> @enderror
                                            @else
                                                <div wire:ignore wire:key="item-select-{{ $index }}" x-data="{
                                                    init() {
                                                        let ts = new TomSelect(this.$refs.select, { create: false, placeholder: '— Pilih alat —' });
                                                        ts.on('change', (val) => { $wire.set('lines.{{ $index }}.item_id', val); });
                                                    }
                                                }">
                                                    <select x-ref="select" class="field-input">
                                                        <option value="">— Pilih alat —</option>
                                                        @foreach ($itemOptions as $opt)
                                                            <option value="{{ $opt->id }}">{{ $opt->code }} — {{ $opt->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                @error("lines.{$index}.item_id") <p class="field-error">{{ $message }}</p> @enderror
                                            @endif
                                        </div>

                                        <div class="w-24 shrink-0">
                                            <input wire:model="lines.{{ $index }}.quantity" type="number" min="1"
                                                   class="field-input" placeholder="Qty">
                                            @error("lines.{$index}.quantity") <p class="field-error">{{ $message }}</p> @enderror
                                        </div>

                                        <button type="button" wire:click="removeLine({{ $index }})"
                                                class="mt-1 shrink-0 rounded-md p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                                title="Hapus baris">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <input wire:model="lines.{{ $index }}.notes" type="text"
                                           class="field-input mt-2 !bg-white text-xs" placeholder="Catatan baris (opsional) — mis. gagang retak">

                                    @if ($row['line_type'] === 'set' && ! empty($row['set_checks']))
                                        <div class="mt-2.5 rounded-md border border-slate-200 bg-white p-3">
                                            <p class="mb-2 text-xs font-semibold text-slate-600">Cek kelengkapan isi set</p>
                                            <div class="space-y-2">
                                                @foreach ($row['set_checks'] as $ci => $check)
                                                    <div class="flex flex-wrap items-center gap-2" wire:key="check-{{ $index }}-{{ $check['item_id'] }}">
                                                        <label class="flex items-center gap-2 text-sm text-slate-700">
                                                            <input type="checkbox"
                                                                   wire:model="lines.{{ $index }}.set_checks.{{ $ci }}.is_present"
                                                                   class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                                            {{ $check['name'] }}
                                                            <span class="text-xs text-slate-400">({{ $check['expected_quantity'] }}x)</span>
                                                        </label>
                                                        <input type="text"
                                                               wire:model="lines.{{ $index }}.set_checks.{{ $ci }}.note"
                                                               class="field-input ml-auto max-w-[45%] !bg-white text-xs"
                                                               placeholder="Catatan (opsional)">
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <p class="mt-1.5 text-xs text-slate-400">
                                        @if ($row['line_type'] === 'set')
                                            Akan dibuat <strong>{{ max(1, (int) $row['quantity']) }} label QR</strong> — tiap set dapat labelnya sendiri.
                                        @else
                                            Akan dibuat <strong>1 label QR</strong> berisi {{ max(1, (int) $row['quantity']) }} pcs dalam satu kemasan.
                                        @endif
                                    </p>
                                </div>
                            @endforeach

                            <button type="button" wire:click="addLine" class="btn-secondary w-full">+ Tambah Baris</button>
                        </div>

                        <div class="flex items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-3">
                            <p class="text-xs text-slate-500">
                                Setelah disimpan, unit otomatis diberi notifikasi dan bisa melihat rincian ini.
                            </p>
                            <button type="submit" class="btn-primary shrink-0" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="save">Simpan Pendataan</span>
                                <span wire:loading wire:target="save">Menyimpan…</span>
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            @if ($dirtyZoneGroups->isNotEmpty())
                <div class="card border-amber-200">
                    <div class="border-b border-amber-200 bg-amber-50/50 px-5 py-4">
                        <h2 class="text-sm font-semibold text-amber-900">Progres Zona Kotor</h2>
                        <p class="mt-0.5 text-xs text-amber-800">
                            Alat di tahap ini belum ditempel barcode fisik (baru ditempel saat pengemasan),
                            jadi dipindahkan sekaligus per jumlah — bukan dibuka satu per satu.
                        </p>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @foreach ($dirtyZoneGroups as $group)
                            @php
                                [$buttonLabel, $confirmText] = match ($group['status']) {
                                    \App\Enums\ItemBatchStatus::ReturnedDirty => ['Mulai Cuci', 'Mulai cuci '.$group['count'].' alat?'],
                                    \App\Enums\ItemBatchStatus::DirtyZoneWashing => ['Cuci Selesai → Mulai Pengeringan', 'Tandai '.$group['count'].' alat selesai dicuci dan mulai dikeringkan?'],
                                    \App\Enums\ItemBatchStatus::DirtyZoneDrying => ['Pengeringan Selesai', 'Tandai '.$group['count'].' alat selesai dikeringkan? Alat akan masuk antrian cek kebersihan.'],
                                    default => ['Lanjutkan', 'Lanjutkan?'],
                                };
                            @endphp
                            <div wire:key="dzg-{{ $group['status']->value }}" class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                                <div>
                                    <span class="font-semibold text-slate-900">{{ $group['count'] }}</span>
                                    <span class="text-sm text-slate-600">alat — {{ $group['status']->label() }}</span>
                                </div>
                                <button type="button"
                                        wire:click="advanceZoneGroup('{{ $group['status']->value }}')"
                                        wire:confirm="{{ $confirmText }}"
                                        class="btn-primary !px-3 !py-1.5 whitespace-nowrap">
                                    {{ $buttonLabel }}
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($recordedLines->isNotEmpty())
                <div class="card">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-sm font-semibold text-slate-900">Hasil Pendataan</h2>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Oleh {{ $order->intakeRecordedBy?->name ?? '—' }}
                            @if ($order->intake_recorded_at) pada {{ $order->intake_recorded_at->format('d/m/Y H:i') }} @endif
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead>
                                <tr><th>Jenis</th><th>Nama</th><th>Jumlah</th><th>Catatan</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($recordedLines as $line)
                                    <tr wire:key="rec-{{ $line->id }}">
                                        <td>{{ $line->line_type->label() }}</td>
                                        <td>
                                            <div class="font-medium text-slate-900">{{ $line->subjectName() }}</div>
                                            <div class="font-mono text-xs text-slate-400">{{ $line->subjectCode() }}</div>
                                        </td>
                                        <td>{{ $line->quantity }}</td>
                                        <td class="text-slate-500">{{ $line->notes ?? '—' }}</td>
                                    </tr>
                                    @if ($line->line_type === \App\Enums\BatchType::Set && $line->itemChecks->isNotEmpty())
                                        <tr wire:key="rec-checks-{{ $line->id }}" class="bg-slate-50/60">
                                            <td></td>
                                            <td colspan="3" class="pb-3 pt-0">
                                                <x-set-checklist :checks="$line->itemChecks" />
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-sm font-semibold text-slate-900">Label yang Dibuat ({{ $batches->count() }})</h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead>
                                <tr><th>Kode Label</th><th>Alat / Set</th><th>Jumlah</th><th>Status</th><th class="text-right">Aksi</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($batches as $batch)
                                    @php $qcStage = \App\Enums\BatchQcStage::forStatus($batch->status); @endphp
                                    <tr wire:key="b-{{ $batch->id }}" @class(['bg-amber-50/40' => $qcStage])>
                                        <td class="font-mono text-xs font-medium text-slate-700">{{ $batch->public_code }}</td>
                                        <td>{{ $batch->displayName() }}</td>
                                        <td>{{ $batch->displayQuantity() }}</td>
                                        <td>
                                            <x-state-pill :state="$batch->status" />
                                            @if ($qcStage)
                                                <div class="mt-1">
                                                    <span class="text-xs font-medium text-amber-700 bg-amber-100 rounded px-2 py-0.5 w-fit border border-amber-200">
                                                        Perlu cek {{ $qcStage->label() }}
                                                    </span>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('batches.show', $batch->id) }}" wire:navigate
                                               class="{{ $qcStage ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-1.5 whitespace-nowrap">
                                                {{ $qcStage ? 'Isi Checklist' : 'Detail' }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-5">
            <div class="card p-5">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">Informasi Order</h2>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Status</dt>
                        <dd>
                            <x-state-pill :state="$order->status" />
                            @if ($order->status === \App\Enums\DeliveryOrderStatus::Processing && ! empty($order->status_summary))
                                <div class="mt-2 flex flex-col items-end gap-1">
                                    @foreach ($order->status_summary as $label => $count)
                                        <span class="text-xs text-slate-500 bg-slate-100 rounded px-2 py-0.5 w-fit border border-slate-200">
                                            {{ $count }} {{ $label }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </dd>
                    </div>
                    @foreach ([
                        'Unit Pengirim' => $order->originUnit->name,
                        'Lokasi Ambil' => $order->pickup_location ?? '—',
                        'Petugas Pengantar' => $order->courier_name,
                        'Dibuat Oleh' => $order->submittedBy->name,
                        'Jam Kirim' => $order->sent_at->format('d/m/Y H:i'),
                        'Jumlah Box' => $order->box_count . ' box',
                    ] as $label => $value)
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">{{ $label }}</dt>
                            <dd class="text-right font-medium text-slate-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($order->notes)
                    <div class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900 ring-1 ring-amber-200">
                        <span class="font-medium">Catatan unit:</span> {{ $order->notes }}
                    </div>
                @endif
                
                @if ($order->photos)
                    <div class="mt-4">
                        <span class="font-medium text-slate-700 text-sm">Foto Kondisi Alat:</span>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach (json_decode($order->photos, true) as $photo)
                                <img src="{{ Storage::url($photo) }}" class="h-20 w-20 object-cover rounded-md border border-slate-200" alt="Foto order">
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            @if ($canRecord)
                <div class="card border-sky-200 bg-sky-50/50 p-5">
                    <h2 class="text-sm font-semibold text-sky-900">Aturan pembuatan label</h2>
                    <ul class="mt-2 space-y-1.5 text-sm text-sky-800">
                        <li><strong>Per Set</strong> — tiap set dapat 1 label QR sendiri, karena tiap set dikemas dan beredar terpisah.</li>
                        <li><strong>Per Barang</strong> — alat lepasan sejenis digabung dalam 1 label QR berisi sejumlah pcs.</li>
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
