<div wire:poll.20s>
    <x-page-header title="Pesanan Masuk" subtitle="Permintaan alat steril dari seluruh unit rumah sakit." />

    @if ($citoCount > 0)
        <div class="mb-4 flex items-center gap-3 rounded-lg border-2 border-red-400 bg-red-50 px-4 py-3 text-sm text-red-900">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-red-500 font-semibold text-white">{{ $citoCount }}</span>
            <span><strong>pesanan CITO</strong> untuk pasien gawat. Dahulukan yang bertanda merah di bawah.</span>
        </div>
    @endif

    @if ($pendingCount > 0)
        <div class="mb-4 flex items-center gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-200 font-semibold">{{ $pendingCount }}</span>
            <span>pesanan menunggu disiapkan.</span>
        </div>
    @endif

    <div class="card">
        <div class="flex flex-wrap gap-3 border-b border-slate-200 p-4">
            <input wire:model.live.debounce.300ms="search" type="search"
                   placeholder="Cari nomor pesanan…" class="field-input sm:max-w-xs">

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
                        <th>Nomor</th>
                        <th>Unit</th>
                        <th>Foto</th>
                        <th>Alat Terdata</th>
                        <th>Dibuat</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        @php $needsWork = $order->status === \App\Enums\OrderStatus::Pending; @endphp
                        <tr wire:key="o-{{ $order->id }}" @class([
                            'bg-red-50/60' => $order->is_cito && $order->status->isOpen(),
                            'bg-amber-50/40' => $needsWork && ! $order->is_cito,
                        ])>
                            <td>
                                <div class="font-mono text-xs font-medium text-slate-900">{{ $order->order_number }}</div>
                                @if ($order->is_cito)
                                    <span class="mt-1 inline-block rounded bg-red-600 px-1.5 py-0.5 text-[10px] font-bold tracking-wide text-white">
                                        CITO
                                    </span>
                                @endif
                            </td>
                            <td class="font-medium text-slate-800">{{ $order->unit->name }}</td>
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
                            <td class="text-xs text-slate-500">{{ $order->assets_count }} alat</td>
                            <td class="text-xs text-slate-500">{{ $order->created_at->format('d/m H:i') }}</td>
                            <td><x-state-pill :state="$order->status" /></td>
                            <td class="text-right">
                                <a href="{{ route('cssd.orders.show', $order->id) }}" wire:navigate
                                   class="{{ $needsWork ? 'btn-primary !px-3 !py-1.5' : 'btn-secondary !px-3 !py-1.5' }}">
                                    {{ $needsWork ? 'Terima Alat' : 'Lihat' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="7" title="Belum ada pesanan masuk"
                                       description="Pesanan muncul di sini begitu unit melakukan checkout alat." />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="border-t border-slate-200 p-4">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
