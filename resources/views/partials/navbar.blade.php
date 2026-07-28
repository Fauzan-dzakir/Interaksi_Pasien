@php
    $user = auth()->user();

    $nav = match ($user->role) {
        \App\Enums\UserRole::Admin => [
            ['route' => 'admin.dashboard', 'label' => 'Dashboard'],
            ['route' => 'admin.audit', 'label' => 'Telusur Alat'],
            ['route' => 'admin.units', 'label' => 'Unit'],
            ['route' => 'admin.items', 'label' => 'Katalog Alat'],
            ['route' => 'admin.instrument-sets', 'label' => 'Set Alat'],
            ['route' => 'admin.users', 'label' => 'Pengguna'],
        ],
        \App\Enums\UserRole::CssdStaff => [
            ['route' => 'cssd.dashboard', 'label' => 'Dashboard'],
            ['route' => 'cssd.orders', 'label' => 'Order Masuk'],
            ['route' => 'cssd.scan', 'label' => 'Stasiun Scan'],
            ['route' => 'cssd.barcode-replacement', 'label' => 'Ganti Barcode'],
            ['route' => 'cssd.labels', 'label' => 'Cetak Label'],
            ['route' => 'cssd.distribution', 'label' => 'Distribusi'],
        ],
        \App\Enums\UserRole::Nakes => [
            ['route' => 'unit.dashboard', 'label' => 'Dashboard'],
            ['route' => 'unit.orders', 'label' => 'Order Saya'],
            ['route' => 'unit.pickups', 'label' => 'Penerimaan'],
        ],
    };

    $unreadCount = $user->unreadNotifications()->count();
@endphp

<nav x-data="{ open: false }" class="border-b border-slate-200 bg-white shadow-sm">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-6">
                <a href="{{ route($user->role->homeRoute()) }}" class="flex shrink-0 items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-600 text-sm font-bold text-white">CS</span>
                    <span class="hidden text-sm font-semibold leading-tight text-slate-900 xl:block">
                        SIM Alat CSSD<br>
                        <span class="text-xs font-normal text-slate-500">RS Kemenkes Surabaya</span>
                    </span>
                </a>

                <div class="hidden items-center gap-0.5 lg:flex">
                    @foreach ($nav as $link)
                        <a href="{{ route($link['route']) }}" wire:navigate
                           @class([
                               'whitespace-nowrap rounded-md px-2.5 py-2 text-sm font-medium transition',
                               'bg-teal-50 text-teal-700' => request()->routeIs($link['route']),
                               'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs($link['route']),
                           ])>
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-3">
                <a href="{{ route('notifications') }}" wire:navigate
                   class="relative rounded-md p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
                   title="Notifikasi">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    @if ($unreadCount > 0)
                        <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white">
                            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                        </span>
                    @endif
                </a>

                <div class="hidden text-right sm:block">
                    <div class="text-sm font-medium text-slate-900">{{ $user->name }}</div>
                    <div class="text-xs text-slate-500">
                        {{ $user->role->label() }}@if ($user->unit) &middot; {{ $user->unit->name }} @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Keluar
                    </button>
                </form>

                <button type="button" @click="open = !open"
                        class="rounded-md p-2 text-slate-500 hover:bg-slate-100 lg:hidden">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div x-show="open" x-cloak class="border-t border-slate-200 lg:hidden">
        <div class="space-y-1 px-4 py-3">
            @foreach ($nav as $link)
                <a href="{{ route($link['route']) }}" wire:navigate
                   @class([
                       'block rounded-md px-3 py-2 text-sm font-medium',
                       'bg-teal-50 text-teal-700' => request()->routeIs($link['route']),
                       'text-slate-600 hover:bg-slate-100' => ! request()->routeIs($link['route']),
                   ])>
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</nav>
