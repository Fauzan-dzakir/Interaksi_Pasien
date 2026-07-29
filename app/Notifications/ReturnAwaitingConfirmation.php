<?php

namespace App\Notifications;

use App\Models\ReturnShipment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Memberi tahu CSSD bahwa ada kiriman alat kotor yang menunggu dikonfirmasi.
 * Selama belum dikonfirmasi, alat menggantung, inilah sisi kedua dari
 * konfirmasi dua sisi yang membuat selisih serah terima cepat ketahuan.
 */
class ReturnAwaitingConfirmation extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ReturnShipment $shipment,
        public readonly int $assetCount,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'return_awaiting',
            'title' => 'Kiriman alat kotor menunggu konfirmasi',
            'message' => sprintf(
                '%s dari %s berisi %d alat. Mohon konfirmasi penerimaannya.',
                $this->shipment->return_number,
                $this->shipment->unit->name,
                $this->assetCount,
            ),
            'return_id' => $this->shipment->id,
            'return_number' => $this->shipment->return_number,
            'url' => route('cssd.returns'),
        ];
    }
}
