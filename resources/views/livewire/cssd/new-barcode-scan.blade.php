<div>
    <x-page-header title="Scan Barcode Baru"
                   subtitle="Pasang barcode baru pada alat yang selesai dekontaminasi & pengemasan." />

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-slate-900">1. Scan barcode lama</h2>
                <p class="mt-1 text-xs text-slate-500">
                    Barcode lama masih berlaku di sistem walaupun label fisiknya sudah dibuang saat kemasan dibuka.
                </p>

                <form wire:submit="lookup" class="mt-3">
                    <input wire:model="lookupCode" type="text" autocomplete="off" autocapitalize="characters"
                           class="field-input font-mono tracking-wider" placeholder="CSSD-XXXXXXXX" autofocus>
                </form>

                @if ($feedback)
                    <p @class([
                        'mt-2 text-sm',
                        'text-leaf-700' => $feedbackType === 'success',
                        'text-red-600' => $feedbackType === 'error',
                        'text-slate-500' => $feedbackType === 'info',
                    ])>{{ $feedback }}</p>
                @endif
            </div>

            @if ($asset)
                <div class="card">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-sm font-semibold text-slate-900">{{ $asset->displayName() }}</h2>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">
                                {{ $asset->asset_type->label() }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Barcode lama: <span class="font-mono">{{ $asset->current_code }}</span>
                            @if ($asset->batch) · batch {{ $asset->batch->name }} ({{ $asset->batch->unit->name }}) @endif
                        </p>
                    </div>

                    <form wire:submit="save" class="space-y-4 p-5">
                        <div>
                            <label class="field-label" for="new-code">2. Barcode Baru</label>
                            <input wire:model="newCode" id="new-code" type="text"
                                   autocomplete="off" autocapitalize="characters"
                                   class="field-input font-mono tracking-wider" placeholder="Scan label baru di sini">
                            @error('newCode') <p class="field-error">{{ $message }}</p> @enderror
                            <p class="mt-1 text-xs text-slate-400">
                                Cetak label kosong lebih dulu di menu
                                <a href="{{ route('cssd.barcodes') }}" wire:navigate class="text-brand-700 underline">Buat Barcode</a>.
                            </p>
                        </div>

                        <div>
                            <label class="field-label" for="photo">
                                3. Foto {{ $asset->asset_type === \App\Enums\AssetType::Set ? 'Set Terakit' : 'Alat' }}
                                @if ($asset->asset_type === \App\Enums\AssetType::Set)
                                    <span class="text-red-600">*</span>
                                @endif
                            </label>
                            <input wire:model="photo" id="photo" type="file" accept="image/*" class="field-input !py-1.5">
                            @error('photo') <p class="field-error">{{ $message }}</p> @enderror

                            <div wire:loading wire:target="photo" class="mt-1 text-xs text-slate-500">Mengunggah foto…</div>

                            @if ($photo)
                                <img src="{{ $photo->temporaryUrl() }}" alt="Pratinjau"
                                     class="mt-2 h-40 rounded-lg object-cover ring-1 ring-slate-200">
                            @endif
                        </div>

                        @if ($asset->asset_type === \App\Enums\AssetType::Set && count($checklist) > 0)
                            <div>
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="field-label !mb-0">4. Periksa Isi Set</span>
                                    @if ($missingCount > 0)
                                        <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">
                                            {{ $missingCount }} tidak ditemukan
                                        </span>
                                    @endif
                                </div>

                                <div class="space-y-1.5">
                                    @foreach ($checklist as $index => $row)
                                        <div wire:key="chk-{{ $index }}"
                                             @class([
                                                 'flex items-center gap-3 rounded-lg border px-3 py-2 transition',
                                                 'border-leaf-200 bg-leaf-50' => $row['is_present'],
                                                 'border-red-300 bg-red-50' => ! $row['is_present'],
                                             ])>
                                            <button type="button" wire:click="toggleContent({{ $index }})"
                                                    @class([
                                                        'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white transition',
                                                        'bg-leaf-600 hover:bg-leaf-700' => $row['is_present'],
                                                        'bg-red-500 hover:bg-red-600' => ! $row['is_present'],
                                                    ])
                                                    title="{{ $row['is_present'] ? 'Ada klik bila tidak ditemukan' : 'Tidak ada klik bila ditemukan' }}">
                                                {{ $row['is_present'] ? '✓' : '✗' }}
                                            </button>

                                            <div class="min-w-0 flex-1">
                                                <div class="truncate text-sm font-medium text-slate-800">{{ $row['name'] }}</div>
                                                <div class="font-mono text-xs text-slate-400">{{ $row['code'] }} · {{ $row['quantity'] }} pcs</div>
                                            </div>

                                            @unless ($row['is_present'])
                                                <input wire:model="checklist.{{ $index }}.note" type="text"
                                                       class="field-input !w-48 !py-1 text-xs" placeholder="Keterangan">
                                            @endunless
                                        </div>
                                    @endforeach
                                </div>

                                @if ($missingCount > 0)
                                    <p class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-800 ring-1 ring-red-200">
                                        Set akan ditandai <strong>tidak lengkap</strong> dan muncul di laporan Admin,
                                        tapi proses tetap boleh lanjut. Alat yang hilang tercatat beserta nama Anda dan waktunya.
                                    </p>
                                @endif
                            </div>
                        @endif

                        <div class="flex justify-end gap-2 border-t border-slate-200 pt-4">
                            <button type="button" wire:click="$set('assetId', null)" class="btn-secondary">Batal</button>
                            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="save">Pasang Barcode Baru</span>
                                <span wire:loading wire:target="save">Menyimpan…</span>
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>

        <div class="space-y-5">
            <div class="card p-5">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">
                    Antrian Pencucian ({{ $washingQueue->count() }})
                </h2>
                <p class="mb-3 text-xs text-slate-500">Alat yang menunggu dipasangi barcode baru.</p>

                @forelse ($washingQueue as $queued)
                    <button wire:click="$set('lookupCode', '{{ $queued->current_code }}')" wire:key="wq-{{ $queued->id }}"
                            class="mb-1.5 flex w-full items-center justify-between gap-2 rounded-lg border border-slate-200 px-3 py-2 text-left transition hover:border-brand-300 hover:bg-brand-50">
                        <div class="min-w-0">
                            <div class="font-mono text-xs text-slate-600">{{ $queued->current_code }}</div>
                            <div class="truncate text-sm text-slate-700">{{ $queued->displayName() }}</div>
                        </div>
                        <span class="shrink-0 text-xs text-slate-400">
                            {{ $queued->asset_type === \App\Enums\AssetType::Set ? 'Set' : 'Satuan' }}
                        </span>
                    </button>
                @empty
                    <p class="text-sm text-slate-400">Tidak ada alat di tahap pencucian.</p>
                @endforelse
            </div>

            <div class="card border-sky-200 bg-sky-50/50 p-5">
                <h2 class="text-sm font-semibold text-sky-900">Kenapa set diperlakukan berbeda?</h2>
                <ul class="mt-2 space-y-1.5 text-sm text-sky-800">
                    <li class="flex gap-2">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-sky-400"></span>
                        Alat satuan: scan barcode baru, langsung selesai.
                    </li>
                    <li class="flex gap-2">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-sky-400"></span>
                        Set: isinya rawan tertinggal, jadi wajib difoto dan diperiksa satu per satu.
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
