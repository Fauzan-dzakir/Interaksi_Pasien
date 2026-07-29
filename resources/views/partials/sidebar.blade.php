@php
    /** @var bool $mobile Ditentukan oleh layout: true saat dirender sebagai laci di layar kecil. */
    $mobile = $mobile ?? false;

    $user = auth()->user();
    $unreadCount = $user->unreadNotifications()->count();

    // Menu dikelompokkan sesuai alur kerja tiap peran, bukan sekadar daftar panjang.
    $groups = match ($user->role) {
        \App\Enums\UserRole::Admin => [
            'Menu Utama' => [
                ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
                ['route' => 'notifications', 'label' => 'Notifikasi', 'icon' => 'bell', 'badge' => $unreadCount],
            ],
            'Pengawasan' => [
                ['route' => 'admin.audit', 'label' => 'Telusur Alat', 'icon' => 'search'],
                ['route' => 'admin.incomplete-sets', 'label' => 'Set Tidak Lengkap', 'icon' => 'warning'],
            ],
            'Master Data' => [
                ['route' => 'admin.units', 'label' => 'Unit', 'icon' => 'building'],
                ['route' => 'admin.items', 'label' => 'Katalog Alat', 'icon' => 'cube'],
                ['route' => 'admin.instrument-sets', 'label' => 'Set Alat', 'icon' => 'stack'],
                ['route' => 'admin.users', 'label' => 'Pengguna', 'icon' => 'users'],
            ],
            'Bantuan' => [
                ['route' => 'guide', 'label' => 'Panduan', 'icon' => 'book'],
            ],
        ],

        \App\Enums\UserRole::CssdStaff => [
            'Menu Utama' => [
                ['route' => 'cssd.dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
                ['route' => 'notifications', 'label' => 'Notifikasi', 'icon' => 'bell', 'badge' => $unreadCount],
            ],
            'Pesanan & Kiriman' => [
                ['route' => 'cssd.orders', 'label' => 'Pesanan', 'icon' => 'clipboard'],
                ['route' => 'cssd.returns', 'label' => 'Kiriman Kotor', 'icon' => 'inbox'],
            ],
            'Proses Alat' => [
                ['route' => 'cssd.scan', 'label' => 'Stasiun Scan', 'icon' => 'scan'],
                ['route' => 'cssd.new-barcode', 'label' => 'Barcode Baru', 'icon' => 'refresh'],
            ],
            'Inventaris' => [
                ['route' => 'cssd.stock', 'label' => 'Gudang', 'icon' => 'archive'],
                ['route' => 'cssd.batches', 'label' => 'Batch', 'icon' => 'squares'],
                ['route' => 'cssd.barcodes', 'label' => 'Cetak Label', 'icon' => 'tag'],
            ],
            'Dokumen' => [
                ['route' => 'cssd.reports', 'label' => 'Laporan', 'icon' => 'document'],
            ],
            'Bantuan' => [
                ['route' => 'guide', 'label' => 'Panduan', 'icon' => 'book'],
            ],
        ],

        \App\Enums\UserRole::Nakes => [
            'Menu Utama' => [
                ['route' => 'unit.dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
                ['route' => 'notifications', 'label' => 'Notifikasi', 'icon' => 'bell', 'badge' => $unreadCount],
            ],
            'Pesanan Cuci' => [
                ['route' => 'unit.orders.create', 'label' => 'Pesan Cuci', 'icon' => 'return'],
                ['route' => 'unit.orders', 'label' => 'Pesanan Saya', 'icon' => 'clipboard'],
                ['route' => 'unit.progress', 'label' => 'Progress Pencucian', 'icon' => 'refresh'],
            ],
            'Alat di Unit' => [
                ['route' => 'unit.usage', 'label' => 'Scan Pemakaian', 'icon' => 'camera'],
            ],
            'Bantuan' => [
                ['route' => 'guide', 'label' => 'Panduan', 'icon' => 'book'],
            ],
        ],
    };

    $initials = collect(explode(' ', $user->name))
        ->filter()
        ->take(2)
        ->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->join('');
@endphp

<div class="flex h-full flex-col border-r border-slate-200 bg-white">
    {{-- Identitas aplikasi --}}
    <div class="flex h-16 shrink-0 items-center gap-2.5 border-b border-slate-100 px-5">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-600 to-leaf-500 text-sm font-bold text-white">
            CS
        </span>
        <div class="min-w-0 leading-tight">
            <div class="truncate text-sm font-bold text-slate-900">SIM Alat CSSD</div>
            <div class="truncate text-[11px] text-slate-500">RS Kemenkes Surabaya</div>
        </div>

        @if ($mobile)
            <button type="button" @click="sidebarOpen = false"
                    class="ml-auto rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                    aria-label="Tutup menu">
                <x-icon name="close" class="h-5 w-5" />
            </button>
        @endif
    </div>

    {{-- Menu ter-grouping --}}
    <nav class="flex-1 space-y-5 overflow-y-auto px-3 py-4">
        @foreach ($groups as $groupLabel => $items)
            <div>
                <div class="nav-group-label mb-1.5">{{ $groupLabel }}</div>
                <div class="space-y-0.5">
                    @foreach ($items as $item)
                        @php
                            $active = request()->routeIs($item['route'])
                                || request()->routeIs($item['route'].'.*');
                        @endphp
                        <a href="{{ route($item['route']) }}" wire:navigate
                           @class(['nav-link', 'is-active' => $active])>
                            <x-icon :name="$item['icon']"
                                    @class(['text-brand-600' => $active, 'text-slate-400' => ! $active]) />
                            <span class="truncate">{{ $item['label'] }}</span>

                            @if (($item['badge'] ?? 0) > 0)
                                <span class="badge-count">{{ $item['badge'] > 9 ? '9+' : $item['badge'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    {{-- Kartu akun --}}
    <div class="shrink-0 border-t border-slate-100 p-3">
        <div class="flex items-center gap-2.5 rounded-xl bg-slate-50 p-2.5">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">
                {{ $initials }}
            </span>
            <div class="min-w-0 flex-1 leading-tight">
                <div class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</div>
                <div class="truncate text-[11px] text-slate-500">
                    {{ $user->role->label() }}@if ($user->unit) &middot; {{ $user->unit->name }} @endif
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Keluar" aria-label="Keluar"
                        class="rounded-lg p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                    <x-icon name="logout" class="h-5 w-5" />
                </button>
            </form>
        </div>
    </div>
</div>
