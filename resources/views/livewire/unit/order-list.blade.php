<div>
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
                        <th>Pengantar</th>
                        <th>Box</th>
                        <th>Rincian Alat</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr wire:key="order-{{ $order->id }}">
                            <td class="font-mono text-xs font-medium text-slate-900">{{ $order->order_number }}</td>
                            <td>{{ $order->sent_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $order->courier_name }}</td>
                            <td>{{ $order->box_count }}</td>
                            <td>
                                @if ($order->detailVisibleToUnit())
                                    <span class="text-slate-700">{{ $order->lines_count }} baris · {{ $order->item_batches_count }} label</span>
                                @else
                                    <span class="text-slate-400">Belum didata</span>
                                @endif
                            </td>
                            <td><x-state-pill :state="$order->status" /></td>
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
