<div wire:poll.15s>
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
                onCamera dikustom (bukan pakai default komponen) supaya metode input
                tetap tercatat 'qr_camera' vs 'hid_scanner' di jejak audit.
            --}}
            <div class="card p-5">
                <x-scan-input
                    submit="handleScan(null, 'hid_scanner')"
                    on-camera="$wire.handleScan(code, 'qr_camera')"
                    label="Scan atau ketik kode label"
                    input-class="field-input font-mono text-lg tracking-wider"
                />

                <p class="mt-2 text-xs text-slate-400">
                    Scanner fisik cukup diarahkan ke label — kode terisi dan terkirim otomatis.
                    Kolom ini juga bisa diketik manual bila label rusak.
                </p>
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
