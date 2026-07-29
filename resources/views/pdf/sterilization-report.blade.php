<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Sterilisasi {{ $record->record_number }}</title>
    <style>
        /* DomPDF hanya mendukung CSS dasar, jadi gaya ditulis sederhana & eksplisit. */
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; margin: 0; }
        .header { border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 12px; }
        .hospital { font-size: 14px; font-weight: bold; }
        .subtitle { font-size: 9px; color: #555; }
        .doc-title { font-size: 12px; font-weight: bold; text-align: center; margin: 10px 0 12px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #333; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #eee; font-size: 9px; text-transform: uppercase; }
        .meta td { border: none; padding: 2px 0; }
        .meta .label { width: 110px; color: #555; }
        .section { font-size: 10px; font-weight: bold; margin: 12px 0 5px; text-transform: uppercase; }
        .method span { display: inline-block; margin-right: 14px; }
        .box { display: inline-block; width: 10px; height: 10px; border: 1px solid #333; text-align: center;
               line-height: 10px; font-size: 9px; margin-right: 3px; }
        .note { border: 1px solid #333; padding: 6px; min-height: 40px; }
        .result-baik { font-weight: bold; }
        .result-tidak { font-weight: bold; color: #b00; }
        .warning { border: 1px solid #b00; background: #fee; padding: 6px; margin-bottom: 10px; color: #b00; font-weight: bold; }
        .sign { width: 100%; margin-top: 18px; }
        .sign td { border: none; text-align: center; font-size: 9px; padding-top: 4px; }
        .sign .line { border-bottom: 1px solid #333; height: 42px; }
        .footer { margin-top: 14px; font-size: 8px; color: #666; border-top: 1px solid #ccc; padding-top: 5px; }
        .auto { font-size: 8px; color: #666; font-style: italic; }
    </style>
</head>
<body>

<div class="header">
    <div class="hospital">RS KEMENKES SURABAYA</div>
    <div class="subtitle">Instalasi CSSD, Central Sterile Supply Department</div>
</div>

<div class="doc-title">Formulir Proses Sterilisasi</div>

@if ($record->biological_indicator_result === \App\Enums\BiologicalIndicatorResult::TidakBaik)
    <div class="warning">
        PERHATIAN: Hasil indikator biologi TIDAK BAIK, muatan ini tidak boleh digunakan untuk pasien.
    </div>
@endif

<table class="meta">
    <tr>
        <td class="label">Nomor</td><td><strong>{{ $record->record_number }}</strong></td>
        <td class="label">Tanggal</td><td>{{ $record->created_at->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td class="label">Ruang / Unit</td><td>{{ $record->unit->name ?? 'tidak ada' }}</td>
        <td class="label">Batch</td><td>{{ $record->batch->name ?? 'tidak ada' }}</td>
    </tr>
    <tr>
        <td class="label">Petugas CSSD</td><td>{{ $record->createdBy->name }}</td>
        <td class="label">Jumlah Alat</td><td>{{ $record->assets->count() }}</td>
    </tr>
</table>

<div class="section">Metode Sterilisasi</div>
<div class="method">
    @foreach (\App\Enums\SterilizationMethod::cases() as $m)
        <span>
            <span class="box">{{ $record->method === $m ? 'X' : '' }}</span>{{ $m->shortLabel() }}
        </span>
    @endforeach
</div>

<div class="section">Daftar Alat / Set</div>
<table>
    <thead>
        <tr>
            <th style="width: 26px">No</th>
            <th style="width: 95px">Barcode</th>
            <th>Nama Alat / Set</th>
            <th style="width: 70px">Jenis</th>
            <th style="width: 70px">Kelengkapan</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($record->assets as $asset)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $asset->current_code }}</td>
                <td>{{ $asset->displayName() }}</td>
                <td>{{ $asset->asset_type->label() }}</td>
                <td>{{ $asset->is_complete ? 'Lengkap' : 'TIDAK LENGKAP' }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Tidak ada alat tercatat.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="section">Tahapan Proses Sterilisasi</div>
<div class="auto">
    Kolom di bawah terisi otomatis dari catatan scan petugas, tidak diisi manual.
</div>
<table>
    <thead>
        <tr>
            <th style="width: 26px">No</th>
            <th>Tahapan Proses</th>
            <th style="width: 70px">Jam Mulai</th>
            <th style="width: 70px">Jam Selesai</th>
            <th style="width: 45px">Jumlah</th>
            <th style="width: 130px">Petugas</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($stages as $stage)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $stage['label'] }}</td>
                <td>{{ $stage['started_at']?->format('d/m H:i') ?? 'tidak ada' }}</td>
                <td>{{ $stage['finished_at']?->format('d/m H:i') ?? 'tidak ada' }}</td>
                <td>{{ $stage['count'] }}</td>
                <td>{{ $stage['staff'] }}</td>
            </tr>
        @empty
            <tr><td colspan="6">Belum ada tahapan tercatat untuk alat pada laporan ini.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="section">Hasil Uji Indikator Biologi</div>
<table>
    <tr>
        <th style="width: 120px">Hasil</th>
        <td class="{{ $record->biological_indicator_result === \App\Enums\BiologicalIndicatorResult::TidakBaik ? 'result-tidak' : 'result-baik' }}">
            {{ $record->biological_indicator_result->label() }}
        </td>
        <th style="width: 90px">Tanggal Baca</th>
        <td>{{ $record->bi_read_at?->format('d/m/Y H:i') ?? 'tidak ada' }}</td>
    </tr>
    <tr>
        <th>Dibaca Oleh</th>
        <td>{{ $record->biReadBy->name ?? 'tidak ada' }}</td>
        <th>Inkubasi</th>
        <td>{{ $record->bi_incubated_at?->format('d/m/Y H:i') ?? 'tidak ada' }}</td>
    </tr>
</table>

<div class="section">Catatan</div>
<div class="note">{{ $record->notes ?? '' }}</div>

<table class="sign">
    <tr>
        <td style="width: 50%">Petugas CSSD</td>
        <td style="width: 50%">Penanggung Jawab</td>
    </tr>
    <tr>
        <td class="line"></td>
        <td class="line"></td>
    </tr>
    <tr>
        <td>( {{ $record->createdBy->name }} )</td>
        <td>( ............................................ )</td>
    </tr>
</table>

<div class="footer">
    Dicetak {{ $printedAt->format('d/m/Y H:i') }} oleh {{ $printedBy->name }} ·
    SIM Alat CSSD RS Kemenkes Surabaya ·
    Dokumen ini dihasilkan otomatis dari jejak audit sistem.
</div>

</body>
</html>
