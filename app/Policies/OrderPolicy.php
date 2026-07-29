<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /** Nakes hanya boleh melihat pesanan unitnya sendiri; CSSD & Admin lintas unit. */
    public function view(User $user, Order $order): bool
    {
        if ($user->isAdmin() || $user->isCssdStaff()) {
            return true;
        }

        return $user->unit_id !== null && $user->unit_id === $order->unit_id;
    }

    public function create(User $user): bool
    {
        return $user->isNakes() && $user->unit_id !== null;
    }

    /** Penyiapan & serah terima adalah wewenang CSSD (Admin boleh untuk koreksi). */
    public function prepare(User $user, Order $order): bool
    {
        return ($user->isCssdStaff() || $user->isAdmin()) && $order->status->isOpen();
    }

    /** Hanya nakes unit tujuan yang boleh menyatakan pesanan sudah diterima. */
    public function confirmReceipt(User $user, Order $order): bool
    {
        return $user->isNakes()
            && $user->unit_id === $order->unit_id
            && in_array($order->status, [OrderStatus::ReadyForPickup, OrderStatus::Delivering], true);
    }

    /** Pesanan hanya bisa dibatalkan selama alatnya belum diterima CSSD. */
    public function cancel(User $user, Order $order): bool
    {
        if ($order->status !== OrderStatus::Pending) {
            return false;
        }

        if ($user->isAdmin() || $user->isCssdStaff()) {
            return true;
        }

        return $user->isNakes() && $user->unit_id === $order->unit_id;
    }
}
