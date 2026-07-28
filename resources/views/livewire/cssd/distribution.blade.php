<div>
    <x-page-header title="Distribusi Alat Steril"
                   subtitle="Serahkan alat dari gudang steril kembali ke unit pemiliknya." />

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="card p-5">
                <label class="field-label" for="unit">Unit Tujuan</label>
                <select wire:model.live="unitId" id="unit" class="field-input sm:max-w-md">
                    <option value="">— Pilih unit —</option>
                    @foreach ($unitOptions as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
                @error('unitId') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            @if ($unitId)
                <div class="card">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
                        <h2 class="text-sm font-semibold text-slate-900">
                            Alat Siap Diserahkan ({{ $available->count() }})
                        </h2>
                        @if ($available->isNotEmpty())
                            <button wire:click="toggleAll" class="text-xs font-medium text-teal-700 hover:text-teal-800">
                                {{ count($selected) === $available->count() ? 'Batal pilih semua' : 'Pilih semua' }}
                            </button>
                        @endif
                    </div>

                    @error('selected') <p class="field-error px-5 pt-3">{{ $message }}</p> @enderror

                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead>
                                <tr>
                                    <th class="w-10"></th>
                                    <th>Kode Label</th>
                                    <th>Alat / Set</th>
                                    <th>Jumlah</th>
                                    <th>Order Asal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($available as $batch)
                                    <tr wire:key="av-{{ $batch->id }}">
                                        <td>
                                            <input type="checkbox" wire:model.live="selected" value="{{ $batch->id }}"
                                                   class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                        </td>
                                        <td class="font-mono text-xs text-slate-600">{{ $batch->public_code }}</td>
                                        <td class="font-medium text-slate-900">{{ $batch->displayName() }}</td>
                                        <td>{{ $batch->displayQuantity() }}</td>
                                        <td class="font-mono text-xs text-slate-500">
                                            {{ $batch->currentDeliveryOrder?->order_number ?? '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="5" title="Belum ada alat siap diserahkan untuk unit ini"
                                                   description="Alat muncul di sini setelah lolos cek label steril dan masuk gudang." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($available->isNotEmpty())
                        <form wire:submit="dispatchItems" class="space-y-4 border-t border-slate-200 bg-slate-50 p-5">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="field-label" for="method">Cara Serah Terima</label>
                                    <select wire:model="method" id="method" class="field-input !bg-white">
                                        @foreach ($methodOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="field-label" for="receiver">Nama Penerima / Pengambil</label>
                                    <input wire:model="receiverName" id="receiver" type="text" class="field-input !bg-white"
                                           placeholder="Opsional">
                                </div>
                            </div>

                            <div>
                                <label class="field-label" for="dist-notes">Catatan</label>
                                <input wire:model="notes" id="dist-notes" type="text" class="field-input !bg-white"
                                       placeholder="Opsional">
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm text-slate-600">
                                    <strong>{{ count($selected) }}</strong> alat dipilih.
                                    Unit akan langsung menerima notifikasi.
                                </p>
                                <button type="submit" class="btn-primary shrink-0" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="dispatchItems">Serahkan Alat</span>
                                    <span wire:loading wire:target="dispatchItems">Memproses…</span>
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            @else
                <x-empty-state title="Pilih unit tujuan"
                               description="Daftar alat siap serah akan muncul setelah unit dipilih." />
            @endif
        </div>

        <div class="space-y-5">
            <div class="card p-5">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">Siap Serah per Unit</h2>
                @forelse ($readyPerUnit as $row)
                    <button wire:click="$set('unitId', '{{ $row->origin_unit_id }}')" wire:key="rpu-{{ $row->origin_unit_id }}"
                            class="mb-1.5 flex w-full items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2 text-left text-sm transition hover:border-teal-300 hover:bg-teal-50">
                        <span class="truncate text-slate-700">{{ $row->originUnit->name }}</span>
                        <span class="shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">
                            {{ $row->total }}
                        </span>
                    </button>
                @empty
                    <p class="text-sm text-slate-400">Belum ada alat di gudang steril.</p>
                @endforelse
            </div>

            <div class="card p-5">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">Menunggu Konfirmasi Unit</h2>
                @forelse ($pendingPickups as $pickup)
                    <div wire:key="pp-{{ $pickup->id }}" class="mb-2 rounded-lg border border-slate-200 px-3 py-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono text-xs font-medium text-slate-700">{{ $pickup->pickup_number }}</span>
                            <span class="text-xs text-slate-400">{{ $pickup->dispatched_at->format('d/m H:i') }}</span>
                        </div>
                        <div class="mt-0.5 text-sm text-slate-600">{{ $pickup->originUnit->name }}</div>
                        <div class="text-xs text-slate-400">
                            {{ $pickup->item_batches_count }} alat · {{ $pickup->delivery_method->label() }}
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Tidak ada serah terima yang menggantung.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
