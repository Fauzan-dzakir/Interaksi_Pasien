<div wire:poll.15s x-data="{ tab: 'table' }">
    <x-page-header title="Pendataan Alat di Unit"
                   subtitle="Informasi alat real-time di unit Anda — tandai alat yang sudah dipakai." />

    @if ($feedback)
        <p @class([
            'mb-4 rounded-lg border px-4 py-3 text-sm',
            'border-emerald-300 bg-emerald-50 text-emerald-700' => $feedbackType === 'success',
            'border-red-300 bg-red-50 text-red-600' => $feedbackType === 'error',
            'border-slate-300 bg-slate-50 text-slate-500' => $feedbackType === 'info',
        ])>{{ $feedback }}</p>
    @endif

    {{-- Tab switcher — cuma tampil di HP, di desktop kedua bagian digabung sekaligus. --}}
    <div class="mb-4 flex gap-2 lg:hidden">
        <button type="button" @click="tab = 'table'"
                :class="tab === 'table' ? 'bg-teal-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200'"
                class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition">Daftar Alat</button>
        <button type="button" @click="tab = 'scan'"
                :class="tab === 'scan' ? 'bg-teal-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200'"
                class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition">Scan Alat</button>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="lg:col-span-2" :class="tab === 'table' ? 'block' : 'hidden lg:block'">
            <div class="card">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">Alat Belum Dipakai ({{ $batches->count() }})</h2>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Alat hasil pengambilan dari CSSD yang masih ada di unit ini. Begitu ditandai
                        dipakai, alat pindah ke daftar "Alat di Unit Ini" pada halaman Buat Order.
                    </p>
                </div>

                @if ($batches->isEmpty())
                    <div class="px-5 py-10 text-center text-sm text-slate-400">
                        Belum ada alat yang diambil dari CSSD — kalau ini pengiriman pertama, itu wajar.
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($batches as $batch)
                            <div wire:key="inv-{{ $batch->id }}" class="px-5 py-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <span class="font-mono text-xs text-slate-500">{{ $batch->public_code }}</span>
                                        <div class="font-medium text-slate-900">{{ $batch->displayName() }}</div>
                                    </div>

                                    @if ($batch->batch_type === \App\Enums\BatchType::Individual)
                                        <button wire:click="toggleIndividualUsage({{ $batch->id }})"
                                                class="btn-primary !px-3 !py-1.5">Tandai Dipakai</button>
                                    @endif
                                </div>

                                @if ($batch->batch_type === \App\Enums\BatchType::Set && $batch->instrumentSet)
                                    @php $usedCount = $batch->usageMarks->where('is_used', true)->count(); @endphp
                                    <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50/50 p-3">
                                        <p class="mb-2 text-xs font-semibold text-slate-500">Tandai per alat dalam set ini</p>
                                        <div class="space-y-1.5">
                                            @foreach ($batch->instrumentSet->items as $setItem)
                                                @php
                                                    $mark = $batch->usageMarks->firstWhere('item_id', $setItem->id);
                                                    $isUsed = $mark?->is_used ?? false;
                                                @endphp
                                                <label wire:key="usage-{{ $batch->id }}-{{ $setItem->id }}"
                                                       class="flex items-center gap-2 text-sm text-slate-700">
                                                    <input type="checkbox" @checked($isUsed)
                                                           wire:click="toggleSetItemMark({{ $batch->id }}, {{ $setItem->id }})"
                                                           class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                                    {{ $setItem->name }}
                                                    <span class="text-xs text-slate-400">({{ $setItem->pivot->quantity }}x)</span>
                                                </label>
                                            @endforeach
                                        </div>

                                        <button type="button" wire:click="confirmSetUsage({{ $batch->id }})"
                                                @disabled($usedCount === 0)
                                                wire:confirm="Konfirmasi {{ $usedCount }} alat dalam set ini sedang dipakai? Set akan pindah ke daftar alat kotor."
                                                class="btn-primary mt-3 w-full !py-1.5 text-xs disabled:cursor-not-allowed disabled:opacity-40">
                                            {{ $usedCount > 0 ? "Konfirmasi Pemakaian ({$usedCount} alat)" : 'Centang alat yang dipakai dulu' }}
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div :class="tab === 'scan' ? 'block' : 'hidden lg:block'">
            <div class="card p-5">
                <x-scan-input submit="scanUsage" label="Scan alat yang mulai dipakai"
                              placeholder="CSSD-XXXXXXXX" />
                <p class="mt-3 text-xs text-slate-500">
                    Scan barcode satu alat (atau satu set) untuk langsung menandainya "sedang dipakai".
                    Untuk menandai sebagian isi satu set saja, gunakan checklist di tabel sebelah.
                </p>
            </div>
        </div>
    </div>
</div>
