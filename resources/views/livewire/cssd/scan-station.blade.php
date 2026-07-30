<div>
    <x-page-header title="Stasiun Scan" subtitle="Pindai label QR untuk memindahkan alat ke tahap berikutnya." />

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="card p-5">
                <label class="field-label" for="station">Tahap / Stasiun</label>
                <select wire:model.live="station" id="station" class="field-input">
                    @foreach ($stationOptions as $opt)
                        <option value="{{ $opt->value }}">{{ $opt->label() }}</option>
                    @endforeach
                </select>
                <p class="mt-2 text-sm text-slate-500">{{ $stationEnum->description() }}</p>

                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                    <span class="text-slate-500">Alat akan berpindah ke:</span>
                    <x-state-pill :state="$stationEnum->targetStatus()" />
                </div>
            </div>

            {{--
                Satu handler untuk dua jalur input:
                - scanner barcode fisik (HID) "mengetik" kode lalu menekan Enter di input ini,
                - kamera memanggil $wire.handleScan(...) lewat Alpine.
                Input di-fokus ulang terus-menerus supaya petugas bisa scan beruntun tanpa klik.
            --}}
            <div class="card p-5"
                 x-data="scanStation()"
                 x-on:scan-processed.window="refocus()">

                <div class="flex items-center justify-between gap-3">
                    <label class="field-label !mb-0" for="scan-input">Scan atau ketik kode label</label>
                    <button type="button" x-on:click="toggleCamera()"
                            class="btn-secondary !px-3 !py-1.5"
                            x-text="cameraOn ? 'Tutup Kamera' : 'Buka Kamera'"></button>
                </div>

                <form wire:submit="handleScan(null, 'hid_scanner')" class="mt-2">
                    <input wire:model="code" id="scan-input" x-ref="input" type="text"
                           autocomplete="off" autocapitalize="characters" spellcheck="false"
                           class="field-input font-mono text-lg tracking-wider"
                           placeholder="CSSD-XXXXXXXX"
                           x-on:blur="scheduleRefocus()">
                </form>

                <p class="mt-2 text-xs text-slate-400">
                    Scanner fisik cukup diarahkan ke label — kode terisi dan terkirim otomatis.
                    Kolom ini juga bisa diketik manual bila label rusak.
                </p>

                <div x-show="cameraOn" x-cloak class="mt-4">
                    <video x-ref="video" class="w-full rounded-lg bg-slate-900" style="max-height: 320px"></video>
                    <p x-show="cameraError" x-cloak class="field-error" x-text="cameraError"></p>
                    <p class="mt-1.5 text-xs text-slate-400">
                        Kamera butuh koneksi HTTPS. Bila kamera tidak muncul saat diakses lewat jaringan RS,
                        pastikan alamatnya memakai https.
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-900">Riwayat Scan Sesi Ini</h2>
                    @if (count($log) > 0)
                        <button wire:click="$set('log', [])" class="text-xs text-slate-500 hover:text-slate-700">Bersihkan</button>
                    @endif
                </div>

                <ul class="divide-y divide-slate-100">
                    @forelse ($log as $entry)
                        <li class="flex items-start gap-3 px-5 py-3">
                            <span @class([
                                'mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                'bg-emerald-100 text-emerald-700' => $entry['status'] === 'success',
                                'bg-red-100 text-red-700' => $entry['status'] === 'error',
                                'bg-slate-100 text-slate-500' => $entry['status'] === 'info',
                            ])>
                                {{ $entry['status'] === 'success' ? '✓' : ($entry['status'] === 'error' ? '!' : '·') }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="font-mono text-xs text-slate-500">{{ $entry['code'] }}</div>
                                <div @class([
                                    'text-sm',
                                    'text-slate-800' => $entry['status'] !== 'error',
                                    'font-medium text-red-700' => $entry['status'] === 'error',
                                ])>{{ $entry['message'] }}</div>
                            </div>
                            <span class="shrink-0 text-xs text-slate-400">{{ $entry['at'] }}</span>
                        </li>
                    @empty
                        <li class="px-5 py-10 text-center text-sm text-slate-400">
                            Belum ada scan. Arahkan scanner ke label QR untuk mulai.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="space-y-5">
            <div class="card p-5">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">Antrian Tahap Ini</h2>
                <div class="space-y-3">
                    <div class="rounded-lg bg-amber-50 p-4 ring-1 ring-amber-200">
                        <div class="text-xs font-medium text-amber-800">Menunggu masuk tahap ini</div>
                        <div class="mt-0.5 text-2xl font-semibold text-amber-900">{{ $waitingCount }}</div>
                    </div>
                    <div class="rounded-lg bg-teal-50 p-4 ring-1 ring-teal-200">
                        <div class="text-xs font-medium text-teal-800">Sedang di tahap ini</div>
                        <div class="mt-0.5 text-2xl font-semibold text-teal-900">{{ $atStationCount }}</div>
                    </div>
                </div>
            </div>

            <div class="card border-slate-200 bg-slate-50/60 p-5">
                <h2 class="text-sm font-semibold text-slate-900">Kalau scan ditolak</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Sistem menolak alat yang belum melewati tahap sebelumnya. Ini disengaja —
                    alat yang belum selesai dicuci tidak boleh tercatat steril.
                </p>
                <p class="mt-2 text-sm text-slate-600">
                    Cek status alat lewat menu <strong>Ganti Barcode</strong> atau halaman detail alat.
                    Bila memang ada salah scan sebelumnya, minta Admin melakukan koreksi.
                </p>
            </div>
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('scanStation', () => ({
        cameraOn: false,
        cameraError: '',
        scanner: null,
        refocusTimer: null,

        init() {
            this.refocus();
        },

        refocus() {
            this.$nextTick(() => this.$refs.input?.focus());
        },

        // Scanner HID kadang memicu blur sesaat; fokus dikembalikan supaya
        // petugas bisa scan beruntun tanpa harus klik kolom input lagi.
        // TAPI: jangan rebut fokus balik kalau blur itu terjadi karena petugas
        // sengaja klik kontrol lain (dropdown tahap, tombol kamera, dst) —
        // dulu bug-nya persis itu: dropdown "Tahap/Stasiun" langsung menutup
        // sendiri sebelum sempat memilih opsi, karena fokus direbut paksa.
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

        lastCode: '',
        lastAt: 0,

        onDecode(code) {
            // Kamera membaca terus-menerus; kode yang sama dalam 2 detik diabaikan
            // supaya satu label tidak terkirim berkali-kali.
            const now = Date.now();
            if (code === this.lastCode && now - this.lastAt < 2000) return;

            this.lastCode = code;
            this.lastAt = now;

            $wire.handleScan(code, 'qr_camera');
        },

        destroy() {
            this.stopCamera();
        },
    }));
</script>
@endscript
