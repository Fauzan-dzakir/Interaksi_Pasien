<div>
    <x-page-header title="Stasiun Scan" subtitle="Pindai barcode untuk memindahkan alat ke tahap berikutnya." />

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

                @if ($stationEnum === \App\Enums\ScanStation::Sterilizing)
                    <div class="mt-4">
                        <label class="field-label" for="method">Metode Sterilisasi</label>
                        <select wire:model="method" id="method" class="field-input sm:max-w-xs">
                            @foreach ($methodOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-400">Tercatat pada tiap alat dan ikut muncul di laporan PDF.</p>
                    </div>
                @endif
            </div>

            {{--
                Satu handler untuk dua jalur input:
                - scanner barcode fisik (HID) "mengetik" kode lalu menekan Enter di input ini,
                - kamera memanggil $wire.handleScan(...) lewat Alpine.
            --}}
            <div class="card p-5" x-data="scanStation()">
                <div class="flex items-center justify-between gap-3">
                    <label class="field-label !mb-0" for="scan-input">Scan atau ketik barcode</label>
                    <button type="button" x-on:click="toggleCamera()" class="btn-secondary !px-3 !py-1.5"
                            x-text="cameraOn ? 'Tutup Kamera' : 'Buka Kamera'"></button>
                </div>

                <form wire:submit="handleScan(null, 'hid_scanner')" class="mt-2">
                    <input wire:model="code" id="scan-input" x-ref="input" type="text"
                           autocomplete="off" autocapitalize="characters" spellcheck="false"
                           class="field-input font-mono text-lg tracking-wider"
                           placeholder="CSSD-XXXXXXXX" x-on:blur="scheduleRefocus()">
                </form>

                <p class="mt-2 text-xs text-slate-400">
                    Scanner fisik cukup diarahkan ke label, kode terisi dan terkirim otomatis.
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

            @if ($stationEnum === \App\Enums\ScanStation::Complete)
                <div class="card border-leaf-300 bg-leaf-50/60 p-5">
                    <h2 class="text-sm font-semibold text-leaf-900">Selesaikan Satu Batch Sekaligus</h2>
                    <p class="mt-1 text-sm text-leaf-800">
                        Satu muatan autoclave biasanya keluar bersamaan, pakai ini supaya tidak perlu scan satu per satu.
                    </p>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <select wire:model="bulkBatchId" class="field-input !bg-white sm:max-w-sm">
                            <option value="">Pilih batch</option>
                            @foreach ($sterilizingBatches as $batch)
                                <option value="{{ $batch->id }}">
                                    {{ $batch->name }} ({{ $batch->unit->name }}), {{ $batch->assets_count }} alat
                                </option>
                            @endforeach
                        </select>
                        <button wire:click="completeBatch" class="btn-primary shrink-0">Selesaikan Batch</button>
                    </div>

                    @error('bulkBatchId') <p class="field-error">{{ $message }}</p> @enderror

                    @if ($sterilizingBatches->isEmpty())
                        <p class="mt-2 text-xs text-leaf-700">Tidak ada batch yang sedang disterilkan.</p>
                    @endif
                </div>
            @endif

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
                                'bg-leaf-100 text-leaf-700' => $entry['status'] === 'success',
                                'bg-red-100 text-red-700' => $entry['status'] === 'error',
                                'bg-slate-100 text-slate-500' => $entry['status'] === 'info',
                            ])>{{ $entry['status'] === 'success' ? '✓' : ($entry['status'] === 'error' ? '!' : '·') }}</span>

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
                            Belum ada scan. Arahkan scanner ke barcode untuk mulai.
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
                    <div class="rounded-lg bg-brand-50 p-4 ring-1 ring-brand-200">
                        <div class="text-xs font-medium text-brand-800">Sedang di tahap ini</div>
                        <div class="mt-0.5 text-2xl font-semibold text-brand-900">{{ $atStationCount }}</div>
                    </div>
                </div>
            </div>

            <div class="card border-slate-200 bg-slate-50/60 p-5">
                <h2 class="text-sm font-semibold text-slate-900">Kalau scan ditolak</h2>
                <ul class="mt-2 space-y-1.5 text-sm text-slate-600">
                    <li class="flex gap-2">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-400"></span>
                        Alat belum melewati tahap sebelumnya. Ini disengaja demi keselamatan pasien.
                    </li>
                    <li class="flex gap-2">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-400"></span>
                        <span>Selesai dekontaminasi? Lewat
                        <a href="{{ route('cssd.new-barcode') }}" wire:navigate class="font-medium text-brand-700 underline">Scan Barcode Baru</a>
                        dulu.</span>
                    </li>
                </ul>
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
        //
        // Tapi fokus TIDAK boleh direbut saat petugas sedang memakai kontrol lain,
        // misalnya membuka dropdown stasiun, karena itu menutup dropdown-nya.
        scheduleRefocus() {
            clearTimeout(this.refocusTimer);
            this.refocusTimer = setTimeout(() => {
                if (this.cameraOn || this.isUserBusyElsewhere()) return;
                this.$refs.input?.focus();
            }, 150);
        },

        isUserBusyElsewhere() {
            const active = document.activeElement;

            if (!active || active === document.body || active === this.$refs.input) {
                return false;
            }

            return active.matches('input, select, textarea, button, a, [tabindex]');
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

            $wire.handleScan(code, 'qr_camera');
        },

        destroy() {
            this.stopCamera();
        },
    }));
</script>
@endscript
