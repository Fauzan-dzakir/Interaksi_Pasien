<div wire:poll.20s>
    <x-page-header title="Pesanan Saya"
                   :subtitle="'Riwayat pesanan cuci alat dari ' . auth()->user()->unit->name . '.'">
        <x-slot:actions>
            <a href="{{ route('unit.orders.create') }}" wire:navigate class="btn-primary">+ Pesan Cuci</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <div class="flex flex-wrap gap-3 border-b border-slate-200 p-4">
            <input wire:model.live.debounce.300ms="search" type="search"
                   placeholder="Cari nomor pesanan..." class="field-input sm:max-w-xs">

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
                        <th>Nomor</th>
                        <th>Foto</th>
                        <th>Dibuat</th>
                        <th>Alat Terdata</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr wire:key="o-{{ $order->id }}" @class(['bg-red-50/50' => $order->is_cito && $order->status->isOpen()])>
                            <td>
                                <div class="font-mono text-xs font-semibold text-slate-900">{{ $order->order_number }}</div>
                                @if ($order->is_cito)
                                    <span class="mt-1 inline-block rounded bg-red-600 px-1.5 py-0.5 text-[10px] font-bold text-white">CITO</span>
                                @endif
                            </td>
                            <td>
                                @if ($order->photos->isNotEmpty())
                                    <div class="flex -space-x-2">
                                        @foreach ($order->photos->take(3) as $photo)
                                            <img src="{{ Storage::url($photo->photo_path) }}" alt="Foto barang"
                                                 class="h-9 w-9 rounded-lg object-cover ring-2 ring-white">
                                        @endforeach
                                        @if ($order->photos->count() > 3)
                                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-xs font-semibold text-slate-600 ring-2 ring-white">
                                                +{{ $order->photos->count() - 3 }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">tidak ada</span>
                                @endif
                            </td>
                            <td class="text-xs text-slate-600">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-xs text-slate-500">{{ $order->assets_count }} alat</td>
                            <td><x-state-pill :state="$order->status" /></td>
                            <td class="text-right">
                                <a href="{{ route('unit.orders.show', $order->id) }}" wire:navigate
                                   class="btn-secondary !px-3 !py-1.5">Lihat</a>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="6" title="Belum ada pesanan"
                                       description="Klik Pesan Cuci untuk mengirim alat kotor ke CSSD." />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="border-t border-slate-200 p-4">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
