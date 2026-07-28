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
                                                <select wire:model="lines.{{ $index }}.instrument_set_id" class="field-input">
                                                    <option value="">— Pilih set alat —</option>
                                                    @foreach ($setOptions as $opt)
                                                        <option value="{{ $opt->id }}">{{ $opt->code }} — {{ $opt->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error("lines.{$index}.instrument_set_id") <p class="field-error">{{ $message }}</p> @enderror
                                            @else
                                                <select wire:model="lines.{{ $index }}.item_id" class="field-input">
                                                    <option value="">— Pilih alat —</option>
                                                    @foreach ($itemOptions as $opt)
                                                        <option value="{{ $opt->id }}">{{ $opt->code }} — {{ $opt->name }}</option>
                                                    @endforeach
                                                </select>
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
                                    <tr wire:key="b-{{ $batch->id }}">
                                        <td class="font-mono text-xs font-medium text-slate-700">{{ $batch->public_code }}</td>
                                        <td>{{ $batch->displayName() }}</td>
                                        <td>{{ $batch->displayQuantity() }}</td>
                                        <td><x-state-pill :state="$batch->status" /></td>
                                        <td class="text-right">
                                            <a href="{{ route('batches.show', $batch->id) }}" wire:navigate
                                               class="btn-secondary !px-3 !py-1.5">Detail</a>
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
                        <dd><x-state-pill :state="$order->status" /></dd>
                    </div>
                    @foreach ([
                        'Unit Pengirim' => $order->originUnit->name,
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
