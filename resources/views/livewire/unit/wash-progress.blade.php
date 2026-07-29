<div wire:poll.15s>
    <x-page-header title="Progress Pencucian"
                   subtitle="Pantau sampai mana alat kiriman Anda diproses CSSD.">
        <x-slot:actions>
            <a href="{{ route('unit.orders.create') }}" wire:navigate class="btn-primary">+ Pesan Cuci</a>
        </x-slot:actions>
    </x-page-header>

    @if ($readyCount > 0)
        <div class="mb-5 flex flex-wrap items-center gap-3 rounded-xl border border-leaf-300 bg-leaf-50 px-4 py-3">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-leaf-200 font-bold text-leaf-900">
                {{ $readyCount }}
            </span>
            <span class="text-sm text-leaf-900">alat sudah selesai dan siap kembali ke unit Anda.</span>
            <a href="{{ route('unit.orders') }}" wire:navigate class="btn-primary ml-auto shrink-0">Lihat Pesanan</a>
        </div>
    @endif

    {{-- Tahapan proses, bisa diklik untuk menyaring daftar di bawah. --}}
    <div class="grid gap-4 sm:grid-cols-3">
        @foreach ($zones as $row)
            <button wire:click="$set('zoneFilter', '{{ $zoneFilter === $row['zone']->value ? '' : $row['zone']->value }}')"
                    wire:key="z-{{ $row['zone']->value }}"
                    @class([
                        'card p-5 text-left transition',
                        'ring-2 ring-brand-500' => $zoneFilter === $row['zone']->value,
                        'hover:border-brand-300 hover:shadow' => $zoneFilter !== $row['zone']->value,
                    ])>
                <div class="text-sm font-medium text-slate-700">{{ $row['zone']->label() }}</div>
                <div class="mt-1 text-3xl font-bold text-slate-900">{{ $row['count'] }}</div>
                <div class="mt-1 text-xs text-slate-400">{{ $row['zone']->description() }}</div>
            </button>
        @endforeach
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-5 py-4">
                <div>
                    <h2 class="font-semibold text-slate-900">Alat Sedang Diproses</h2>
                    <p class="mt-0.5 text-xs text-slate-500">{{ $totalInProcess }} alat, diperbarui otomatis</p>
                </div>
                @if ($zoneFilter)
                    <button wire:click="$set('zoneFilter', '')" class="text-xs font-medium text-brand-700 hover:text-brand-800">
                        Tampilkan semua
                    </button>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr><th>Barcode</th><th>Alat</th><th>Tahap</th><th>Diperbarui</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($assets as $asset)
                            <tr wire:key="wa-{{ $asset->id }}" class="cursor-pointer"
                                onclick="window.location='{{ route('assets.show', $asset->id) }}'">
                                <td class="font-mono text-xs text-slate-600">{{ $asset->current_code }}</td>
                                <td class="font-medium text-slate-900">{{ $asset->displayName() }}</td>
                                <td><x-state-pill :state="$asset->status" /></td>
                                <td class="text-xs text-slate-500">{{ $asset->status_changed_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <x-empty-state colspan="4" title="Tidak ada alat sedang diproses"
                                           description="Buat pesanan cuci untuk mulai memantau." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-5">
            <h2 class="mb-3 font-semibold text-slate-900">Pesanan Berjalan</h2>

            @forelse ($activeOrders as $order)
                <a href="{{ route('unit.orders.show', $order->id) }}" wire:navigate wire:key="ao-{{ $order->id }}"
                   class="mb-2 block rounded-lg border border-slate-200 p-3 transition hover:border-brand-300 hover:bg-brand-50/40">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-mono text-xs font-semibold text-slate-700">{{ $order->order_number }}</span>
                        @if ($order->is_cito)
                            <span class="rounded bg-red-600 px-1.5 py-0.5 text-[10px] font-bold text-white">CITO</span>
                        @endif
                    </div>
                    <div class="mt-1.5"><x-state-pill :state="$order->status" /></div>
                    <div class="mt-1 text-xs text-slate-400">{{ $order->assets_count }} alat terdata</div>
                </a>
            @empty
                <p class="rounded-lg border border-dashed border-slate-300 px-3 py-6 text-center text-sm text-slate-400">
                    Tidak ada pesanan berjalan.
                </p>
            @endforelse
        </div>
    </div>

    <div class="card mt-5">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold text-slate-900">Riwayat Pesanan Selesai</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr><th>Nomor</th><th>Dibuat</th><th>Alat</th><th>Selesai</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($history as $order)
                        <tr wire:key="h-{{ $order->id }}" class="cursor-pointer"
                            onclick="window.location='{{ route('unit.orders.show', $order->id) }}'">
                            <td class="font-mono text-xs text-slate-600">{{ $order->order_number }}</td>
                            <td class="text-xs text-slate-500">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $order->assets_count }} alat</td>
                            <td class="text-xs text-slate-500">{{ $order->received_at?->format('d/m/Y H:i') ?? 'tidak ada' }}</td>
                            <td><x-state-pill :state="$order->status" /></td>
                        </tr>
                    @empty
                        <x-empty-state colspan="5" title="Belum ada riwayat" />
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($history->hasPages())
            <div class="border-t border-slate-200 p-4">{{ $history->links() }}</div>
        @endif
    </div>
</div>
