<?php

namespace App\Enums;

enum UserRole: string
{
    /** Admin sistem: manajemen user, master data, koreksi human error. */
    case Admin = 'admin';

    /** Petugas CSSD: pendataan barang & eksekusi alur zona kotor -> zona bersih. */
    case CssdStaff = 'cssd_staff';

    /** Dokter/Perawat/Nakes di unit pengirim: buat order & pantau status. */
    case Nakes = 'nakes';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::CssdStaff => 'Petugas CSSD',
            self::Nakes => 'Dokter / Perawat / Nakes',
        };
    }

    /** Role yang wajib terikat ke satu unit asal (Admin tidak terikat unit). */
    public function requiresUnit(): bool
    {
        return $this !== self::Admin;
    }

    /** Route tujuan setelah login, sesuai peran. */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::CssdStaff => 'cssd.dashboard',
            self::Nakes => 'unit.dashboard',
        };
    }

    /** @return array<string, string> value => label, untuk dropdown form. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}
