<div wire:poll.20s>
    <x-page-header title="Penerimaan Alat Steril"
                   :subtitle="'Konfirmasi alat yang dikembalikan CSSD ke ' . auth()->user()->unit->name . '.'" />

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            @forelse ($pending as $pickup)
                <div wire:key="pickup-{{ $pickup->id }}" class="card border-emerald-300">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 px-5 py-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-sm font-semibold text-slate-900">{{ $pickup->pickup_number }}</span>
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">
                                    {{ $pickup->delivery_method->label() }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">
                                Disiapkan {{ $pickup->dispatchedBy->name }} · {{ $pickup->dispatched_at->format('d/m/Y H:i') }}
                            </p>
                        </div>

                        <button wire:click="confirm({{ $pickup->id }})"
                                wire:confirm="Konfirmasi bahwa {{ $pickup->itemBatches->count() }} alat pada {{ $pickup->pickup_number }} sudah Anda terima?"
                                class="btn-primary shrink-0">
                            Diterima
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead>
                                <tr><th>Kode Label</th><th>Alat / Set</th><th>Jumlah</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($pickup->itemBatches as $batch)
                                    <tr wire:key="pb-{{ $batch->id }}">
                                        <td class="font-mono text-xs text-slate-600">{{ $batch->public_code }}</td>
                                        <td class="font-medium text-slate-900">{{ $batch->displayName() }}</td>
                                        <td>{{ $batch->displayQuantity() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($pickup->notes)
                        <div class="border-t border-slate-200 bg-slate-50 px-5 py-3 text-sm text-slate-600">
                            <span class="font-medium">Catatan CSSD:</span> {{ $pickup->notes }}
                        </div>
                    @endif
                </div>
            @empty
                <x-empty-state title="Tidak ada alat menunggu konfirmasi"
                               description="Anda akan menerima notifikasi begitu CSSD menyiapkan alat steril untuk unit ini." />
            @endforelse

            <div class="card">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">Riwayat Penerimaan</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr><th>Nomor</th><th>Jumlah Alat</th><th>Dikonfirmasi</th><th>Oleh</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($history as $row)
                                <tr wire:key="hist-{{ $row->id }}">
                                    <td class="font-mono text-xs text-slate-600">{{ $row->pickup_number }}</td>
                                    <td>{{ $row->item_batches_count }} alat</td>
                                    <td>{{ $row->confirmed_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $row->confirmedBy?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <x-empty-state colspan="4" title="Belum ada riwayat penerimaan" />
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($history->hasPages())
                    <div class="border-t border-slate-200 p-4">{{ $history->links() }}</div>
                @endif
            </div>
        </div>

        <div class="space-y-5">
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-slate-900">Tandai Alat Dipakai</h2>
                <p class="mt-1 text-xs text-slate-500">
                    Scan label alat saat mulai digunakan, agar posisinya tercatat.
                </p>

                <div class="mt-3">
                    <x-scan-input submit="markInUse" label="Scan atau ketik kode label" />
                </div>

                @if ($feedback)
                    <p @class([
                        'mt-2 text-sm',
                        'text-emerald-700' => $feedbackType === 'success',
                        'text-red-600' => $feedbackType === 'error',
                        'text-slate-500' => $feedbackType === 'info',
                    ])>{{ $feedback }}</p>
                @endif

                <p class="mt-3 text-xs text-slate-400">
                    Alat yang tidak jadi dipakai tidak perlu discan — sistem tetap mengenalinya
                    saat dikembalikan ke CSSD.
                </p>
            </div>

            <div class="card p-5">
                <div class="text-sm text-slate-500">Alat sedang di unit ini</div>
                <div class="mt-1 text-3xl font-semibold text-slate-900">{{ $atUnitCount }}</div>
                <p class="mt-1 text-xs text-slate-400">Sudah diambil atau sedang dipakai.</p>
            </div>
        </div>
    </div>
</div>
