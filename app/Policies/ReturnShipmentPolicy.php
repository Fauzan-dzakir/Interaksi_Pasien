<?php

namespace App\Policies;

use App\Models\ReturnShipment;
use App\Models\User;

class ReturnShipmentPolicy
{
    public function view(User $user, ReturnShipment $shipment): bool
    {
        if ($user->isAdmin() || $user->isCssdStaff()) {
            return true;
        }

        return $user->unit_id !== null && $user->unit_id === $shipment->unit_id;
    }

    public function create(User $user): bool
    {
        return $user->isNakes() && $user->unit_id !== null;
    }

    /** Sisi kedua dari konfirmasi dua sisi, hanya CSSD yang boleh menutupnya. */
    public function confirmReceipt(User $user, ReturnShipment $shipment): bool
    {
        return ($user->isCssdStaff() || $user->isAdmin()) && ! $shipment->isConfirmed();
    }
}
