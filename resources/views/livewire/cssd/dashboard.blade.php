<div wire:poll.20s>
    <x-page-header title="Dashboard CSSD" subtitle="Beban kerja dan posisi alat di setiap zona.">
        <x-slot:actions>
            {{-- Cari langsung berdasarkan alat/kode label — tidak perlu lewat nomor order dulu. --}}
            <form method="GET" action="{{ route('cssd.audit') }}" class="flex items-center">
                <input type="search" name="q" class="field-input !h-auto w-40 sm:w-56"
                       placeholder="Cari kode/nama alat…">
                <button type="submit" class="btn-secondary ml-2 shrink-0">Cari Alat</button>
            </form>
            <a href="{{ route('cssd.scan') }}" wire:navigate class="btn-primary">Buka Stasiun Scan</a>
        </x-slot:actions>
    </x-page-header>

    @php $citoPending = $pendingIntake->where('is_cito', true); @endphp
    @if ($citoPending->isNotEmpty())
        <div class="mb-5 card border-red-300 bg-red-50/80 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-red-900">
                        {{ $citoPending->count() }} order CITO menunggu — didahulukan
                    </h2>
                    <p class="mt-0.5 text-sm text-red-800">
                        Segera data dan proses lebih dulu dari antrian biasa.
                    </p>
                </div>
                <a href="{{ route('cssd.orders', ['status' => \App\Enums\DeliveryOrderStatus::PendingCssdIntake->value]) }}"
                   wire:navigate class="btn-primary !bg-red-600 hover:!bg-red-700 shrink-0">Proses Sekarang</a>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($citoPending as $order)
                    <a href="{{ route('cssd.orders.show', $order->id) }}" wire:navigate wire:key="ci-{{ $order->id }}"
                       class="rounded-lg bg-white px-3 py-1.5 text-xs ring-1 ring-red-300 transition hover:ring-red-500">
                        <span class="font-mono font-medium text-slate-700">{{ $order->order_number }}</span>
                        <span class="text-slate-500">· {{ $order->originUnit->name }}</span>
                        @if ($order->needed_at)
                            <span class="font-semibold text-red-600"> · butuh {{ $order->needed_at->format('d/m H:i') }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if ($pendingIntake->isNotEmpty())
        <div class="mb-5 card border-amber-300 bg-amber-50/70 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-amber-900">
                        {{ $pendingIntake->count() }} order menunggu pendataan
                    </h2>
                    <p class="mt-0.5 text-sm text-amber-800">
                        Unit belum bisa melihat rincian alatnya sampai pendataan disimpan.
                    </p>
                </div>
                <a href="{{ route('cssd.orders') }}" wire:navigate class="btn-primary shrink-0">Lihat Antrian</a>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($pendingIntake->take(6) as $order)
                    <a href="{{ route('cssd.orders.show', $order->id) }}" wire:navigate wire:key="pi-{{ $order->id }}"
                       class="rounded-lg bg-white px-3 py-1.5 text-xs ring-1 ring-amber-200 transition hover:ring-amber-400">
                        <span class="font-mono font-medium text-slate-700">{{ $order->order_number }}</span>
                        <span class="text-slate-500">· {{ $order->originUnit->name }}</span>
                        @if ($order->is_cito)
                            <span class="ml-1 rounded bg-red-100 px-1 py-0.5 text-[10px] font-bold text-red-700">CITO</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($zoneTotals as $row)
            <div wire:key="zt-{{ $row['zone']->value }}" class="card p-5">
                <div class="text-sm font-medium text-slate-700">{{ $row['zone']->label() }}</div>
                <div class="mt-1 text-3xl font-semibold text-slate-900">{{ $row['count'] }}</div>
                <div class="mt-1 text-xs text-slate-400">{{ $row['zone']->description() }}</div>
            </div>
        @endforeach

        <a href="{{ route('cssd.distribution') }}" wire:navigate
           class="card p-5 transition hover:border-teal-300 hover:shadow">
            <div class="text-sm font-medium text-slate-700">Siap Diserahkan</div>
            <div class="mt-1 text-3xl font-semibold text-emerald-700">{{ $readyForDistribution }}</div>
            <div class="mt-1 text-xs text-slate-400">
                {{ $pendingPickups }} serah terima menunggu konfirmasi unit
            </div>
        </a>

        <a href="{{ route('cssd.distribution') }}" wire:navigate
           @class([
               'card p-5 transition hover:border-red-300 hover:shadow',
               'border-red-300 bg-red-50/40' => $expiredSterileCount > 0,
           ])>
            <div class="text-sm font-medium text-slate-700">Kedaluwarsa Steril</div>
            <div @class([
                'mt-1 text-3xl font-semibold',
                'text-red-700' => $expiredSterileCount > 0,
                'text-slate-300' => $expiredSterileCount === 0,
            ])>{{ $expiredSterileCount }}</div>
            <div class="mt-1 text-xs text-slate-400">
                Alat di gudang, masa sterilnya sudah lewat — perlu sterilisasi ulang
            </div>
        </a>
    </div>

    <div class="mt-5 card">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-900">Rincian per Tahap</h2>
            <p class="mt-0.5 text-xs text-slate-500">Menunjukkan di tahap mana antrian sedang menumpuk.</p>
        </div>

        <div class="grid gap-px bg-slate-200 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($stageBreakdown as $row)
                <div wire:key="sb-{{ $row['status']->value }}" class="bg-white p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="truncate text-sm text-slate-600">{{ $row['status']->label() }}</div>
                            <div class="mt-0.5 text-xs text-slate-400">{{ $row['status']->zone()->label() }}</div>
                        </div>
                        <div @class([
                            'shrink-0 text-2xl font-semibold',
                            'text-slate-900' => $row['count'] > 0,
                            'text-slate-300' => $row['count'] === 0,
                        ])>{{ $row['count'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
