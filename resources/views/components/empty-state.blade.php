@props(['title', 'description' => null, 'colspan' => null])

@if ($colspan)
    <tr>
        <td colspan="{{ $colspan }}" class="px-4 py-12 text-center">
            <div class="text-sm font-medium text-slate-600">{{ $title }}</div>
            @if ($description)
                <div class="mt-1 text-sm text-slate-400">{{ $description }}</div>
            @endif
        </td>
    </tr>
@else
    <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center">
        <div class="text-sm font-medium text-slate-600">{{ $title }}</div>
        @if ($description)
            <div class="mt-1 text-sm text-slate-400">{{ $description }}</div>
        @endif
        @if (isset($actions))
            <div class="mt-4 flex justify-center gap-2">{{ $actions }}</div>
        @endif
    </div>
@endif
