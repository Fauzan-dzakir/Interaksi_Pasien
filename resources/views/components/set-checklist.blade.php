@props(['checks'])

<div class="rounded-md border border-slate-200 bg-white p-2.5">
    <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Kelengkapan isi set</p>
    <ul class="space-y-1">
        @foreach ($checks as $check)
            <li class="flex items-start gap-2 text-xs">
                @if ($check->is_present)
                    <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                        <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                    </span>
                @else
                    <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                        <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </span>
                @endif
                <span class="{{ $check->is_present ? 'text-slate-700' : 'font-medium text-red-700' }}">
                    {{ $check->item?->name ?? '(alat terhapus)' }}
                </span>
                @if ($check->note)
                    <span class="text-slate-400">— {{ $check->note }}</span>
                @endif
            </li>
        @endforeach
    </ul>
</div>
