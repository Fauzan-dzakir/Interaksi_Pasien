<?php

namespace App\Policies;

use App\Enums\DeliveryOrderStatus;
use App\Models\DeliveryOrder;
use App\Models\User;

class DeliveryOrderPolicy
{
    /** Nakes hanya boleh melihat order dari unitnya sendiri; CSSD & Admin lintas unit. */
    public function view(User $user, DeliveryOrder $order): bool
    {
        if ($user->isAdmin() || $user->isCssdStaff()) {
            return true;
        }

        return $user->unit_id !== null && $user->unit_id === $order->origin_unit_id;
    }

    public function create(User $user): bool
    {
        return $user->isNakes() && $user->unit_id !== null;
    }

    /** Pendataan fisik adalah wewenang CSSD (Admin boleh untuk koreksi). */
    public function recordIntake(User $user, DeliveryOrder $order): bool
    {
        return ($user->isCssdStaff() || $user->isAdmin())
            && $order->status === DeliveryOrderStatus::PendingCssdIntake;
    }

    /** Order hanya bisa dibatalkan selama CSSD belum mendata isinya. */
    public function cancel(User $user, DeliveryOrder $order): bool
    {
        if ($order->status !== DeliveryOrderStatus::PendingCssdIntake) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isNakes() && $user->unit_id === $order->origin_unit_id;
    }
}
