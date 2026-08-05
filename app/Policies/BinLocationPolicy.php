<?php

namespace App\Policies;

use App\Models\BinLocation;
use App\Models\Rack;
use App\Models\User;

class BinLocationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BinLocation $bin): bool
    {
        return $user->hasAccessToWarehouse($bin->warehouse);
    }

    /**
     * $rack passed as context: Gate::authorize('create', [BinLocation::class, $rack]).
     */
    public function create(User $user, ?Rack $rack = null): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->hasRole('Warehouse Admin')
            && $rack !== null
            && $user->hasAccessToWarehouse($rack->warehouse);
    }

    public function update(User $user, BinLocation $bin): bool
    {
        return $user->hasRole('Super Admin')
            || ($user->hasRole('Warehouse Admin') && $user->hasAccessToWarehouse($bin->warehouse));
    }

    public function delete(User $user, BinLocation $bin): bool
    {
        return $user->hasRole('Super Admin')
            || ($user->hasRole('Warehouse Admin') && $user->hasAccessToWarehouse($bin->warehouse));
    }
}
