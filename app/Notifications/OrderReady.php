<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Memberi tahu unit bahwa pesanan cucinya selesai, pengganti kebiasaan
 * menelepon CSSD untuk menanyakan "alat saya sudah selesai belum?".
 */
class OrderReady extends Notification
{
    use Queueable;

    public function __construct(public readonly Order $order) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_ready',
            'title' => 'Pesanan cuci selesai',
            'message' => sprintf(
                'Alat pada pesanan %s selesai disterilkan dan siap kembali ke unit Anda. Mohon konfirmasi setelah alat diterima.',
                $this->order->order_number,
            ),
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'url' => route('unit.orders.show', $this->order->id),
        ];
    }
}
