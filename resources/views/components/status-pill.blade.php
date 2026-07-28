@props(['active' => true, 'labelOn' => 'Aktif', 'labelOff' => 'Nonaktif'])

<span @class([
    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
    'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' => $active,
    'bg-slate-100 text-slate-500 ring-1 ring-slate-200' => ! $active,
])>
    {{ $active ? $labelOn : $labelOff }}
</span>
