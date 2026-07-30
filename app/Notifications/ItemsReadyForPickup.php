<?php

namespace App\Notifications;

use App\Models\Pickup;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Memberi tahu unit bahwa alat sterilnya sudah siap — inilah pengganti
 * kebiasaan menelepon/WA CSSD untuk menanyakan status.
 */
class ItemsReadyForPickup extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Pickup $pickup,
        public readonly int $batchCount,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $isDirect = $this->pickup->delivery_method->value === 'direct_delivery';

        return [
            'type' => 'items_ready',
            'title' => $isDirect ? 'Alat steril dikirim ke unit' : 'Alat steril siap diambil',
            'message' => sprintf(
                '%d alat/set (nomor serah terima %s) %s. Mohon konfirmasi penerimaan di menu Penerimaan.',
                $this->batchCount,
                $this->pickup->pickup_number,
                $isDirect ? 'sedang diantar CSSD' : 'sudah siap diambil di CSSD',
            ),
            'pickup_id' => $this->pickup->id,
            'pickup_number' => $this->pickup->pickup_number,
            'url' => route('unit.pickups'),
        ];
    }
}
