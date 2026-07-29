<div wire:poll.15s>
    <x-page-header :title="'Pesanan ' . $order->order_number"
                   :subtitle="'Pesanan cuci dari ' . $order->unit->name">
        <x-slot:actions>
            <a href="{{ route('unit.orders') }}" wire:navigate class="btn-secondary">Kembali</a>
            @can('cancel', $order)
                <button wire:click="$set('showCancel', true)" class="btn-danger !px-4 !py-2">Batalkan</button>
            @endcan
            @if ($canConfirm)
                <button wire:click="confirmReceipt"
                        wire:confirm="Konfirmasi bahwa {{ $order->assets->count() }} alat pada pesanan ini sudah kembali ke unit Anda?"
                        class="btn-primary">Konfirmasi Diterima</button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($order->is_cito && $order->status->isOpen())
        <div class="mb-5 flex items-center gap-3 rounded-xl border-2 border-red-400 bg-red-50 px-4 py-3">
            <span class="shrink-0 rounded bg-red-600 px-2 py-1 text-xs font-bold text-white">CITO</span>
            <span class="text-sm text-red-900">
                Pesanan prioritas.
                @if ($order->needed_at) Dibutuhkan {{ $order->needed_at->format('d/m/Y H:i') }}. @endif
            </span>
        </div>
    @endif

    @if ($canConfirm)
        <div class="mb-5 rounded-xl border border-leaf-300 bg-leaf-50 px-4 py-3 text-sm text-leaf-900">
            Alat sudah selesai disterilkan. Konfirmasi setelah alat benar-benar di tangan Anda.
        </div>
    @endif

    {{-- Lini masa progres, ringkas dan mudah dibaca sekilas. --}}
    <div class="card mb-5 p-5">
        <h2 class="mb-4 font-semibold text-slate-900">Progres Pesanan</h2>

        <div class="flex flex-wrap gap-2">
            @foreach ($progressSteps as $step)
                <div wire:key="ps-{{ $loop->index }}" @class([
                    'flex min-w-36 flex-1 items-center gap-2 rounded-lg border px-3 py-2.5',
                    'border-brand-300 bg-brand-50' => $step['state'] === 'current',
                    'border-leaf-300 bg-leaf-50' => $step['state'] === 'done',
                    'border-slate-200 bg-slate-50' => $step['state'] === 'todo',
                ])>
                    <span @class([
                        'flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                        'bg-brand-600 text-white' => $step['state'] === 'current',
                        'bg-leaf-500 text-white' => $step['state'] === 'done',
                        'bg-slate-200 text-slate-500' => $step['state'] === 'todo',
                    ])>{{ $step['state'] === 'done' ? '✓' : $loop->iteration }}</span>

                    <span @class([
                        'text-xs font-medium leading-tight',
                        'text-brand-800' => $step['state'] === 'current',
                        'text-leaf-800' => $step['state'] === 'done',
                        'text-slate-500' => $step['state'] === 'todo',
                    ])>{{ $step['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            @if ($order->photos->isNotEmpty())
                <div class="card p-5">
                    <h2 class="mb-3 font-semibold text-slate-900">Foto Barang yang Dikirim</h2>
                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-5">
                        @foreach ($order->photos as $photo)
                            <a href="{{ Storage::url($photo->photo_path) }}" target="_blank" wire:key="p-{{ $photo->id }}"
                               class="aspect-square overflow-hidden rounded-lg ring-1 ring-slate-200 transition hover:ring-brand-400">
                                <img src="{{ Storage::url($photo->photo_path) }}" alt="Foto barang"
                                     class="h-full w-full object-cover">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="font-semibold text-slate-900">Alat yang Terdata CSSD</h2>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Hasil pemindaian barcode saat alat diterima, jadi Anda tahu persis apa yang masuk.
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr><th>Barcode</th><th>Alat</th><th>Status</th><th class="text-right">Aksi</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($order->assets as $asset)
                                <tr wire:key="a-{{ $asset->id }}">
                                    <td class="font-mono text-xs text-slate-600">{{ $asset->current_code }}</td>
                                    <td>
                                        <div class="font-medium text-slate-900">{{ $asset->displayName() }}</div>
                                        @unless ($asset->is_complete)
                                            <span class="text-xs font-medium text-red-600">set tidak lengkap</span>
                                        @endunless
                                    </td>
                                    <td><x-state-pill :state="$asset->status" /></td>
                                    <td class="text-right">
                                        <a href="{{ route('assets.show', $asset->id) }}" wire:navigate
                                           class="btn-secondary !px-3 !py-1.5">Riwayat</a>
                                    </td>
                                </tr>
                            @empty
                                <x-empty-state colspan="4" title="Alat belum diterima CSSD"
                                               description="Antar alat kotornya ke CSSD, petugas akan memindai tiap barcode." />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="card p-5">
                <h2 class="mb-3 font-semibold text-slate-900">Informasi</h2>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Status</dt>
                        <dd><x-state-pill :state="$order->status" /></dd>
                    </div>
                    @foreach ([
                        'Dipesan Oleh' => $order->requestedBy->name,
                        'Dibuat' => $order->created_at->format('d/m/Y H:i'),
                        'Dibutuhkan' => $order->needed_at?->format('d/m/Y H:i') ?? 'tidak ada',
                        'Batch' => $order->batch?->name ?? 'tidak ada',
                        'Selesai Diproses' => $order->handed_over_at?->format('d/m/Y H:i') ?? 'tidak ada',
                        'Kembali ke Unit' => $order->received_at?->format('d/m/Y H:i') ?? 'tidak ada',
                    ] as $label => $value)
                        <div class="flex justify-between gap-4">
                            <dt class="shrink-0 text-slate-500">{{ $label }}</dt>
                            <dd class="text-right font-medium text-slate-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($order->notes)
                    <div class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-600 ring-1 ring-slate-200">
                        <span class="font-medium text-slate-700">Catatan:</span> {{ $order->notes }}
                    </div>
                @endif
            </div>

            @if ($order->handover_photo_path)
                <div class="card p-5">
                    <h2 class="mb-2 font-semibold text-slate-900">Bukti Serah Terima</h2>
                    <img src="{{ Storage::url($order->handover_photo_path) }}" alt="Foto serah terima"
                         class="w-full rounded-lg ring-1 ring-slate-200">
                </div>
            @endif

            <div class="card p-5">
                <h2 class="mb-3 font-semibold text-slate-900">Riwayat</h2>
                <ol class="space-y-3">
                    @foreach ($order->events as $event)
                        <li class="flex gap-3 text-sm">
                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-500"></span>
                            <div>
                                <div class="font-medium text-slate-800">{{ $event->to_status->label() }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ $event->occurred_at->format('d/m/Y H:i') }} · {{ $event->actorName() }}
                                </div>
                                @if ($event->note)
                                    <div class="mt-0.5 text-xs text-slate-400">{{ $event->note }}</div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>

    <x-modal :show="$showCancel" title="Batalkan Pesanan">
        <form wire:submit="cancel" id="cancel-form" class="space-y-3">
            <p class="text-sm text-slate-600">
                Pesanan hanya bisa dibatalkan selama alatnya belum diterima CSSD.
                Pembatalan tercatat permanen beserta nama Anda.
            </p>
            <div>
                <label class="field-label" for="c-reason">Alasan Pembatalan</label>
                <textarea wire:model="cancelReason" id="c-reason" rows="3" class="field-input"
                          placeholder="mis. operasi ditunda"></textarea>
                @error('cancelReason') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </form>

        <x-slot:footer>
            <button type="button" wire:click="$set('showCancel', false)" class="btn-secondary">Tutup</button>
            <button type="submit" form="cancel-form" class="btn-danger !px-4 !py-2">Batalkan Pesanan</button>
        </x-slot:footer>
    </x-modal>
</div>
