<?php

namespace App\Notifications;

use App\Models\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Memberi tahu unit bahwa alat batch-nya sudah selesai disterilkan dan siap dipesan lagi. */
class AssetsReadyInStock extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Batch $batch,
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
            'type' => 'assets_ready',
            'title' => 'Alat selesai disterilkan',
            'message' => sprintf(
                '%d alat pada batch "%s" sudah selesai disterilkan dan siap dipesan kembali.',
                $this->assetCount,
                $this->batch->name,
            ),
            'batch_id' => $this->batch->id,
            'url' => route('unit.progress'),
        ];
    }
}
