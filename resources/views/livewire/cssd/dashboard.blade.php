<div wire:poll.20s>
    <x-page-header title="Dashboard CSSD" subtitle="Antrian kerja dan posisi alat di setiap tahap.">
        <x-slot:actions>
            <a href="{{ route('cssd.scan') }}" wire:navigate class="btn-primary">Stasiun Scan</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-5 grid gap-4 lg:grid-cols-2">
        <div class="card border-amber-300 bg-amber-50/70 p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-amber-900">
                        {{ $pendingOrders->count() }} pesanan menunggu disiapkan
                    </h2>
                    <p class="mt-0.5 text-sm text-amber-800">Unit sudah checkout, alat belum dialokasikan.</p>
                </div>
                <a href="{{ route('cssd.orders') }}" wire:navigate class="btn-primary shrink-0">Buka</a>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                @forelse ($pendingOrders->take(6) as $order)
                    <a href="{{ route('cssd.orders.show', $order->id) }}" wire:navigate wire:key="po-{{ $order->id }}"
                       class="rounded-lg bg-white px-3 py-1.5 text-xs ring-1 ring-amber-200 transition hover:ring-amber-400">
                        <span class="font-mono font-medium text-slate-700">{{ $order->order_number }}</span>
                        <span class="text-slate-500">· {{ $order->unit->name }}</span>
                    </a>
                @empty
                    <p class="text-sm text-amber-700">Tidak ada antrian.</p>
                @endforelse
            </div>
        </div>

        <div class="card border-sky-300 bg-sky-50/70 p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-sky-900">
                        {{ $pendingReturns->count() }} kiriman alat kotor menunggu konfirmasi
                    </h2>
                    <p class="mt-0.5 text-sm text-sky-800">Unit sudah mengirim, CSSD belum menyatakan terima.</p>
                </div>
                <a href="{{ route('cssd.returns') }}" wire:navigate class="btn-primary shrink-0">Buka</a>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                @forelse ($pendingReturns->take(6) as $shipment)
                    <a href="{{ route('cssd.returns') }}" wire:navigate wire:key="pr-{{ $shipment->id }}"
                       class="rounded-lg bg-white px-3 py-1.5 text-xs ring-1 ring-sky-200 transition hover:ring-sky-400">
                        <span class="font-mono font-medium text-slate-700">{{ $shipment->return_number }}</span>
                        <span class="text-slate-500">· {{ $shipment->unit->name }} · {{ $shipment->assets_count }} alat</span>
                    </a>
                @empty
                    <p class="text-sm text-sky-700">Tidak ada kiriman menggantung.</p>
                @endforelse
            </div>
        </div>
    </div>

    <h2 class="mb-3 text-sm font-semibold text-slate-700">Posisi Alat per Tahap</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($stageCounts as $stage)
            @php $alert = ($stage['alert'] ?? false) && $stage['value'] > 0; @endphp
            @if ($stage['route'])
                <a href="{{ route($stage['route']) }}" wire:navigate wire:key="sc-{{ $stage['label'] }}"
                   class="card p-5 transition hover:border-brand-300 hover:shadow">
                    <div class="text-sm text-slate-500">{{ $stage['label'] }}</div>
                    <div class="mt-1 text-3xl font-semibold text-slate-900">{{ $stage['value'] }}</div>
                </a>
            @else
                <div wire:key="sc-{{ $stage['label'] }}" class="card p-5">
                    <div class="text-sm text-slate-500">{{ $stage['label'] }}</div>
                    <div class="mt-1 text-3xl font-semibold {{ $alert ? 'text-red-600' : 'text-slate-900' }}">
                        {{ $stage['value'] }}
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>
