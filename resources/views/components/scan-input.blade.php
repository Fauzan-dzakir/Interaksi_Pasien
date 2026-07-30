@props([
    'submit',
    'model' => 'code',
    'placeholder' => 'CSSD-XXXXXXXX',
    'label' => 'Scan atau ketik kode',
    'onCamera' => null,
    'inputClass' => 'field-input font-mono tracking-wider',
])

@php
    // Default: isi properti lewat $wire.set lalu panggil method submit-nya —
    // cocok untuk method tanpa parameter yang baca $this->{$model} (pola paling umum).
    // Dipakai kalau tidak ada onCamera kustom (mis. yang butuh menandai metode input, lihat scan-station).
    $cameraAction = $onCamera ?? "\$wire.set('{$model}', code); \$wire.{$submit}();";
    $inputId = 'scan-' . \Illuminate\Support\Str::random(6);
@endphp

<div x-data="scanInput()" x-on:scan-processed.window="refocus()">
    <div class="flex items-center justify-between gap-3">
        <label class="field-label !mb-0" for="{{ $inputId }}">{{ $label }}</label>
        <button type="button" x-on:click="toggleCamera()"
                class="btn-secondary !px-3 !py-1.5"
                x-text="cameraOn ? 'Tutup Kamera' : 'Buka Kamera'"></button>
    </div>

    <form wire:submit="{{ $submit }}" class="mt-2">
        <input wire:model="{{ $model }}" id="{{ $inputId }}" x-ref="input" type="text"
               autocomplete="off" autocapitalize="characters" spellcheck="false"
               class="{{ $inputClass }}"
               placeholder="{{ $placeholder }}"
               x-on:blur="scheduleRefocus()">
    </form>

    <div x-show="cameraOn" x-cloak class="mt-4">
        <video x-ref="video" class="w-full rounded-lg bg-slate-900" style="max-height: 320px"></video>
        <p x-show="cameraError" x-cloak class="field-error" x-text="cameraError"></p>
        <p class="mt-1.5 text-xs text-slate-400">
            Kamera butuh koneksi HTTPS. Bila kamera tidak muncul saat diakses lewat jaringan RS,
            pastikan alamatnya memakai https.
        </p>
    </div>
</div>

@script
<script>
    Alpine.data('scanInput', () => ({
        cameraOn: false,
        cameraError: '',
        scanner: null,
        refocusTimer: null,
        lastCode: '',
        lastAt: 0,

        init() {
            this.refocus();
        },

        refocus() {
            this.$nextTick(() => this.$refs.input?.focus());
        },

        // Scanner HID kadang memicu blur sesaat; fokus dikembalikan supaya
        // petugas bisa scan beruntun tanpa harus klik kolom input lagi.
        // TAPI: jangan rebut fokus balik kalau blur itu terjadi karena petugas
        // sengaja klik kontrol lain — dulu bug-nya persis itu.
        scheduleRefocus() {
            clearTimeout(this.refocusTimer);
            this.refocusTimer = setTimeout(() => {
                if (this.cameraOn) return;

                const active = document.activeElement;
                const userIsUsingAnotherControl = active
                    && active !== document.body
                    && active !== this.$refs.input
                    && ['INPUT', 'SELECT', 'TEXTAREA', 'BUTTON', 'A'].includes(active.tagName);

                if (! userIsUsingAnotherControl) {
                    this.$refs.input?.focus();
                }
            }, 150);
        },

        async toggleCamera() {
            this.cameraOn ? this.stopCamera() : await this.startCamera();
        },

        async startCamera() {
            this.cameraError = '';
            this.cameraOn = true;

            try {
                const QrScanner = window.QrScanner;

                if (!QrScanner) {
                    throw new Error('modul scanner belum termuat, coba muat ulang halaman');
                }

                this.scanner = new QrScanner(
                    this.$refs.video,
                    (result) => this.onDecode(result.data),
                    { highlightScanRegion: true, highlightCodeOutline: true, maxScansPerSecond: 4 },
                );

                await this.scanner.start();
            } catch (e) {
                this.cameraOn = false;
                this.cameraError = 'Kamera tidak bisa dibuka: ' + e.message;
            }
        },

        stopCamera() {
            this.scanner?.stop();
            this.scanner?.destroy();
            this.scanner = null;
            this.cameraOn = false;
            this.refocus();
        },

        onDecode(code) {
            // Kamera membaca terus-menerus; kode yang sama dalam 2 detik diabaikan
            // supaya satu label tidak terkirim berkali-kali.
            const now = Date.now();
            if (code === this.lastCode && now - this.lastAt < 2000) return;

            this.lastCode = code;
            this.lastAt = now;

            {!! $cameraAction !!}
        },

        destroy() {
            this.stopCamera();
        },
    }));
</script>
@endscript
