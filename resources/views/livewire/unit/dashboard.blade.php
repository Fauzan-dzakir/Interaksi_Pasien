<div wire:poll.15s>
    <x-page-header :title="'Dashboard ' . auth()->user()->unit->name"
                   subtitle="Posisi alat terbaru — tidak perlu telepon CSSD lagi.">
        <x-slot:actions>
            <a href="{{ route('unit.orders.create') }}" wire:navigate class="btn-primary">+ Buat Order</a>
        </x-slot:actions>
    </x-page-header>

    {{--
        Hal yang butuh tindakan unit ditaruh paling atas. SENGAJA hanya berdasarkan
        $awaitingConfirm (jumlah Pickup yang benar-benar menunggu konfirmasi) — itu
        persis yang ditampilkan halaman "Lihat Penerimaan". Dulu ada fallback ke
        $readyCount (hitungan status alat mentah) yang bisa berbeda dari isi
        Penerimaan kalau datanya tidak sinkron, membuat banner ini menunjukkan
        angka yang salah dan mengarah ke halaman yang ternyata kosong.
    --}}
    @if ($awaitingConfirm > 0)
        <div class="mb-5 flex flex-wrap items-center gap-3 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-200 font-semibold text-emerald-900">
                {{ $awaitingConfirm }}
            </span>
            <span class="text-sm text-emerald-900">
                Ada alat steril yang siap diterima unit Anda.
            </span>
            <a href="{{ route('unit.pickups') }}" wire:navigate class="btn-primary ml-auto shrink-0">Lihat Penerimaan</a>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($trackedZones as $row)
            <button wire:click="$set('zoneFilter', '{{ $zoneFilter === $row['zone']->value ? '' : $row['zone']->value }}')"
                    wire:key="zone-{{ $row['zone']->value }}"
                    @class([
                        'card p-5 text-left transition',
                        'ring-2 ring-teal-500' => $zoneFilter === $row['zone']->value,
                        'hover:border-teal-300 hover:shadow' => $zoneFilter !== $row['zone']->value,
                    ])>
                <div class="text-sm font-medium text-slate-700">{{ $row['zone']->label() }}</div>
                <div class="mt-1 text-3xl font-semibold text-slate-900">{{ $row['count'] }}</div>
                <div class="mt-1 text-xs text-slate-400">{{ $row['zone']->description() }}</div>
            </button>
        @endforeach

        <div class="card p-5">
            <div class="text-sm font-medium text-slate-700">Di Unit Ini</div>
            <div class="mt-1 text-3xl font-semibold text-slate-900">{{ $atUnitCount }}</div>
            <div class="mt-1 text-xs text-slate-400">Sudah diambil / sedang dipakai</div>
        </div>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-5 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">
                        Alat Sedang Berjalan
                        @if ($zoneFilter || $statusFilter || $search)
                            <span class="font-normal text-slate-500">— difilter</span>
                        @endif
                    </h2>
                    <p class="mt-0.5 text-xs text-slate-500">
                        {{ $totalActive }} alat aktif · diperbarui otomatis tiap 15 detik
                    </p>
                </div>
                @if ($zoneFilter || $statusFilter || $search)
                    <button wire:click="resetFilters" class="text-xs font-medium text-teal-700 hover:text-teal-800">
                        Tampilkan semua
                    </button>
                @endif
            </div>

            <div class="flex flex-wrap gap-3 border-b border-slate-200 p-4">
                <input wire:model.live.debounce.300ms="search" type="search"
                       placeholder="Cari kode label / nama alat…" class="field-input sm:max-w-xs">

                <div class="sm:max-w-[14rem]">
                    <x-tom-select wire-model="statusFilter" placeholder="Semua status" search-placeholder="Cari status…">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                        @endforeach
                    </x-tom-select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th>Kode Label</th>
                            <th>Alat / Set</th>
                            <th>Zona</th>
                            <th>Status Rinci</th>
                            <th>Diperbarui</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($batches as $batch)
                            <tr wire:key="db-{{ $batch->id }}" class="cursor-pointer"
                                onclick="window.location='{{ route('batches.show', $batch->id) }}'">
                                <td class="font-mono text-xs text-slate-600">{{ $batch->public_code }}</td>
                                <td>
                                    <div class="font-medium text-slate-900">{{ $batch->displayName() }}</div>
                                    <div class="text-xs text-slate-400">{{ $batch->displayQuantity() }}</div>
                                </td>
                                <td><x-state-pill :state="$batch->zone()" /></td>
                                <td class="text-slate-600">{{ $batch->status->label() }}</td>
                                <td class="text-xs text-slate-500">{{ $batch->status_changed_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <x-empty-state colspan="5" title="Belum ada alat sedang diproses"
                                           description="Buat order pengiriman untuk mulai melacak alat." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-5">
            <div class="card p-5">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">Order Berjalan</h2>
                @forelse ($recentOrders as $order)
                    <a href="{{ route('unit.orders.show', $order->id) }}" wire:navigate wire:key="ro-{{ $order->id }}"
                       class="mb-2 block rounded-lg border border-slate-200 px-3 py-2 transition hover:border-teal-300 hover:bg-teal-50/40">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono text-xs font-medium text-slate-700">{{ $order->order_number }}</span>
                            <span class="text-xs text-slate-400">{{ $order->sent_at->format('d/m H:i') }}</span>
                        </div>
                        <div class="mt-1"><x-state-pill :state="$order->status" /></div>
                    </a>
                @empty
                    <p class="text-sm text-slate-400">Tidak ada order berjalan.</p>
                @endforelse

                @if ($awaitingIntake > 0)
                    <p class="mt-2 text-xs text-amber-700">
                        {{ $awaitingIntake }} order masih menunggu pendataan CSSD.
                    </p>
                @endif
            </div>

            <div class="card border-sky-200 bg-sky-50/50 p-5">
                <h2 class="text-sm font-semibold text-sky-900">Arti tiap zona</h2>
                <ul class="mt-2 space-y-2 text-sm text-sky-800">
                    @foreach ($trackedZones as $row)
                        <li>
                            <span class="font-medium">{{ $row['zone']->label() }}</span> —
                            {{ $row['zone']->description() }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
