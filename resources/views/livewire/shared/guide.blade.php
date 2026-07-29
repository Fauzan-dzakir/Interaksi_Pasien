<div>
    <x-page-header title="Panduan Penggunaan"
                   :subtitle="'Alur kerja untuk peran ' . $role->label() . '.'">
        <x-slot:actions>
            <a href="{{ route($role->homeRoute()) }}" wire:navigate class="btn-secondary">Ke Dashboard</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 overflow-hidden rounded-xl bg-gradient-to-r from-brand-700 to-brand-500 p-6 text-white">
        <h2 class="text-lg font-semibold">SIM Alat CSSD</h2>
        <p class="mt-1 max-w-3xl text-sm text-brand-50">
            Sistem ini menggantikan pencatatan kertas dengan pelacakan digital. Setiap alat punya
            barcode, setiap perpindahan tercatat lengkap dengan nama petugas dan jamnya, sehingga
            posisi terakhir alat selalu bisa dibuktikan saat audit.
        </p>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <h2 class="mb-3 text-sm font-semibold text-slate-700">Langkah Kerja Anda</h2>

            <ol class="space-y-3">
                @foreach ($steps as $index => $step)
                    <li wire:key="step-{{ $index }}" class="card p-5">
                        <div class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">
                                {{ $index + 1 }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <h3 class="font-semibold text-slate-900">{{ $step['title'] }}</h3>
                                <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $step['body'] }}</p>

                                @if ($step['route'])
                                    <a href="{{ route($step['route']) }}" wire:navigate
                                       class="btn-secondary mt-3 !px-3 !py-1.5">{{ $step['action'] }}</a>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>

        <div class="space-y-5">
            <div class="card border-leaf-200 bg-leaf-50/60 p-5">
                <h2 class="text-sm font-semibold text-leaf-900">Hal Penting untuk Diingat</h2>
                <ul class="mt-3 space-y-2.5">
                    @foreach ($tips as $tip)
                        <li class="flex gap-2 text-sm text-leaf-900">
                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-leaf-500"></span>
                            <span>{{ $tip }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card p-5">
                <h2 class="mb-2 text-sm font-semibold text-slate-900">Arti Warna Status</h2>
                <ul class="space-y-2">
                    @foreach (\App\Enums\ZoneBucket::cases() as $zone)
                        <li class="flex items-center justify-between gap-3">
                            <x-state-pill :state="$zone" />
                            <span class="text-right text-xs text-slate-500">{{ $zone->description() }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card p-5">
                <h2 class="mb-2 text-sm font-semibold text-slate-900">Butuh Bantuan?</h2>
                <p class="text-sm text-slate-600">
                    Bila alat tidak ditemukan sistem atau ada pemindaian yang ditolak padahal seharusnya benar,
                    hubungi Admin untuk melakukan koreksi. Setiap koreksi tercatat beserta alasannya.
                </p>
            </div>
        </div>
    </div>
</div>
