<?php

namespace App\Exceptions;

use App\Enums\ItemBatchStatus;
use RuntimeException;

/**
 * Dilempar saat scan mencoba memindahkan batch ke tahap yang tidak sah,
 * mis. alat yang masih di zona kotor discan di stasiun cek label steril.
 * Kegagalan seperti ini WAJIB terlihat jelas oleh petugas — jangan didiamkan.
 */
class InvalidTransitionException extends RuntimeException
{
    public function __construct(
        public readonly ItemBatchStatus $from,
        public readonly ItemBatchStatus $to,
        public readonly string $publicCode,
    ) {
        parent::__construct(sprintf(
            'Alat %s berstatus "%s" — tidak bisa langsung dipindah ke "%s".',
            $publicCode,
            $from->label(),
            $to->label(),
        ));
    }
}
