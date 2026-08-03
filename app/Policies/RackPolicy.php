<?php

namespace App\Policies;

use App\Models\Rack;
use App\Models\User;

class RackPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Rack $rack): bool
    {
        return $user->hasAccessToWarehouse($rack->warehouse);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->hasRole('Warehouse Admin');
    }

    public function update(User $user, Rack $rack): bool
    {
        return $user->hasRole('Super Admin')
            || ($user->hasRole('Warehouse Admin') && $user->hasAccessToWarehouse($rack->warehouse));
    }

    public function delete(User $user, Rack $rack): bool
    {
        return $user->hasRole('Super Admin')
            || ($user->hasRole('Warehouse Admin') && $user->hasAccessToWarehouse($rack->warehouse));
    }
}
