<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->hasAccessToWarehouse($warehouse);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->hasRole('Super Admin');
    }
}
