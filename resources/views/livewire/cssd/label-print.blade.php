<div>
    <div class="print:hidden">
        <x-page-header title="Cetak Label QR" subtitle="Tempelkan label pada kemasan alat sebelum masuk sterilisasi.">
            <x-slot:actions>
                <button onclick="window.print()" class="btn-primary">Cetak</button>
            </x-slot:actions>
        </x-page-header>

        <div class="card mb-5 flex flex-wrap gap-3 p-4">
            <select wire:model.live="orderId" class="field-input sm:max-w-sm">
                <option value="">Semua order</option>
                @foreach ($orderOptions as $order)
                    <option value="{{ $order->id }}">{{ $order->order_number }} — {{ $order->originUnit->name }}</option>
                @endforeach
            </select>

            <input wire:model.live.debounce.300ms="search" type="search"
                   placeholder="Cari kode label…" class="field-input font-mono sm:max-w-xs">
        </div>

        @if ($batches->isEmpty())
            <x-empty-state title="Tidak ada label untuk dicetak"
                           description="Pilih order yang sudah didata, atau cari kode label tertentu." />
        @else
            <p class="mb-3 text-sm text-slate-500">{{ $batches->count() }} label siap cetak.</p>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 print:grid-cols-3 print:gap-2">
        @foreach ($batches as $batch)
            <div wire:key="label-{{ $batch->id }}"
                 class="break-inside-avoid rounded-lg border-2 border-slate-800 bg-white p-3 print:rounded-none">
                <div class="flex justify-center">
                    <div class="h-[110px] w-[110px] [&>svg]:h-full [&>svg]:w-full">
                        {!! $qrSvgs[$batch->id] !!}
                    </div>
                </div>

                <div class="mt-2 text-center font-mono text-sm font-bold tracking-wider text-slate-900">
                    {{ $batch->public_code }}
                </div>

                <div class="mt-1 text-center text-xs font-medium leading-tight text-slate-800">
                    {{ $batch->displayName() }}
                </div>

                <div class="mt-1 flex justify-between border-t border-slate-300 pt-1 text-[10px] text-slate-600">
                    <span>{{ $batch->originUnit->code }}</span>
                    <span>{{ $batch->displayQuantity() }}</span>
                </div>

                @if ($batch->currentDeliveryOrder)
                    <div class="text-center text-[10px] text-slate-500">{{ $batch->currentDeliveryOrder->order_number }}</div>
                @endif
            </div>
        @endforeach
    </div>
</div>

@push('styles')
<style>
    @media print {
        @page { margin: 8mm; }
        nav, header, footer { display: none !important; }
        body { background: white !important; }
    }
</style>
@endpush
