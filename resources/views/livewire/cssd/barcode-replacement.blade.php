<div>
    <x-page-header title="Ganti Barcode"
                   subtitle="Gabungkan alat bersih menjadi satu set baru dengan satu label QR." />

    <div class="grid gap-5 lg:grid-cols-2">
        <div class="space-y-5">
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-slate-900">1. Scan barcode lama</h2>
                <p class="mt-1 text-xs text-slate-500">
                    Tambahkan semua barcode lama yang akan digantikan oleh satu barcode baru.
                </p>

                <form wire:submit="addCode" class="mt-3">
                    <input wire:model="code" type="text" autocomplete="off" autocapitalize="characters"
                           class="field-input font-mono tracking-wider" placeholder="CSSD-XXXXXXXX" autofocus>
                </form>

                @if ($feedback)
                    <p @class([
                        'mt-2 text-sm',
                        'text-emerald-700' => $feedbackType === 'success',
                        'text-red-600' => $feedbackType === 'error',
                        'text-slate-500' => $feedbackType === 'info',
                    ])>{{ $feedback }}</p>
                @endif

                @error('selectedIds') <p class="field-error">{{ $message }}</p> @enderror

                <div class="mt-4">
                    @forelse ($selected as $batch)
                        <div wire:key="sel-{{ $batch->id }}"
                             class="mb-2 flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            <div class="min-w-0">
                                <div class="font-mono text-xs font-medium text-slate-700">{{ $batch->public_code }}</div>
                                <div class="truncate text-sm text-slate-600">
                                    {{ $batch->displayName() }} · {{ $batch->displayQuantity() }} · {{ $batch->originUnit->code }}
                                </div>
                            </div>
                            <button wire:click="removeCode({{ $batch->id }})"
                                    class="shrink-0 rounded-md p-1 text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    @empty
                        <p class="rounded-lg border border-dashed border-slate-300 px-3 py-6 text-center text-sm text-slate-400">
                            Belum ada barcode lama dipilih.
                        </p>
                    @endforelse
                </div>
            </div>

            <div class="card border-amber-200 bg-amber-50/60 p-5">
                <h2 class="text-sm font-semibold text-amber-900">Kapan barcode diganti?</h2>
                <p class="mt-2 text-sm text-amber-800">
                    Setelah alat lolos cek kebersihan dan dirakit ulang jadi satu set.
                    Barcode lama <strong>tidak dihapus</strong> — statusnya jadi “Barcode Diganti” dan
                    tetap tertaut ke barcode baru, sehingga riwayat alat lintas siklus tetap utuh saat audit.
                </p>
            </div>
        </div>

        <div class="card p-5">
            <h2 class="text-sm font-semibold text-slate-900">2. Tentukan barcode baru</h2>

            <form wire:submit="save" class="mt-3 space-y-4">
                <div>
                    <label class="field-label" for="new-type">Jenis</label>
                    <select wire:model.live="newType" id="new-type" class="field-input">
                        <option value="set">Per Set (alat dirakit jadi satu set)</option>
                        <option value="individual">Per Barang (alat lepasan sejenis)</option>
                    </select>
                </div>

                @if ($newType === 'set')
                    <div>
                        <label class="field-label" for="new-set">Set Alat</label>
                        <select wire:model="instrument_set_id" id="new-set" class="field-input">
                            <option value="">— Pilih set —</option>
                            @foreach ($setOptions as $opt)
                                <option value="{{ $opt->id }}">{{ $opt->code }} — {{ $opt->name }}</option>
                            @endforeach
                        </select>
                        @error('instrument_set_id') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                @else
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label class="field-label" for="new-item">Alat</label>
                            <select wire:model="item_id" id="new-item" class="field-input">
                                <option value="">— Pilih alat —</option>
                                @foreach ($itemOptions as $opt)
                                    <option value="{{ $opt->id }}">{{ $opt->code }} — {{ $opt->name }}</option>
                                @endforeach
                            </select>
                            @error('item_id') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="field-label" for="new-qty">Jumlah</label>
                            <input wire:model="quantity" id="new-qty" type="number" min="1" class="field-input">
                            @error('quantity') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif

                <div>
                    <label class="field-label" for="photo">Foto Set Terakit</label>
                    <input wire:model="photo" id="photo" type="file" accept="image/*" class="field-input !py-1.5">
                    <p class="mt-1 text-xs text-slate-400">
                        Opsional tapi disarankan — jadi bukti visual isi set saat terjadi selisih.
                    </p>
                    @error('photo') <p class="field-error">{{ $message }}</p> @enderror

                    <div wire:loading wire:target="photo" class="mt-1 text-xs text-slate-500">Mengunggah foto…</div>

                    @if ($photo)
                        <img src="{{ $photo->temporaryUrl() }}" alt="Pratinjau set terakit"
                             class="mt-2 h-32 rounded-lg object-cover ring-1 ring-slate-200">
                    @endif
                </div>

                <div>
                    <label class="field-label" for="reason">Alasan / Keterangan</label>
                    <textarea wire:model="reason" id="reason" rows="2" class="field-input"
                              placeholder="mis. alat lepasan dirakit kembali jadi Set Bedah Minor"></textarea>
                    @error('reason') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Buat Barcode Baru</span>
                    <span wire:loading wire:target="save">Memproses…</span>
                </button>
            </form>
        </div>
    </div>
</div>
