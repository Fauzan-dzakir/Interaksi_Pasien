<div wire:poll.20s>
    <x-page-header title="Order Masuk" subtitle="Kiriman alat kotor dari seluruh unit rumah sakit." />

    @if ($pendingCount > 0)
        <div class="mb-4 flex items-center gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-200 font-semibold">{{ $pendingCount }}</span>
            <span>order menunggu pendataan. Unit tidak bisa melihat rincian alatnya sebelum Anda mendata.</span>
        </div>
    @endif

    <div class="card">
        <div class="flex flex-wrap gap-3 border-b border-slate-200 p-4">
            <input wire:model.live.debounce.300ms="search" type="search"
                   placeholder="Cari nomor order / pengantar…" class="field-input sm:max-w-xs">

            <select wire:model.live="filterStatus" class="field-input sm:max-w-[14rem]">
                <option value="">Semua status</option>
                @foreach ($statusOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterUnit" class="field-input sm:max-w-[14rem]">
                <option value="">Semua unit</option>
                @foreach ($unitOptions as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Nomor Order</th>
                        <th>Unit Pengirim</th>
                        <th>Dikirim</th>
                        <th>Lokasi & Pengantar</th>
                        <th>Box</th>
                        <th>Label</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        @php $needsIntake = $order->status === \App\Enums\DeliveryOrderStatus::PendingCssdIntake; @endphp
                        <tr wire:key="order-{{ $order->id }}" @class(['bg-amber-50/40' => $needsIntake])>
                            <td class="font-mono text-xs font-medium text-slate-900">
                                {{ $order->order_number }}
                                @if ($order->is_cito)
                                    <div class="mt-1 inline-flex items-center gap-1 rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700">
                                        CITO
                                    </div>
                                @endif
                            </td>
                            <td class="font-medium text-slate-800">{{ $order->originUnit->name }}</td>
                            <td>
                                {{ $order->sent_at->format('d/m H:i') }}
                                @if ($order->is_cito && $order->needed_at)
                                    <div class="text-[10px] font-medium text-red-600 mt-0.5">Batas: {{ $order->needed_at->format('d/m H:i') }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="font-medium">{{ $order->courier_name }}</div>
                                @if ($order->pickup_location)
                                    <div class="text-xs text-slate-500 mt-0.5">{{ $order->pickup_location }}</div>
                                @endif
                            </td>
                            <td>{{ $order->box_count }}</td>
                            <td>{{ $order->item_batches_count ?: '—' }}</td>
                            <td><x-state-pill :state="$order->status" /></td>
                            <td class="text-right">
                                <a href="{{ route('cssd.orders.show', $order->id) }}" wire:navigate
                                   class="{{ $needsIntake ? 'btn-primary !px-3 !py-1.5' : 'btn-secondary !px-3 !py-1.5' }} whitespace-nowrap">
                                    {{ $needsIntake ? 'Data Sekarang' : 'Lihat' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="8" title="Belum ada order masuk"
                                       description="Order akan muncul di sini begitu unit mengirim alat kotor." />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="border-t border-slate-200 p-4">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
