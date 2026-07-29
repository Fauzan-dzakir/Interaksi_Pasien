<?php

namespace App\Exceptions;

use App\Enums\AssetStatus;
use RuntimeException;

/**
 * Dilempar saat scan mencoba memindahkan aset ke tahap yang tidak sah,
 * mis. alat yang masih di unit discan di stasiun sterilisasi.
 * Kegagalan seperti ini WAJIB terlihat jelas oleh petugas, jangan didiamkan.
 *
 * Catatan: properti diberi nama $assetCode, bukan $code, karena Exception
 * sudah punya properti $code bawaan yang tidak readonly.
 */
class InvalidTransitionException extends RuntimeException
{
    public function __construct(
        public readonly AssetStatus $from,
        public readonly AssetStatus $to,
        public readonly string $assetCode,
    ) {
        parent::__construct(sprintf(
            'Alat %s berstatus "%s", tidak bisa langsung dipindah ke "%s".',
            $assetCode,
            $from->label(),
            $to->label(),
        ));
    }
}
