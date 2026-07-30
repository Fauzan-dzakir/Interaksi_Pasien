@props(['state'])

{{-- Menerima enum apa pun yang punya label() dan colorClasses(): status order, status alat, atau zona. --}}
<span class="inline-flex items-center whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium ring-1 {{ $state->colorClasses() }}">
    {{ $state->label() }}
</span>
