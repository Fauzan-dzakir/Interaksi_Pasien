{{--
    Dropdown filter yang bisa diketik untuk mencari opsinya (pakai TomSelect,
    sama seperti pemilih alat/set di halaman Pendataan CSSD) — supaya daftar
    unit/status/alat yang panjang tidak perlu di-scroll manual satu-satu.

    wire:ignore sengaja dipasang: TomSelect mengganti tampilan <select> aslinya
    dengan widget JS sendiri, jadi Livewire tidak boleh ikut me-render ulang
    elemen ini. Perubahan dikirim manual ke Livewire lewat event 'change' TomSelect.
--}}
@props([
    'wireModel',
    'options',
    'placeholder' => 'Semua',
    'searchPlaceholder' => 'Ketik untuk mencari…',
])

<div wire:ignore wire:key="ts-{{ $wireModel }}" x-data="{
    init() {
        let ts = new TomSelect(this.$refs.select, {
            create: false,
            placeholder: @js($searchPlaceholder),
            allowEmptyOption: true,
        });
        ts.on('change', (val) => { $wire.set('{{ $wireModel }}', val); });
    }
}">
    <select x-ref="select" class="field-input">
        <option value="">{{ $placeholder }}</option>
        {{ $slot }}
    </select>
</div>
