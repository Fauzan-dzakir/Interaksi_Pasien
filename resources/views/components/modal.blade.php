@props(['show', 'title', 'maxWidth' => 'max-w-lg'])

{{-- $show adalah ekspresi Blade yang mengacu ke properti boolean komponen Livewire. --}}
@if ($show)
    <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/50" wire:click="$dispatch('close-modal')"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full {{ $maxWidth }} rounded-xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>
                    <button type="button" wire:click="$dispatch('close-modal')"
                            class="rounded-md p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="px-5 py-4">{{ $slot }}</div>

                @if (isset($footer))
                    <div class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3">
                        {{ $footer }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
