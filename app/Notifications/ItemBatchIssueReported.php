<?php

namespace App\Notifications;

use App\Models\ItemBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Memberi tahu unit pemilik bahwa salah satu alatnya bermasalah — dinyatakan
 * hilang/rusak (Koreksi Admin) atau tidak lulus uji checklist QC. Tanpa ini,
 * unit tidak akan pernah tahu alatnya bermasalah kecuali bertanya manual ke CSSD.
 */
class ItemBatchIssueReported extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ItemBatch $batch,
        public readonly string $title,
        public readonly string $message,
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
            'type' => 'item_issue',
            'title' => $this->title,
            'message' => $this->message,
            'batch_id' => $this->batch->id,
            'public_code' => $this->batch->public_code,
            'url' => route('batches.show', $this->batch->id),
        ];
    }
}
