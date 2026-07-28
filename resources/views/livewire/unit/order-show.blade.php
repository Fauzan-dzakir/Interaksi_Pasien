<div wire:poll.15s>
    <x-page-header :title="'Order ' . $order->order_number"
                   :subtitle="$order->originUnit->name . ' · dikirim ' . $order->sent_at->format('d/m/Y H:i')">
        <x-slot:actions>
            <a href="{{ route('unit.orders') }}" wire:navigate class="btn-secondary">Kembali</a>
            @can('cancel', $order)
                <button wire:click="$set('showCancel', true)" class="btn-danger">Batalkan Order</button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            {{-- Sebelum CSSD mendata, unit sengaja hanya melihat status — belum ada rincian alat. --}}
            @unless ($order->detailVisibleToUnit())
                <div class="card border-amber-200 bg-amber-50/60 p-6">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">!</span>
                        <div>
                            <h2 class="text-sm font-semibold text-amber-900">{{ $order->status->label() }}</h2>
                            <p class="mt-1 text-sm text-amber-800">
                                @if ($order->status === \App\Enums\DeliveryOrderStatus::Cancelled)
                                    Order ini dibatalkan.
                                @else
                                    Petugas CSSD belum selesai menghitung dan mendata isi kiriman.
                                    Rincian alat akan muncul otomatis di halaman ini begitu pendataan disimpan —
                                    Anda juga akan menerima notifikasi.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-sm font-semibold text-slate-900">Rincian Pendataan CSSD</h2>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Didata oleh {{ $order->intakeRecordedBy?->name ?? '—' }}
                            @if ($order->intake_recorded_at) pada {{ $order->intake_recorded_at->format('d/m/Y H:i') }} @endif
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead>
                                <tr>
                                    <th>Jenis</th>
                                    <th>Nama</th>
                                    <th>Jumlah</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lines as $line)
                                    <tr wire:key="line-{{ $line->id }}">
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
                        <h2 class="text-sm font-semibold text-slate-900">Posisi Alat Saat Ini</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Diperbarui otomatis tiap 15 detik.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead>
                                <tr>
                                    <th>Kode Label</th>
                                    <th>Alat / Set</th>
                                    <th>Jumlah</th>
                                    <th>Zona</th>
                                    <th>Status Rinci</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($batches as $batch)
                                    <tr wire:key="batch-{{ $batch->id }}">
                                        <td class="font-mono text-xs text-slate-600">{{ $batch->public_code }}</td>
                                        <td class="font-medium text-slate-900">{{ $batch->displayName() }}</td>
                                        <td>{{ $batch->displayQuantity() }}</td>
                                        <td><x-state-pill :state="$batch->zone()" /></td>
                                        <td class="text-slate-600">{{ $batch->status->label() }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('batches.show', $batch->id) }}" wire:navigate
                                               class="btn-secondary !px-3 !py-1.5">Riwayat</a>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="6" title="Belum ada label alat dibuat" />
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endunless
        </div>

        <div class="space-y-5">
            <div class="card p-5">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">Informasi Pengiriman</h2>
                <dl class="space-y-2.5 text-sm">
                    @foreach ([
                        'Status' => null,
                        'Unit Pengirim' => $order->originUnit->name,
                        'Petugas Pengantar' => $order->courier_name,
                        'Dibuat Oleh' => $order->submittedBy->name,
                        'Jam Kirim' => $order->sent_at->format('d/m/Y H:i'),
                        'Jumlah Box' => $order->box_count . ' box',
                    ] as $label => $value)
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">{{ $label }}</dt>
                            <dd class="text-right font-medium text-slate-900">
                                @if ($label === 'Status')
                                    <x-state-pill :state="$order->status" />
                                @else
                                    {{ $value }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>

                @if ($order->notes)
                    <div class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-600 ring-1 ring-slate-200">
                        <span class="font-medium text-slate-700">Catatan:</span> {{ $order->notes }}
                    </div>
                @endif
            </div>

            @if ($zoneSummary->isNotEmpty())
                <div class="card p-5">
                    <h2 class="mb-3 text-sm font-semibold text-slate-900">Ringkasan Zona</h2>
                    <ul class="space-y-2">
                        @foreach ($zoneSummary as $row)
                            <li class="flex items-center justify-between gap-3 text-sm">
                                <x-state-pill :state="$row['zone']" />
                                <span class="font-semibold text-slate-900">{{ $row['count'] }} label</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card p-5">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">Riwayat Order</h2>
                <ol class="space-y-3">
                    @foreach ($order->events as $event)
                        <li class="flex gap-3 text-sm">
                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-teal-500"></span>
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

    <x-modal :show="$showCancel" title="Batalkan Order">
        <form wire:submit="cancel" id="cancel-form" class="space-y-3">
            <p class="text-sm text-slate-600">
                Order hanya bisa dibatalkan selama CSSD belum mendata isinya.
                Pembatalan tercatat permanen beserta nama Anda dan alasannya.
            </p>
            <div>
                <label class="field-label" for="cancel-reason">Alasan Pembatalan</label>
                <textarea wire:model="cancelReason" id="cancel-reason" rows="3" class="field-input"
                          placeholder="mis. salah kirim unit, barang ditarik kembali"></textarea>
                @error('cancelReason') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </form>

        <x-slot:footer>
            <button type="button" wire:click="$set('showCancel', false)" class="btn-secondary">Tutup</button>
            <button type="submit" form="cancel-form" class="btn-danger !px-4 !py-2">Batalkan Order</button>
        </x-slot:footer>
    </x-modal>
</div>
