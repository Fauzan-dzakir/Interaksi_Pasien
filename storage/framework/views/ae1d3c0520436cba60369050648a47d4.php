<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'submit',
    'model' => 'code',
    'placeholder' => 'CSSD-XXXXXXXX',
    'label' => 'Scan atau ketik kode',
    'onCamera' => null,
    'inputClass' => 'field-input font-mono tracking-wider',
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'submit',
    'model' => 'code',
    'placeholder' => 'CSSD-XXXXXXXX',
    'label' => 'Scan atau ketik kode',
    'onCamera' => null,
    'inputClass' => 'field-input font-mono tracking-wider',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    // Default: isi properti lewat $wire.set lalu panggil method submit-nya —
    // cocok untuk method tanpa parameter yang baca $this->{$model} (pola paling umum).
    // Dipakai kalau tidak ada onCamera kustom (mis. yang butuh menandai metode input, lihat scan-station).
    $cameraAction = $onCamera ?? "\$wire.set('{$model}', code); \$wire.{$submit}();";
    $inputId = 'scan-' . \Illuminate\Support\Str::random(6);
?>

<div x-data="scanInput()" x-on:scan-processed.window="refocus()">
    <div class="flex items-center justify-between gap-3">
        <label class="field-label !mb-0" for="<?php echo e($inputId); ?>"><?php echo e($label); ?></label>
        <button type="button" x-on:click="toggleCamera()"
                class="btn-secondary !px-3 !py-1.5"
                x-text="cameraOn ? 'Tutup Kamera' : 'Buka Kamera'"></button>
    </div>

    <form wire:submit="<?php echo e($submit); ?>" class="mt-2">
        <input wire:model="<?php echo e($model); ?>" id="<?php echo e($inputId); ?>" x-ref="input" type="text"
               autocomplete="off" autocapitalize="characters" spellcheck="false"
               class="<?php echo e($inputClass); ?>"
               placeholder="<?php echo e($placeholder); ?>"
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

    <?php
        $__scriptKey = '1109158235-0';
        ob_start();
    ?>
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

            <?php echo $cameraAction; ?>

        },

        destroy() {
            this.stopCamera();
        },
    }));
</script>
    <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?>
<?php /**PATH C:\Users\Ridlo\Kuliah\Semester 5\Magang interaksi Pasien\Inovasi Baru(2)\resources\views/components/scan-input.blade.php ENDPATH**/ ?>