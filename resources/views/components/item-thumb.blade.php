@props(['photo' => null, 'name' => '', 'size' => 'md'])

@php
    $box = match ($size) {
        'sm' => 'h-10 w-10',
        'lg' => 'h-full w-full',
        default => 'h-14 w-14',
    };
@endphp

{{-- Foto contoh alat. Bila belum diunggah, tampilkan ikon netral agar tata letak tetap rapi. --}}
<div {{ $attributes->class([
    'flex shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-100 ring-1 ring-slate-200',
    $box,
]) }}>
    @if ($photo)
        <img src="{{ Storage::url($photo) }}" alt="Foto {{ $name }}" class="h-full w-full object-cover">
    @else
        <svg class="h-1/2 w-1/2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M3 16.5l4.5-4.5a2 2 0 012.8 0l3.2 3.2 2-2a2 2 0 012.8 0L21 16.5M4 5h16a1 1 0 011 1v12a1 1 0 01-1 1H4a1 1 0 01-1-1V6a1 1 0 011-1zm5 4a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
        </svg>
    @endif
</div>
