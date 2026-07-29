<?php

namespace App\Enums;

/**
 * Siklus hidup satu aset (satu set fisik atau satu alat satuan).
 *
 * Siklus ini berputar terus:
 *   available -> reserved -> ready_for_handover|in_transit -> at_unit -> in_use
 *   -> return_pending -> washing -> packed -> sterilizing -> available
 *
 * Catatan penting: selama pencucian, barcode LAMA masih berlaku di sistem.
 * Label fisiknya memang dibuang saat kemasan dibuka, tapi karena sudah discan
 * masuk, sistem tetap mengenalinya, barcode baru menggantikan hanya saat discan
 * di menu "Scan Barcode Baru". Jadi pelacakan tidak pernah terputus.
 */
enum AssetStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case ReadyForHandover = 'ready_for_handover';
    case InTransit = 'in_transit';
    case AtUnit = 'at_unit';
    case InUse = 'in_use';
    case ReturnPending = 'return_pending';
    case Washing = 'washing';
    case Packed = 'packed';
    case Sterilizing = 'sterilizing';

    // Status penutup
    case Lost = 'lost';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tersedia di Gudang Steril',
            self::Reserved => 'Disiapkan CSSD',
            self::ReadyForHandover => 'Siap Diambil',
            self::InTransit => 'Sedang Dikirimkan',
            self::AtUnit => 'Di Unit',
            self::InUse => 'Sedang Dipakai',
            self::ReturnPending => 'Menunggu Konfirmasi CSSD',
            self::Washing => 'Pencucian & Dekontaminasi',
            self::Packed => 'Bersih & Dikemas',
            self::Sterilizing => 'Proses Sterilisasi',
            self::Lost => 'Hilang',
            self::Retired => 'Tidak Dipakai Lagi',
        };
    }

    public function zone(): ZoneBucket
    {
        return match ($this) {
            self::Available, self::Reserved, self::ReadyForHandover => ZoneBucket::ReadyForDistribution,
            self::InTransit, self::AtUnit, self::InUse => ZoneBucket::AtUnit,
            self::ReturnPending, self::Washing => ZoneBucket::Dirty,
            self::Packed, self::Sterilizing => ZoneBucket::Clean,
            self::Lost, self::Retired => ZoneBucket::Closed,
        };
    }

    public function colorClasses(): string
    {
        return match ($this) {
            self::Lost => 'bg-red-50 text-red-700 ring-red-200',
            self::Retired => 'bg-slate-100 text-slate-500 ring-slate-200',
            self::Available => 'bg-leaf-50 text-leaf-700 ring-leaf-200',
            default => $this->zone()->colorClasses(),
        };
    }

    public function isActive(): bool
    {
        return ! in_array($this, [self::Lost, self::Retired], true);
    }

    /** Aset bisa dipesan unit hanya saat benar-benar tersedia di gudang steril. */
    public function isOrderable(): bool
    {
        return $this === self::Available;
    }

    /** Aset sedang berada di tangan unit (bukan di CSSD). */
    public function isAtUnit(): bool
    {
        return in_array($this, [self::InTransit, self::AtUnit, self::InUse], true);
    }

    /**
     * Peta transisi yang diizinkan. Ditulis eksplisit supaya scan di tahap yang
     * salah ditolak tegas, konteks keselamatan pasien tidak boleh gagal diam-diam.
     *
     * @return array<int, self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Available => [self::Reserved],
            // CSSD memilih: diambil unit, atau diantar langsung.
            self::Reserved => [self::ReadyForHandover, self::InTransit, self::Available],
            self::ReadyForHandover => [self::AtUnit],
            self::InTransit => [self::AtUnit],
            // Alat yang tidak jadi dipakai langsung dikembalikan tanpa perlu discan pakai.
            self::AtUnit => [self::InUse, self::ReturnPending],
            self::InUse => [self::ReturnPending],
            self::ReturnPending => [self::Washing],
            self::Washing => [self::Packed],
            self::Packed => [self::Sterilizing],
            self::Sterilizing => [self::Available],
            self::Lost, self::Retired => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedNext(), true);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->label()])
            ->all();
    }
}
