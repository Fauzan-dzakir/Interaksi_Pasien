<div>
    <x-page-header title="Laporan Proses Sterilisasi"
                   subtitle="Pengganti digital formulir kertas, tahapan, jam, dan petugas terisi otomatis.">
        <x-slot:actions>
            <button wire:click="create" class="btn-primary">+ Buat Laporan</button>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Metode</th>
                        <th>Batch / Unit</th>
                        <th>Alat</th>
                        <th>Indikator Biologi</th>
                        <th>Dibuat</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr wire:key="sr-{{ $record->id }}">
                            <td class="font-mono text-xs font-medium text-slate-900">{{ $record->record_number }}</td>
                            <td>{{ $record->method->label() }}</td>
                            <td class="text-xs text-slate-500">
                                {{ $record->batch?->name ?? 'tidak ada' }}
                                @if ($record->unit) <br>{{ $record->unit->name }} @endif
                            </td>
                            <td>{{ $record->assets_count }}</td>
                            <td><x-state-pill :state="$record->biological_indicator_result" /></td>
                            <td class="text-xs text-slate-500">
                                {{ $record->created_at->format('d/m/Y H:i') }}<br>
                                <span class="text-slate-400">{{ $record->createdBy->name }}</span>
                            </td>
                            <td>
                                <div class="flex justify-end gap-2">
                                    <button wire:click="startResult({{ $record->id }})"
                                            class="btn-secondary !px-3 !py-1.5">Hasil BI</button>
                                    <a href="{{ route('cssd.reports.pdf', $record->id) }}" target="_blank"
                                       class="btn-primary !px-3 !py-1.5">PDF</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="7" title="Belum ada laporan sterilisasi"
                                       description="Buat laporan untuk satu muatan sterilisasi, lalu cetak PDF-nya." />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($records->hasPages())
            <div class="border-t border-slate-200 p-4">{{ $records->links() }}</div>
        @endif
    </div>

    <x-modal :show="$showForm" title="Buat Laporan Sterilisasi" max-width="max-w-3xl">
        <form wire:submit="save" id="report-form" class="space-y-4">
            <p class="rounded-lg bg-sky-50 px-3 py-2 text-sm text-sky-800 ring-1 ring-sky-200">
                Anda hanya perlu mengisi metode dan memilih alat. Tahapan proses, jam, dan nama
                petugas akan diambil otomatis dari jejak audit saat PDF dicetak.
            </p>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="field-label" for="r-method">Metode</label>
                    <select wire:model="method" id="r-method" class="field-input">
                        @foreach ($methodOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('method') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="field-label" for="r-batch">Batch</label>
                    <select wire:model.live="batchId" id="r-batch" class="field-input">
                        <option value="">Semua</option>
                        @foreach ($batchOptions as $batch)
                            <option value="{{ $batch->id }}">{{ $batch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="field-label" for="r-unit">Unit</label>
                    <select wire:model="unitId" id="r-unit" class="field-input">
                        <option value="">Tidak spesifik</option>
                        @foreach ($unitOptions as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="field-label">Alat dalam Muatan Ini</label>
                @error('selectedAssets') <p class="field-error mb-2">{{ $message }}</p> @enderror

                <div class="max-h-64 space-y-1 overflow-y-auto rounded-lg border border-slate-200 p-2">
                    @forelse ($candidates as $asset)
                        <label wire:key="ca-{{ $asset->id }}"
                               class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-1.5 hover:bg-slate-50">
                            <input type="checkbox" wire:model="selectedAssets" value="{{ $asset->id }}"
                                   class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <span class="font-mono text-xs text-slate-600">{{ $asset->current_code }}</span>
                            <span class="min-w-0 flex-1 truncate text-sm text-slate-700">{{ $asset->displayName() }}</span>
                            <x-state-pill :state="$asset->status" />
                        </label>
                    @empty
                        <p class="px-2 py-6 text-center text-sm text-slate-400">
                            Tidak ada alat pada tahap sterilisasi.
                        </p>
                    @endforelse
                </div>
            </div>

            <div>
                <label class="field-label" for="r-notes">Catatan</label>
                <textarea wire:model="notes" id="r-notes" rows="2" class="field-input" placeholder="Opsional"></textarea>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" wire:click="$set('showForm', false)" class="btn-secondary">Batal</button>
            <button type="submit" form="report-form" class="btn-primary">Buat Laporan</button>
        </x-slot:footer>
    </x-modal>

    <x-modal :show="$editingResultId !== null" title="Hasil Uji Indikator Biologi">
        <form wire:submit="saveResult" id="bi-form" class="space-y-4">
            <p class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900 ring-1 ring-amber-200">
                Hasil <strong>Tidak Baik</strong> berarti muatan ini <strong>tidak boleh dipakai ke pasien</strong>.
                Segera tindak lanjuti sesuai SOP.
            </p>

            <div>
                <label class="field-label" for="bi-result">Hasil</label>
                <select wire:model="biResult" id="bi-result" class="field-input">
                    @foreach ($resultOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('biResult') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="field-label" for="bi-notes">Catatan</label>
                <textarea wire:model="biNotes" id="bi-notes" rows="3" class="field-input"
                          placeholder="mis. inkubasi 24 jam, hasil dibaca petugas jaga malam"></textarea>
                @error('biNotes') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </form>

        <x-slot:footer>
            <button type="button" wire:click="$set('editingResultId', null)" class="btn-secondary">Batal</button>
            <button type="submit" form="bi-form" class="btn-primary">Simpan Hasil</button>
        </x-slot:footer>
    </x-modal>
</div>
