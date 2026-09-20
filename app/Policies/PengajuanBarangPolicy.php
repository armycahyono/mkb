<?php

namespace App\Policies;

use App\Models\PengajuanBarang;
use App\Models\User;

class PengajuanBarangPolicy
{
    /**
     * Determine whether the user can view any PengajuanBarang.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('access-data-pokok');
    }

    /**
     * Determine whether the user can view a specific PengajuanBarang.
     */
    public function view(User $user, PengajuanBarang $pengajuanBarang): bool
    {
        // Users can view if they have access or they own the record.
        return $user->can('access-data-pokok') || $user->id === $pengajuanBarang->pemohon_id;
    }

    /**
     * Determine whether the user can create a PengajuanBarang.
     */
    public function create(User $user): bool
    {
        return $user->can('access-data-pokok');
    }

    /**
     * Determine whether the user can update the PengajuanBarang.
     */
    public function update(User $user, PengajuanBarang $pengajuanBarang): bool
    {
        return $user->id === $pengajuanBarang->pemohon_id || $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the PengajuanBarang.
     */
    public function delete(User $user, PengajuanBarang $pengajuanBarang): bool
    {
        return $user->id === $pengajuanBarang->pemohon_id || $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can perform realisasi.
     */
    public function realisasi(User $user, ?PengajuanBarang $pengajuanBarang = null): bool
    {
        return $user->can('access-bosp-kas') || $user->isWakaSarpras() || $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PengajuanBarang $pengajuanBarang): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PengajuanBarang $pengajuanBarang): bool
    {
        return false;
    }
}
