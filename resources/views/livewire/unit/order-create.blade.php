<div>
    <x-page-header title="Buat Pesanan Cuci"
                   :subtitle="'Kirim alat kotor ' . auth()->user()->unit->name . ' untuk dicuci dan disterilkan CSSD.'">
        <x-slot:actions>
            <a href="{{ route('unit.orders') }}" wire:navigate class="btn-secondary">Kembali</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            {{-- Langkah 1: foto barang. Tidak ada pendataan barang oleh unit. --}}
            <div class="card p-5">
                <div class="flex items-start gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">1</span>
                    <div class="min-w-0 flex-1">
                        <h2 class="font-semibold text-slate-900">Foto Barang</h2>
                        <p class="mt-0.5 text-sm text-slate-500">
                            Jepret atau pilih foto alat kotor yang akan dikirim. Foto ini jadi bukti isi kiriman.
                        </p>

                        <div class="mt-4 flex flex-wrap gap-2">
                            {{-- Jepret langsung: atribut capture membuka kamera di HP/tablet. --}}
                            <label class="btn-primary cursor-pointer">
                                <x-icon name="camera" class="h-4 w-4" />
                                Ambil dari Kamera
                                <input wire:model="cameraShot" type="file" accept="image/*" capture="environment" class="hidden">
                            </label>

                            <label class="btn-secondary cursor-pointer">
                                <x-icon name="bag" class="h-4 w-4" />
                                Pilih dari Galeri
                                <input wire:model="galleryPicks" type="file" accept="image/*" multiple class="hidden">
                            </label>
                        </div>

                        <div wire:loading wire:target="cameraShot, galleryPicks" class="mt-2 text-xs text-slate-500">
                            Mengunggah foto...
                        </div>

                        @error('photos') <p class="field-error">{{ $message }}</p> @enderror
                        @error('cameraShot') <p class="field-error">{{ $message }}</p> @enderror
                        @error('galleryPicks.*') <p class="field-error">{{ $message }}</p> @enderror

                        @if (count($photos) > 0)
                            <div class="mt-4 grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-5">
                                @foreach ($photos as $index => $photo)
                                    <div wire:key="photo-{{ $index }}" class="group relative aspect-square overflow-hidden rounded-lg ring-1 ring-slate-200">
                                        <img src="{{ $photo->temporaryUrl() }}" alt="Foto barang {{ $index + 1 }}"
                                             class="h-full w-full object-cover">
                                        <button type="button" wire:click="removePhoto({{ $index }})"
                                                class="absolute right-1 top-1 rounded-full bg-slate-900/70 p-1 text-white transition hover:bg-red-600"
                                                title="Hapus foto">
                                            <x-icon name="close" class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                            <p class="mt-2 text-xs text-slate-400">{{ count($photos) }} foto terlampir.</p>
                        @else
                            <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-400">
                                Belum ada foto. Minimal satu foto barang.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Langkah 2: catatan --}}
            <div class="card p-5">
                <div class="flex items-start gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">2</span>
                    <div class="min-w-0 flex-1">
                        <h2 class="font-semibold text-slate-900">Catatan untuk CSSD</h2>
                        <textarea wire:model="notes" rows="3" class="field-input mt-3"
                                  placeholder="Opsional. Contoh: 2 set bedah minor dan beberapa gunting, ada satu klem yang macet."></textarea>
                        @error('notes') <p class="field-error">{{ $message }}</p> @enderror

                        @if ($batchOptions->isNotEmpty())
                            <div class="mt-3">
                                <label class="field-label" for="oc-batch">Batch Terkait</label>
                                <select wire:model="batchId" id="oc-batch" class="field-input sm:max-w-xs">
                                    <option value="">Tanpa batch</option>
                                    @foreach ($batchOptions as $batch)
                                        <option value="{{ $batch->id }}">{{ $batch->name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-slate-400">Opsional, untuk alat langganan unit Anda.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Ringkasan & kirim --}}
        <div class="card h-fit p-5 lg:sticky lg:top-6">
            <h2 class="mb-3 font-semibold text-slate-900">Kirim Pesanan</h2>

            <form wire:submit="save" class="space-y-4">
                <label @class([
                    'flex cursor-pointer items-start gap-2.5 rounded-lg border p-3 transition',
                    'border-red-400 bg-red-50' => $isCito,
                    'border-slate-200 hover:bg-slate-50' => ! $isCito,
                ])>
                    <input wire:model.live="isCito" type="checkbox"
                           class="mt-0.5 rounded border-slate-300 text-red-600 focus:ring-red-500">
                    <span>
                        <span @class(['text-sm font-semibold', 'text-red-800' => $isCito, 'text-slate-800' => ! $isCito])>
                            Pasien CITO
                        </span>
                        <span class="mt-0.5 block text-xs {{ $isCito ? 'text-red-700' : 'text-slate-500' }}">
                            Alat dibutuhkan segera. Pesanan naik ke urutan teratas antrian CSSD.
                        </span>
                    </span>
                </label>

                {{-- Waktu dibutuhkan hanya muncul untuk pesanan CITO. --}}
                @if ($isCito)
                    <div>
                        <label class="field-label" for="oc-needed">Dibutuhkan Pada</label>
                        <input wire:model="neededAt" id="oc-needed" type="datetime-local" class="field-input">
                        @error('neededAt') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="rounded-lg bg-slate-50 p-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Foto terlampir</span>
                        <span class="font-semibold text-slate-900">{{ count($photos) }}</span>
                    </div>
                </div>

                <button type="submit" class="btn-primary w-full" @disabled(count($photos) === 0)
                        wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">
                        {{ $isCito ? 'Kirim Pesanan CITO' : 'Kirim Pesanan' }}
                    </span>
                    <span wire:loading wire:target="save">Mengirim...</span>
                </button>

                <p class="text-xs text-slate-400">
                    Setelah pesanan terkirim, antar alat kotornya ke CSSD. Petugas akan men-scan
                    tiap alat yang datang, dan progresnya bisa Anda pantau langsung.
                </p>
            </form>
        </div>
    </div>
</div>
