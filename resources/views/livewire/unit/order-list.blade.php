<div wire:poll.20s>
    <x-page-header title="Order Saya"
                   :subtitle="'Riwayat pengiriman alat dari ' . auth()->user()->unit->name . ' ke CSSD.'">
        <x-slot:actions>
            <a href="{{ route('unit.orders.create') }}" wire:navigate class="btn-primary">+ Buat Order</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <div class="flex flex-wrap gap-3 border-b border-slate-200 p-4">
            <input wire:model.live.debounce.300ms="search" type="search"
                   placeholder="Cari nomor order atau nama pengantar…" class="field-input sm:max-w-xs">

            <select wire:model.live="filterStatus" class="field-input sm:max-w-xs">
                <option value="">Semua status</option>
                @foreach ($statusOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Nomor Order</th>
                        <th>Dikirim</th>
                        <th>Lokasi & Pengantar</th>
                        <th>Box</th>
                        <th>Rincian Alat</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr wire:key="order-{{ $order->id }}">
                            <td class="font-mono text-xs font-medium text-slate-900">
                                {{ $order->order_number }}
                                @if ($order->is_cito)
                                    <div class="mt-1 inline-flex items-center gap-1 rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700">
                                        CITO
                                    </div>
                                @endif
                            </td>
                            <td>
                                {{ $order->sent_at->format('d/m/Y H:i') }}
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
                            <td>
                                @if ($order->detailVisibleToUnit())
                                    <span class="text-slate-700">{{ $order->item_batches_count }} label</span>
                                @else
                                    <span class="text-slate-400">Belum didata</span>
                                @endif
                            </td>
                            <td>
                                <x-state-pill :state="$order->status" />
                                @if ($order->detailVisibleToUnit() && !empty($order->status_summary))
                                    <div class="mt-2 flex flex-col gap-1">
                                        @foreach ($order->status_summary as $label => $count)
                                            <span class="text-xs text-slate-500 bg-slate-100 rounded px-2 py-0.5 w-fit border border-slate-200">
                                                {{ $count }} {{ $label }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('unit.orders.show', $order->id) }}" wire:navigate
                                   class="btn-secondary !px-3 !py-1.5">Lihat</a>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="7" title="Belum ada order pengiriman"
                                       description="Klik “Buat Order” untuk mengirim alat kotor ke CSSD." />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="border-t border-slate-200 p-4">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
