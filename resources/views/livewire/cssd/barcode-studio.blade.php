<div>
    <div class="print:hidden">
        <x-page-header title="Buat dan Cetak Barcode"
                       subtitle="Label untuk alat terdaftar, atau lembar barcode kosong untuk dipakai setelah dekontaminasi.">
            <x-slot:actions>
                <button onclick="window.print()" class="btn-primary">Cetak</button>
            </x-slot:actions>
        </x-page-header>

        <div class="card mb-5">
            <div class="flex gap-1 border-b border-slate-200 p-2">
                <button wire:click="$set('mode', 'registered')"
                        @class([
                            'rounded-md px-4 py-2 text-sm font-medium transition',
                            'bg-brand-50 text-brand-700' => $mode === 'registered',
                            'text-slate-600 hover:bg-slate-100' => $mode !== 'registered',
                        ])>Label Alat Terdaftar</button>

                <button wire:click="$set('mode', 'blank')"
                        @class([
                            'rounded-md px-4 py-2 text-sm font-medium transition',
                            'bg-brand-50 text-brand-700' => $mode === 'blank',
                            'text-slate-600 hover:bg-slate-100' => $mode !== 'blank',
                        ])>Barcode Kosong</button>
            </div>

            <div class="p-4">
                @if ($mode === 'registered')
                    <input wire:model.live.debounce.300ms="search" type="search"
                           placeholder="Cari barcode alat…" class="field-input font-mono sm:max-w-xs">
                    <p class="mt-2 text-xs text-slate-500">
                        Mencetak ulang label alat yang sudah terdaftar, dipakai kalau label lama rusak atau hilang.
                    </p>
                @else
                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="field-label" for="blank-count">Jumlah Label</label>
                            <input wire:model="blankCount" id="blank-count" type="number" min="1" max="60"
                                   class="field-input !w-32">
                        </div>
                        <button wire:click="generateBlanks" class="btn-primary">Buat Kode</button>
                    </div>
                    @error('blankCount') <p class="field-error">{{ $message }}</p> @enderror

                    <p class="mt-3 rounded-lg bg-sky-50 px-3 py-2 text-sm text-sky-800 ring-1 ring-sky-200">
                        Kode ini belum terikat ke alat mana pun. Cetak, tempelkan pada kemasan alat yang
                        sudah didekontaminasi, lalu scan di menu
                        <a href="{{ route('cssd.new-barcode') }}" wire:navigate class="font-medium underline">Scan Barcode Baru</a>
                       , di situlah kode terikat ke alatnya.
                    </p>
                @endif
            </div>
        </div>
    </div>

    @php $printItems = $mode === 'registered' ? $assets : collect($blankCodes); @endphp

    @if ($printItems->isEmpty())
        <div class="print:hidden">
            <x-empty-state title="Belum ada label untuk dicetak"
                           :description="$mode === 'registered' ? 'Cari barcode alat yang ingin dicetak ulang.' : 'Tentukan jumlah label lalu klik “Buat Kode”.'" />
        </div>
    @else
        <p class="mb-3 text-sm text-slate-500 print:hidden">{{ $printItems->count() }} label siap cetak.</p>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 print:grid-cols-3 print:gap-2">
            @if ($mode === 'registered')
                @foreach ($assets as $asset)
                    <div wire:key="lbl-{{ $asset->id }}"
                         class="break-inside-avoid rounded-lg border-2 border-slate-800 bg-white p-3 print:rounded-none">
                        <div class="flex justify-center">
                            <div class="h-[110px] w-[110px] [&>svg]:h-full [&>svg]:w-full">{!! $assetQrs[$asset->id] !!}</div>
                        </div>
                        <div class="mt-2 text-center font-mono text-sm font-bold tracking-wider text-slate-900">
                            {{ $asset->current_code }}
                        </div>
                        <div class="mt-1 text-center text-xs font-medium leading-tight text-slate-800">
                            {{ $asset->displayName() }}
                        </div>
                        <div class="mt-1 flex justify-between border-t border-slate-300 pt-1 text-[10px] text-slate-600">
                            <span>{{ $asset->asset_type === \App\Enums\AssetType::Set ? 'SET' : 'SATUAN' }}</span>
                            <span>{{ $asset->batch?->unit->code ?? 'STOK' }}</span>
                        </div>
                    </div>
                @endforeach
            @else
                @foreach ($blankCodes as $code)
                    <div wire:key="blank-{{ $code }}"
                         class="break-inside-avoid rounded-lg border-2 border-slate-800 bg-white p-3 print:rounded-none">
                        <div class="flex justify-center">
                            <div class="h-[110px] w-[110px] [&>svg]:h-full [&>svg]:w-full">{!! $blankQrs[$code] !!}</div>
                        </div>
                        <div class="mt-2 text-center font-mono text-sm font-bold tracking-wider text-slate-900">
                            {{ $code }}
                        </div>
                        <div class="mt-1 border-t border-slate-300 pt-1 text-center text-[10px] text-slate-500">
                            Isi alat: ______________
                        </div>
                        <div class="text-center text-[10px] text-slate-500">Tgl: ____/____/______</div>
                    </div>
                @endforeach
            @endif
        </div>
    @endif
</div>

@push('styles')
<style>
    @media print {
        @page { margin: 8mm; }
        body { background: white !important; }
    }
</style>
@endpush
