<?php

namespace App\Policies;

use App\Models\Pickup;
use App\Models\User;

class PickupPolicy
{
    public function view(User $user, Pickup $pickup): bool
    {
        if ($user->isAdmin() || $user->isCssdStaff()) {
            return true;
        }

        return $user->unit_id !== null && $user->unit_id === $pickup->origin_unit_id;
    }

    /** Hanya nakes dari unit tujuan yang boleh menyatakan "Diterima". */
    public function confirmReceipt(User $user, Pickup $pickup): bool
    {
        return $user->isNakes()
            && $user->unit_id === $pickup->origin_unit_id
            && ! $pickup->isConfirmed();
    }
}
