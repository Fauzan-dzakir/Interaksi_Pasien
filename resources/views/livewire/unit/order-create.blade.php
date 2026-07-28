<div>
    <x-page-header title="Buat Order Pengiriman"
                   :subtitle="'Kirim alat kotor dari ' . auth()->user()->unit->name . ' ke CSSD.'">
        <x-slot:actions>
            <a href="{{ route('unit.orders') }}" wire:navigate class="btn-secondary">Kembali</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="card p-6 lg:col-span-2">
            <form wire:submit="save" class="space-y-4">
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
                    <label class="field-label" for="notes">Catatan untuk CSSD</label>
                    <textarea wire:model="notes" id="notes" rows="3" class="field-input"
                              placeholder="Opsional — mis. alat rusak, perlu prioritas, dll."></textarea>
                    @error('notes') <p class="field-error">{{ $message }}</p> @enderror
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
