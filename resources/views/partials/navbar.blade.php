@php
    $user = auth()->user();

    $nav = match ($user->role) {
        \App\Enums\UserRole::Admin => [
            'Utama' => [
                ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
                ['route' => 'admin.audit', 'label' => 'Telusur Alat', 'icon' => 'search'],
            ],
            'Master Data' => [
                ['route' => 'admin.units', 'label' => 'Unit', 'icon' => 'building'],
                ['route' => 'admin.items', 'label' => 'Katalog Alat', 'icon' => 'box'],
                ['route' => 'admin.instrument-sets', 'label' => 'Set Alat', 'icon' => 'layers'],
                ['route' => 'admin.pickup-locations', 'label' => 'Lokasi Pengambilan', 'icon' => 'pin'],
                ['route' => 'admin.users', 'label' => 'Pengguna', 'icon' => 'users'],
            ],
            'Bantuan' => [
                ['route' => 'guide', 'label' => 'Panduan Penggunaan', 'icon' => 'help'],
            ],
        ],
        \App\Enums\UserRole::CssdStaff => [
            'Utama' => [
                ['route' => 'cssd.dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
                ['route' => 'cssd.audit', 'label' => 'Telusur Alat', 'icon' => 'search'],
            ],
            'Transaksi & Proses' => [
                ['route' => 'cssd.orders', 'label' => 'Order Masuk', 'icon' => 'inbox'],
                ['route' => 'cssd.scan', 'label' => 'Stasiun Scan', 'icon' => 'scan'],
                ['route' => 'cssd.barcode-replacement', 'label' => 'Ganti Barcode', 'icon' => 'refresh'],
                ['route' => 'cssd.labels', 'label' => 'Cetak Label', 'icon' => 'printer'],
                ['route' => 'cssd.distribution', 'label' => 'Distribusi', 'icon' => 'truck'],
            ],
            'Bantuan' => [
                ['route' => 'guide', 'label' => 'Panduan Penggunaan', 'icon' => 'help'],
            ],
        ],
        \App\Enums\UserRole::Nakes => [
            'Utama' => [
                ['route' => 'unit.dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
            ],
            'Transaksi' => [
                ['route' => 'unit.orders', 'label' => 'Order Saya', 'icon' => 'clipboard'],
                ['route' => 'unit.pickups', 'label' => 'Penerimaan', 'icon' => 'download'],
                ['route' => 'unit.inventory', 'label' => 'Pendataan Alat di Unit', 'icon' => 'box'],
            ],
            'Bantuan' => [
                ['route' => 'guide', 'label' => 'Panduan Penggunaan', 'icon' => 'help'],
            ],
        ],
    };

    $unreadCount = $user->unreadNotifications()->count();

    $icons = [
        'home' => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75',
        'search' => 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z',
        'building' => 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75',
        'box' => 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
        'layers' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
        'pin' => 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0z',
        'pin2' => 'M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z',
        'users' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
        'inbox' => 'M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.86M2.25 13.5V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18v-4.5M2.25 13.5V9.75A2.25 2.25 0 014.5 7.5h15a2.25 2.25 0 012.25 2.25v3.75',
        'scan' => 'M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M20.25 3.75v4.5m0-4.5h-4.5m4.5 0L15 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 20.25v-4.5m0 4.5h-4.5m4.5 0L15 15',
        'refresh' => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99',
        'printer' => 'M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0M6.34 18l-.229 2.523A1.125 1.125 0 007.231 21.75h9.538a1.125 1.125 0 001.12-1.227L17.66 18M17.28 13.925L17.66 18m0 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659',
        'truck' => 'M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12',
        'clipboard' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 00-9-9H8.25z',
        'download' => 'M12 3v13.5m0 0l-4.5-4.5m4.5 4.5l4.5-4.5M4.5 19.5h15',
        'help' => 'M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z',
    ];
@endphp

<div x-data="{ open: false }" class="print:hidden md:contents">
    {{-- Top bar mobile — selalu tampil di layar sempit, jadi pemicu buka/tutup sidebar --}}
    <div class="flex items-center justify-between border-b border-slate-200 bg-white p-3 md:hidden">
        <button type="button" @click="open = ! open" class="rounded-md p-2 text-slate-500 hover:bg-slate-100" aria-label="Buka/tutup menu">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <a href="{{ route($user->role->homeRoute()) }}" class="flex shrink-0 items-center gap-2">
            <img src="{{ asset('images/kemenkes-logo.png') }}" alt="RS Kemenkes Surabaya" class="h-7 w-auto shrink-0">
            <span class="text-sm font-semibold leading-tight text-slate-900">SIM Alat CSSD</span>
        </a>
        <span class="w-9"></span>
    </div>

    {{-- Backdrop — hanya dipakai di mobile saat sidebar dibuka --}}
    <div x-show="open" x-cloak x-on:click="open = false"
         x-transition:enter="transition-opacity ease-linear duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-30 bg-slate-900/50 md:hidden"></div>

    {{-- Sidebar: overlay geser di mobile (toggle), kolom tetap (fixed) di layar desktop/laptop --}}
    <aside :class="open ? 'translate-x-0' : '-translate-x-full'"
           class="fixed left-0 top-0 z-40 flex h-dvh w-72 shrink-0 transform flex-col overflow-hidden border-r border-slate-200 bg-white shadow-xl transition-transform duration-200 ease-in-out md:sticky md:top-0 md:h-screen md:z-auto md:w-64 md:translate-x-0 md:shadow-none">

        <div class="flex items-center justify-between border-b border-slate-200 p-4">
            <a href="{{ route($user->role->homeRoute()) }}" class="flex min-w-0 shrink items-center gap-2.5">
                <img src="{{ asset('images/kemenkes-logo.png') }}" alt="RS Kemenkes Surabaya" class="h-9 w-auto shrink-0">
                <span class="min-w-0 text-left text-sm font-semibold leading-tight text-slate-900">
                    SIM Alat CSSD
                </span>
            </a>
            <button type="button" @click="open = false" class="rounded-md p-2 text-slate-500 hover:bg-slate-100 md:hidden" aria-label="Tutup menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto p-4">
            @foreach ($nav as $groupName => $links)
                <div>
                    <h3 class="px-2 mb-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $groupName }}</h3>
                    <div class="space-y-1">
                        @foreach ($links as $link)
                            <a href="{{ route($link['route']) }}" wire:navigate @click="open = false"
                               @class([
                                   'flex items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium transition',
                                   'bg-teal-50 text-teal-700' => request()->routeIs($link['route']),
                                   'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs($link['route']),
                               ])>
                                <svg class="h-4.5 w-4.5 shrink-0" style="width:1.125rem;height:1.125rem" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icons[$link['icon']] ?? $icons['box'] }}"/>
                                    @if ($link['icon'] === 'pin')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icons['pin2'] }}"/>
                                    @endif
                                </svg>
                                <span class="truncate">{{ $link['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        <div class="border-t border-slate-200 p-4">
            <div class="flex items-center justify-between mb-4">
                <div class="min-w-0">
                    <div class="truncate text-sm font-medium text-slate-900">{{ $user->name }}</div>
                    <div class="truncate text-xs text-slate-500">
                        {{ $user->role->label() }}@if ($user->unit) &middot; {{ $user->unit->name }} @endif
                    </div>
                </div>

                <a href="{{ route('notifications') }}" wire:navigate @click="open = false"
                   class="relative shrink-0 rounded-md p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
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
            </div>

            <form method="POST" action="{{ route('logout') }}" x-data>
                @csrf
                <button type="submit" @click="if(!confirm('Apakah Anda yakin ingin keluar?')) { $event.preventDefault(); }"
                        class="w-full text-center rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 hover:text-red-600 hover:border-red-300">
                    Keluar Akun
                </button>
            </form>
        </div>
    </aside>
</div>
