<div wire:poll.20s>
    <x-page-header title="Kiriman Alat Kotor"
                   subtitle="Konfirmasi penerimaan dari unit, sisi kedua dari konfirmasi dua arah." />

    <div class="space-y-4">
        @forelse ($pending as $shipment)
            <div wire:key="rs-{{ $shipment->id }}" class="card border-sky-300">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 px-5 py-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-sm font-semibold text-slate-900">{{ $shipment->return_number }}</span>
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
                                {{ $shipment->statusLabel() }}
                            </span>
                            @if ($shipment->reorder_batch)
                                <span class="rounded-full bg-brand-100 px-2 py-0.5 text-xs font-medium text-brand-800">
                                    Batch dipesan ulang
                                </span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                                    Batch dilepas ke stok
                                </span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $shipment->unit->name }}
                            @if ($shipment->batch) · batch {{ $shipment->batch->name }} @endif
                            · dikirim {{ $shipment->sentBy->name }} pada {{ $shipment->sent_at->format('d/m/Y H:i') }}
                        </p>
                    </div>

                    <button wire:click="startConfirm({{ $shipment->id }})" class="btn-primary shrink-0">
                        Konfirmasi Terima
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr><th>Barcode</th><th>Alat / Set</th><th>Jenis</th><th>Terpakai?</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($shipment->assets as $asset)
                                <tr wire:key="rsa-{{ $asset->id }}">
                                    <td class="font-mono text-xs text-slate-600">{{ $asset->current_code }}</td>
                                    <td class="font-medium text-slate-900">{{ $asset->displayName() }}</td>
                                    <td>{{ $asset->asset_type->label() }}</td>
                                    <td>
                                        @if ($asset->pivot->was_used)
                                            <span class="text-sm text-slate-700">Terpakai</span>
                                        @else
                                            <span class="text-sm text-slate-400">Tidak terpakai</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($shipment->sender_photo_path || $shipment->notes)
                    <div class="border-t border-slate-200 bg-slate-50 px-5 py-3">
                        @if ($shipment->notes)
                            <p class="text-sm text-slate-600"><span class="font-medium">Catatan unit:</span> {{ $shipment->notes }}</p>
                        @endif
                        @if ($shipment->sender_photo_path)
                            <img src="{{ Storage::url($shipment->sender_photo_path) }}" alt="Foto pengiriman unit"
                                 class="mt-2 h-32 rounded-lg object-cover ring-1 ring-slate-200">
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <x-empty-state title="Tidak ada kiriman menunggu konfirmasi"
                           description="Kiriman muncul di sini begitu unit menyatakan sudah mengirim alat kotor." />
        @endforelse
    </div>

    <div class="card mt-5">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-900">Riwayat Penerimaan</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr><th>Nomor</th><th>Unit</th><th>Jumlah</th><th>Dikonfirmasi</th><th>Oleh</th></tr>
                </thead>
                <tbody>
                    @forelse ($history as $row)
                        <tr wire:key="rh-{{ $row->id }}">
                            <td class="font-mono text-xs text-slate-600">{{ $row->return_number }}</td>
                            <td>{{ $row->unit->name }}</td>
                            <td>{{ $row->assets_count }} alat</td>
                            <td>{{ $row->confirmed_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $row->confirmedBy?->name ?? 'tidak ada' }}</td>
                        </tr>
                    @empty
                        <x-empty-state colspan="5" title="Belum ada riwayat penerimaan" />
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($history->hasPages())
            <div class="border-t border-slate-200 p-4">{{ $history->links() }}</div>
        @endif
    </div>

    <x-modal :show="$confirmingId !== null" title="Konfirmasi Penerimaan Alat Kotor">
        <form wire:submit="confirm" id="confirm-form" class="space-y-4">
            <p class="rounded-lg bg-sky-50 px-3 py-2 text-sm text-sky-800 ring-1 ring-sky-200">
                Setelah dikonfirmasi, alat berpindah ke tahap <strong>Pencucian</strong>.
                Barcode lama tetap berlaku sampai barcode baru dipasang seusai dekontaminasi.
            </p>

            <div>
                <label class="field-label" for="rc-photo">Foto Bukti Penerimaan</label>
                <input wire:model="receiverPhoto" id="rc-photo" type="file" accept="image/*" class="field-input !py-1.5">
                <p class="mt-1 text-xs text-slate-400">Opsional tapi disarankan sebagai bukti kondisi alat saat diterima.</p>
                @error('receiverPhoto') <p class="field-error">{{ $message }}</p> @enderror

                <div wire:loading wire:target="receiverPhoto" class="mt-1 text-xs text-slate-500">Mengunggah foto…</div>

                @if ($receiverPhoto)
                    <img src="{{ $receiverPhoto->temporaryUrl() }}" alt="Pratinjau"
                         class="mt-2 h-32 rounded-lg object-cover ring-1 ring-slate-200">
                @endif
            </div>
        </form>

        <x-slot:footer>
            <button type="button" wire:click="$set('confirmingId', null)" class="btn-secondary">Batal</button>
            <button type="submit" form="confirm-form" class="btn-primary">Konfirmasi Terima</button>
        </x-slot:footer>
    </x-modal>
</div>
