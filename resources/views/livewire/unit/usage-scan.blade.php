<div>
    <x-page-header title="Scan Pemakaian Alat"
                   subtitle="Tandai alat yang benar-benar dipakai agar posisinya tercatat.">
        <x-slot:actions>
            <a href="{{ route('unit.orders.create') }}" wire:navigate class="btn-secondary">Pesan Cuci</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="card p-5" x-data="usageScan()">
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
                    Alat yang tidak jadi dipakai <strong>tidak perlu discan</strong>,
                    sistem tetap mengenalinya saat dikembalikan ke CSSD.
                </p>

                <div x-show="cameraOn" x-cloak class="mt-4">
                    <video x-ref="video" class="w-full rounded-lg bg-slate-900" style="max-height: 320px"></video>
                    <p x-show="cameraError" x-cloak class="field-error" x-text="cameraError"></p>
                    <p class="mt-1.5 text-xs text-slate-400">Kamera butuh koneksi HTTPS.</p>
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
                        <li class="px-5 py-10 text-center text-sm text-slate-400">Belum ada scan.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="card p-5">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Alat di Unit Anda ({{ $atUnit->count() }})</h2>

            @forelse ($atUnit as $asset)
                <div wire:key="au-{{ $asset->id }}"
                     class="mb-2 rounded-lg border border-slate-200 px-3 py-2">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-mono text-xs text-slate-600">{{ $asset->current_code }}</span>
                        <x-state-pill :state="$asset->status" />
                    </div>
                    <div class="mt-0.5 truncate text-sm text-slate-700">{{ $asset->displayName() }}</div>
                </div>
            @empty
                <p class="rounded-lg border border-dashed border-slate-300 px-3 py-6 text-center text-sm text-slate-400">
                    Tidak ada alat di unit Anda saat ini.
                </p>
            @endforelse
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('usageScan', () => ({
        cameraOn: false,
        cameraError: '',
        scanner: null,
        refocusTimer: null,
        lastCode: '',
        lastAt: 0,

        init() { this.refocus(); },

        refocus() {
            this.$nextTick(() => this.$refs.input?.focus());
        },

        // Fokus dikembalikan agar bisa scan beruntun, tapi tidak boleh direbut
        // saat petugas sedang memakai kontrol lain di halaman ini.
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
            const now = Date.now();
            if (code === this.lastCode && now - this.lastAt < 2000) return;

            this.lastCode = code;
            this.lastAt = now;

            $wire.handleScan(code, 'qr_camera');
        },

        destroy() { this.stopCamera(); },
    }));
</script>
@endscript
