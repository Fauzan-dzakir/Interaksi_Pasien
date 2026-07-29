<div wire:poll.20s>
    <x-page-header :title="'Pesanan ' . $order->order_number"
                   :subtitle="'Pesanan cuci dari ' . $order->unit->name">
        <x-slot:actions>
            <a href="{{ route('cssd.orders') }}" wire:navigate class="btn-secondary">Kembali</a>
            @can('cancel', $order)
                <button wire:click="$set('showCancel', true)" class="btn-danger !px-4 !py-2">Batalkan</button>
            @endcan
            @if ($canHandBack)
                <button wire:click="$set('showHandBack', true)" class="btn-primary">Kembalikan ke Unit</button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($order->is_cito && $order->status->isOpen())
        <div class="mb-5 flex items-start gap-3 rounded-xl border-2 border-red-400 bg-red-50 px-4 py-3">
            <span class="shrink-0 rounded bg-red-600 px-2 py-1 text-xs font-bold text-white">CITO</span>
            <div class="text-sm text-red-900">
                <strong>Pesanan untuk pasien gawat.</strong>
                @if ($order->needed_at) Dibutuhkan {{ $order->needed_at->format('d/m/Y H:i') }}. @endif
                Dahulukan pesanan ini.
            </div>
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            @if ($canPrepare && $order->status !== \App\Enums\OrderStatus::Cancelled)
                <div class="card p-5">
                    <h2 class="font-semibold text-slate-900">Terima Alat Kotor</h2>
                    <p class="mt-0.5 text-sm text-slate-500">
                        Scan barcode tiap alat yang datang. Di sinilah pendataan terjadi, unit tidak mendata sendiri.
                    </p>

                    <form wire:submit="receiveScan" class="mt-3">
                        <input wire:model="scanCode" type="text" autocomplete="off" autocapitalize="characters"
                               class="field-input font-mono text-lg tracking-wider"
                               placeholder="CSSD-XXXXXXXX" autofocus>
                    </form>

                    @if ($feedback)
                        <p @class([
                            'mt-2 text-sm',
                            'text-leaf-700' => $feedbackType === 'success',
                            'text-red-600' => $feedbackType === 'error',
                            'text-slate-500' => $feedbackType === 'info',
                        ])>{{ $feedback }}</p>
                    @endif
                </div>
            @endif

            @if ($order->photos->isNotEmpty())
                <div class="card p-5">
                    <h2 class="mb-1 font-semibold text-slate-900">Foto dari Unit</h2>
                    <p class="mb-3 text-xs text-slate-500">Acuan isi kiriman yang dilampirkan unit saat memesan.</p>
                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-5">
                        @foreach ($order->photos as $photo)
                            <a href="{{ Storage::url($photo->photo_path) }}" target="_blank" wire:key="op-{{ $photo->id }}"
                               class="aspect-square overflow-hidden rounded-lg ring-1 ring-slate-200 transition hover:ring-brand-400">
                                <img src="{{ Storage::url($photo->photo_path) }}" alt="Foto barang"
                                     class="h-full w-full object-cover">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2 class="font-semibold text-slate-900">Alat Terdata ({{ $order->assets->count() }})</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Hasil scan barcode saat alat diterima.</p>
                    </div>
                    @if ($allDone)
                        <span class="rounded-full bg-leaf-100 px-2.5 py-1 text-xs font-semibold text-leaf-800">
                            Semua selesai steril
                        </span>
                    @elseif ($pendingAssets->isNotEmpty())
                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                            {{ $pendingAssets->count() }} masih diproses
                        </span>
                    @endif
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
                                <x-empty-state colspan="4" title="Belum ada alat discan"
                                               description="Scan barcode alat kotor yang datang dari unit." />
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($order->assets->isNotEmpty() && ! $allDone)
                    <div class="border-t border-slate-200 bg-slate-50 px-5 py-3 text-xs text-slate-500">
                        Alat diproses lewat Stasiun Scan dan Barcode Baru. Tombol kembalikan ke unit
                        muncul setelah seluruh alat berstatus tersedia.
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-5">
            <div class="card p-5">
                <h2 class="mb-3 font-semibold text-slate-900">Informasi Pesanan</h2>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Status</dt>
                        <dd><x-state-pill :state="$order->status" /></dd>
                    </div>
                    @foreach ([
                        'Unit' => $order->unit->name,
                        'Dipesan Oleh' => $order->requestedBy->name,
                        'Dibuat' => $order->created_at->format('d/m/Y H:i'),
                        'Dibutuhkan' => $order->needed_at?->format('d/m/Y H:i') ?? 'tidak ada',
                        'Batch' => $order->batch?->name ?? 'tidak ada',
                        'Dikembalikan' => $order->handed_over_at?->format('d/m/Y H:i') ?? 'tidak ada',
                        'Diterima Unit' => $order->received_at?->format('d/m/Y H:i') ?? 'tidak ada',
                    ] as $label => $value)
                        <div class="flex justify-between gap-4">
                            <dt class="shrink-0 text-slate-500">{{ $label }}</dt>
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

            @if ($order->handover_photo_path)
                <div class="card p-5">
                    <h2 class="mb-2 font-semibold text-slate-900">Bukti Serah Terima</h2>
                    <img src="{{ Storage::url($order->handover_photo_path) }}" alt="Foto serah terima"
                         class="w-full rounded-lg ring-1 ring-slate-200">
                    @if ($order->receiver_name)
                        <p class="mt-2 text-sm text-slate-600">Diterima oleh: <strong>{{ $order->receiver_name }}</strong></p>
                    @endif
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

    <x-modal :show="$showHandBack" title="Kembalikan Alat ke Unit">
        <form wire:submit="handBack" id="handback-form" class="space-y-4">
            <p class="rounded-lg bg-leaf-50 px-3 py-2 text-sm text-leaf-800 ring-1 ring-leaf-200">
                {{ $order->assets->count() }} alat sudah selesai steril dan akan dikembalikan ke
                {{ $order->unit->name }}. Unit akan menerima notifikasi untuk mengonfirmasi.
            </p>

            <div>
                <label class="field-label" for="hb-receiver">Nama Penerima</label>
                <input wire:model="receiverName" id="hb-receiver" type="text" class="field-input" placeholder="Opsional">
                @error('receiverName') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="field-label" for="hb-photo">Foto Bukti Serah Terima</label>
                <input wire:model="handoverPhoto" id="hb-photo" type="file" accept="image/*" capture="environment"
                       class="field-input !py-1.5">
                <p class="mt-1 text-xs text-slate-400">Disarankan, jadi bukti kuat bila terjadi selisih.</p>
                @error('handoverPhoto') <p class="field-error">{{ $message }}</p> @enderror

                <div wire:loading wire:target="handoverPhoto" class="mt-1 text-xs text-slate-500">Mengunggah foto...</div>

                @if ($handoverPhoto)
                    <img src="{{ $handoverPhoto->temporaryUrl() }}" alt="Pratinjau"
                         class="mt-2 h-32 rounded-lg object-cover ring-1 ring-slate-200">
                @endif
            </div>
        </form>

        <x-slot:footer>
            <button type="button" wire:click="$set('showHandBack', false)" class="btn-secondary">Batal</button>
            <button type="submit" form="handback-form" class="btn-primary">Kembalikan</button>
        </x-slot:footer>
    </x-modal>

    <x-modal :show="$showCancel" title="Batalkan Pesanan">
        <form wire:submit="cancel" id="cancel-form" class="space-y-3">
            <p class="text-sm text-slate-600">
                Pesanan hanya bisa dibatalkan selama belum ada alat yang diterima.
            </p>
            <div>
                <label class="field-label" for="c-reason">Alasan Pembatalan</label>
                <textarea wire:model="cancelReason" id="c-reason" rows="3" class="field-input"
                          placeholder="mis. unit membatalkan permintaan"></textarea>
                @error('cancelReason') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </form>

        <x-slot:footer>
            <button type="button" wire:click="$set('showCancel', false)" class="btn-secondary">Tutup</button>
            <button type="submit" form="cancel-form" class="btn-danger !px-4 !py-2">Batalkan Pesanan</button>
        </x-slot:footer>
    </x-modal>
</div>
