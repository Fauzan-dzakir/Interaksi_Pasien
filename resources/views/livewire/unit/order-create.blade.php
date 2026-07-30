<div>
    <x-page-header title="Buat Order Pengiriman"
                   :subtitle="'Kirim alat kotor dari ' . auth()->user()->unit->name . ' ke CSSD.'">
        <x-slot:actions>
            <a href="{{ route('unit.orders') }}" wire:navigate class="btn-secondary">Kembali</a>
        </x-slot:actions>
    </x-page-header>

    {{--
        Alat yang sudah ditandai dipakai (lewat menu "Pendataan Alat di Unit") — ini
        alat kotor yang akan ikut dikirim balik lewat order ini. TAMPILAN SAJA, tidak
        bisa diubah dari sini — untuk menandai alat mana yang dipakai, buka menu
        "Pendataan Alat di Unit".
    --}}
    <div class="card mb-5">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-5 py-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Alat Kotor (Sudah Dipakai)</h2>
                <p class="mt-0.5 text-xs text-slate-500">
                    Alat yang sudah ditandai dipakai — akan ikut dikirim balik lewat order ini.
                </p>
            </div>
            <a href="{{ route('unit.inventory') }}" wire:navigate class="btn-secondary !px-3 !py-1.5 shrink-0">
                Kelola di Pendataan Alat
            </a>
        </div>

        @if ($heldBatches->isEmpty())
            <div class="px-5 py-8 text-center text-sm text-slate-400">
                Belum ada alat yang ditandai dipakai. Buka menu
                <a href="{{ route('unit.inventory') }}" wire:navigate class="text-teal-700 hover:underline">Pendataan Alat di Unit</a>
                untuk menandainya.
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($heldBatches as $batch)
                    <div wire:key="held-{{ $batch->id }}" class="px-5 py-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <span class="font-mono text-xs text-slate-500">{{ $batch->public_code }}</span>
                                <div class="font-medium text-slate-900">{{ $batch->displayName() }}</div>
                            </div>
                            <x-state-pill :state="$batch->status" />
                        </div>

                        @if ($batch->batch_type === \App\Enums\BatchType::Set && $batch->instrumentSet)
                            <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50/50 p-3">
                                <p class="mb-2 text-xs font-semibold text-slate-500">Isi set yang ditandai dipakai</p>
                                <div class="space-y-1">
                                    @foreach ($batch->instrumentSet->items as $setItem)
                                        @php
                                            $mark = $batch->usageMarks->firstWhere('item_id', $setItem->id);
                                            $isUsed = $mark?->is_used ?? false;
                                        @endphp
                                        <div class="flex items-center gap-2 text-sm text-slate-700">
                                            <span @class([
                                                'text-emerald-600' => $isUsed,
                                                'text-slate-300' => ! $isUsed,
                                            ])>{{ $isUsed ? '✓' : '—' }}</span>
                                            {{ $setItem->name }}
                                            <span class="text-xs text-slate-400">({{ $setItem->pivot->quantity }}x)</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="card p-6 lg:col-span-2">
            <form wire:submit="save" class="space-y-4">
                <div class="rounded-lg bg-slate-50 p-3 text-xs text-slate-600 ring-1 ring-slate-200">
                    @if ($isIbs)
                        Petugas CSSD dapat menjemput langsung ke lokasi yang Anda pilih di bawah — terutama untuk kasus CITO.
                    @else
                        Alat kotor diantar sendiri oleh petugas unit Anda ke CSSD (bukan CSSD yang menjemput).
                        Isi nama petugas pengantar di bawah.
                    @endif
                </div>

                <div>
                    <label class="field-label" for="courier">Nama Petugas yang Mengantar</label>
                    <input wire:model="courier_name" id="courier" type="text" class="field-input">
                    @error('courier_name') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="field-label" for="sent-at">Tanggal &amp; Jam Kirim</label>
                        <input wire:model="sent_at" id="sent-at" type="datetime-local" class="field-input">
                        @error('sent_at') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label" for="box-count">Jumlah Box / Kontainer</label>
                        <input wire:model="box_count" id="box-count" type="number" min="1" max="99" class="field-input">
                        @error('box_count') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                
                <div>
                    <label class="field-label" for="pickup-location">{{ $isIbs ? 'Lokasi Pengambilan (Spesifik)' : 'Ruangan / Lokasi Asal' }}</label>
                    <select wire:model.live="pickup_location_id" id="pickup-location" class="field-input">
                        <option value="">— Pilih lokasi —</option>
                        @foreach ($locationOptions as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                        <option value="other">Lokasi lain (belum ada di daftar)</option>
                    </select>
                    @error('pickup_location_id') <p class="field-error">{{ $message }}</p> @enderror

                    @if ($pickup_location_id === 'other')
                        <input wire:model="new_location_name" type="text" class="field-input mt-2"
                               placeholder="Tulis nama lokasi — akan diajukan ke Admin untuk ditambahkan ke daftar">
                        @error('new_location_name') <p class="field-error">{{ $message }}</p> @enderror
                    @endif
                </div>

                <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-5 items-center">
                            <input wire:model.live="is_cito" id="is_cito" type="checkbox" class="h-4 w-4 rounded border-red-300 text-red-600 focus:ring-red-600">
                        </div>
                        <div class="flex-1">
                            <label for="is_cito" class="text-sm font-medium text-red-900">Urgensi Pasien CITO (Prioritas Tinggi)</label>
                            <p class="text-xs text-red-700 mt-1">Tandai order ini jika alat dibutuhkan segera untuk operasi CITO agar didahulukan dalam proses pencucian/sterilisasi CSSD.</p>
                        </div>
                    </div>

                    @if ($is_cito)
                        <div class="mt-4 pl-7">
                            <label class="field-label !text-red-900" for="needed-at">Dibutuhkan Pada Jam Berapa</label>
                            <input wire:model="needed_at" id="needed-at" type="time" class="field-input border-red-300 focus:border-red-500 focus:ring-red-500">
                            <p class="mt-1 text-xs text-red-700">
                                Sistem otomatis memakai hari ini kalau jamnya belum lewat, atau besok kalau sudah lewat —
                                jadi selalu dalam 24 jam ke depan dari sekarang.
                            </p>
                            @error('needed_at') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </div>

                <div>
                    <label class="field-label" for="notes">Catatan untuk CSSD</label>
                    <textarea wire:model="notes" id="notes" rows="3" class="field-input"
                              placeholder="Opsional — mis. alat rusak, perlu prioritas, dll."></textarea>
                    @error('notes') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="field-label" for="photos">Foto Kondisi Alat</label>
                    <input wire:model="photos" id="photos" type="file" multiple accept="image/*" class="field-input">
                    <p class="mt-1 text-xs text-slate-500">Wajib — minimal 1 foto sebagai bukti kondisi alat saat dikirim.</p>
                    <div wire:loading wire:target="photos" class="text-xs text-slate-500 mt-1">Mengunggah...</div>
                    @error('photos') <p class="field-error">{{ $message }}</p> @enderror
                    @error('photos.*') <p class="field-error">{{ $message }}</p> @enderror
                    
                    @if ($photos)
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($photos as $index => $photo)
                                <div wire:key="photo-{{ $index }}" class="group relative h-16 w-16">
                                    <img src="{{ $photo->temporaryUrl() }}" class="h-16 w-16 rounded-md border border-slate-200 object-cover">
                                    <button type="button" wire:click="removePhoto({{ $index }})"
                                            wire:confirm="Hapus foto ini?"
                                            class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-xs font-bold text-white shadow hover:bg-red-700"
                                            title="Hapus foto ini">
                                        &times;
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 pt-4">
                    <a href="{{ route('unit.orders') }}" wire:navigate class="btn-secondary">Batal</a>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">Kirim Order</span>
                        <span wire:loading wire:target="save">Menyimpan…</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="card h-fit border-sky-200 bg-sky-50/50 p-5">
            <h2 class="text-sm font-semibold text-sky-900">Kenapa tidak ada rincian alat?</h2>
            <p class="mt-2 text-sm text-sky-800">
                Rincian alat didata oleh <strong>petugas CSSD</strong> saat barang diterima dan dihitung fisik.
                Ini mencegah selisih antara catatan unit dan jumlah alat yang benar-benar sampai.
            </p>
            <p class="mt-3 text-sm text-sky-800">
                Setelah CSSD menekan “Simpan Pendataan”, Anda akan menerima notifikasi dan
                rincian alat langsung terbuka di halaman order ini.
            </p>
        </div>
    </div>
</div>
