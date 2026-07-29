@props(['title', 'subtitle' => null])

<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endif
</div>
