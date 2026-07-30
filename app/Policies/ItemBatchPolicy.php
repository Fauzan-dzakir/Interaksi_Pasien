<?php

namespace App\Policies;

use App\Models\ItemBatch;
use App\Models\User;

class ItemBatchPolicy
{
    public function view(User $user, ItemBatch $batch): bool
    {
        if ($user->isAdmin() || $user->isCssdStaff()) {
            return true;
        }

        return $user->unit_id !== null && $user->unit_id === $batch->origin_unit_id;
    }

    /** Perpindahan tahap di CSSD hanya boleh dieksekusi petugas CSSD/Admin. */
    public function advanceStage(User $user): bool
    {
        return $user->isCssdStaff() || $user->isAdmin();
    }

    /** Koreksi lintas alur (mis. menandai hilang) khusus Admin. */
    public function override(User $user): bool
    {
        return $user->isAdmin();
    }
}
