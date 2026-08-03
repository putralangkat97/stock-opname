<?php

namespace App\Policies;

use App\Models\BinLocation;
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

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->hasRole('Warehouse Admin');
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
