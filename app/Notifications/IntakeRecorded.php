<?php

namespace App\Notifications;

use App\Models\DeliveryOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Memberi tahu unit pengirim bahwa CSSD sudah selesai mendata isi kiriman,
 * sehingga rincian alat kini terbuka untuk dilihat unit.
 */
class IntakeRecorded extends Notification
{
    use Queueable;

    public function __construct(public readonly DeliveryOrder $order) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'intake_recorded',
            'title' => 'Pendataan CSSD selesai',
            'message' => "Order {$this->order->order_number} sudah didata CSSD. Rincian alat kini bisa dilihat.",
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'url' => route('unit.orders.show', $this->order->id),
        ];
    }
}
