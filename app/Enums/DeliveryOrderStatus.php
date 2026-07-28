<?php

namespace App\Enums;

enum DeliveryOrderStatus: string
{
    /** Unit sudah kirim, CSSD belum melakukan pendataan fisik. */
    case PendingCssdIntake = 'pending_cssd_intake';

    /** CSSD sudah mendata isi kiriman; rincian alat mulai terlihat oleh unit. */
    case IntakeRecorded = 'intake_recorded';

    /** Alat sedang diproses di zona kotor/bersih. */
    case Processing = 'processing';

    /** Seluruh alat pada order ini sudah diterima kembali oleh unit. */
    case Completed = 'completed';

    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingCssdIntake => 'Menunggu Pendataan CSSD',
            self::IntakeRecorded => 'Sudah Didata',
            self::Processing => 'Sedang Diproses',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    /** Kelas warna Tailwind untuk pill status. */
    public function colorClasses(): string
    {
        return match ($this) {
            self::PendingCssdIntake => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::IntakeRecorded => 'bg-sky-50 text-sky-700 ring-sky-200',
            self::Processing => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
            self::Completed => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::Cancelled => 'bg-slate-100 text-slate-500 ring-slate-200',
        };
    }

    /** Rincian alat baru boleh dilihat unit setelah CSSD selesai mendata. */
    public function detailVisibleToUnit(): bool
    {
        return $this !== self::PendingCssdIntake && $this !== self::Cancelled;
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Completed, self::Cancelled], true);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->label()])
            ->all();
    }
}
