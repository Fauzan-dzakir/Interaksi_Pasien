@props(['set', 'label' => 'Lihat isi set'])

@php
    $contents = $set?->items ?? collect();
    $totalPcs = $contents->sum(fn ($i) => $i->pivot->quantity);
@endphp

@if ($contents->isNotEmpty())
    {{--
        Dropdown isi set. Ditutup secara bawaan supaya daftar tetap ringkas,
        tapi petugas dan unit bisa memastikan isi set tanpa berpindah halaman.
    --}}
    <div x-data="{ open: false }" {{ $attributes->class(['text-sm']) }}>
        <button type="button" x-on:click="open = !open"
                class="inline-flex items-center gap-1 text-xs font-medium text-brand-700 transition hover:text-brand-800">
            <svg class="h-3.5 w-3.5 transition" :class="open && 'rotate-90'"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
            <span x-text="open ? 'Tutup isi set' : @js($label.' ('.$contents->count().' jenis, '.$totalPcs.' pcs)')"></span>
        </button>

        <div x-show="open" x-cloak class="mt-2 rounded-lg border border-slate-200 bg-slate-50 p-2">
            <ul class="divide-y divide-slate-200">
                @foreach ($contents as $item)
                    <li class="flex items-center gap-3 py-1.5">
                        <x-item-thumb :photo="$item->photo_path" :name="$item->name" size="sm" />

                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm text-slate-800">{{ $item->name }}</div>
                            <div class="font-mono text-xs text-slate-400">{{ $item->code }}</div>
                        </div>

                        <span class="shrink-0 rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">
                            {{ $item->pivot->quantity }} pcs
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
