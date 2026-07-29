<div wire:poll.30s>
    <x-page-header title="Dashboard Admin" subtitle="Ringkasan operasional dan master data SIM Alat CSSD.">
        <x-slot:actions>
            <a href="{{ route('admin.audit') }}" wire:navigate class="btn-primary">Telusur Alat</a>
        </x-slot:actions>
    </x-page-header>

    <h2 class="mb-3 text-sm font-semibold text-slate-700">Operasional</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($operationalStats as $stat)
            @php $alert = ($stat['alert'] ?? false) && $stat['value'] > 0; @endphp
            @if ($stat['route'])
                <a href="{{ route($stat['route']) }}" wire:navigate wire:key="op-{{ $stat['label'] }}"
                   class="card p-5 transition hover:border-brand-300 hover:shadow">
                    <div class="text-sm text-slate-500">{{ $stat['label'] }}</div>
                    <div class="mt-1 text-3xl font-semibold {{ $alert ? 'text-red-600' : 'text-slate-900' }}">{{ $stat['value'] }}</div>
                </a>
            @else
                <div wire:key="op-{{ $stat['label'] }}" class="card p-5">
                    <div class="text-sm text-slate-500">{{ $stat['label'] }}</div>
                    <div class="mt-1 text-3xl font-semibold {{ $alert ? 'text-red-600' : 'text-slate-900' }}">{{ $stat['value'] }}</div>
                </div>
            @endif
        @endforeach
    </div>

    <h2 class="mb-3 mt-6 text-sm font-semibold text-slate-700">Posisi Aset per Zona</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($zoneTotals as $row)
            <div wire:key="az-{{ $row['zone']->value }}" class="card p-5">
                <div class="text-sm font-medium text-slate-700">{{ $row['zone']->label() }}</div>
                <div class="mt-1 text-3xl font-semibold text-slate-900">{{ $row['count'] }}</div>
                <div class="mt-1 text-xs text-slate-400">{{ $row['zone']->description() }}</div>
            </div>
        @endforeach
    </div>

    <h2 class="mb-3 mt-6 text-sm font-semibold text-slate-700">Master Data</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($masterStats as $stat)
            <a href="{{ route($stat['route']) }}" wire:navigate wire:key="ms-{{ $stat['label'] }}"
               class="card p-5 transition hover:border-brand-300 hover:shadow">
                <div class="text-sm text-slate-500">{{ $stat['label'] }}</div>
                <div class="mt-1 text-3xl font-semibold text-slate-900">{{ $stat['value'] }}</div>
            </a>
        @endforeach
    </div>

    <div class="mt-6 card p-5">
        <h2 class="mb-3 text-sm font-semibold text-slate-900">Pengguna per Peran</h2>
        <ul class="divide-y divide-slate-100">
            @foreach ($usersByRole as $row)
                <li class="flex items-center justify-between py-2 text-sm">
                    <span class="text-slate-600">{{ $row['label'] }}</span>
                    <span class="font-semibold text-slate-900">{{ $row['count'] }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>
