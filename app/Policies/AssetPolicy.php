<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy
{
    public function view(User $user, Asset $asset): bool
    {
        if ($user->isAdmin() || $user->isCssdStaff()) {
            return true;
        }

        // Nakes boleh melihat alat yang terikat batch unitnya, atau stok bebas
        // (stok bebas memang sengaja terbuka supaya unit tahu apa yang bisa dipesan).
        return $asset->batch_id === null
            || ($user->unit_id !== null && $asset->batch?->unit_id === $user->unit_id);
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
