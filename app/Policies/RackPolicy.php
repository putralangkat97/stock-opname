<?php

namespace App\Policies;

use App\Models\Rack;
use App\Models\User;
use App\Models\Warehouse;

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

    /**
     * $warehouse is passed as context from the controller
     * (Gate::authorize('create', [Rack::class, $warehouse])) so a Warehouse
     * Admin can only create racks in a warehouse they actually have access to
     * — checking the role alone wasn't enough.
     */
    public function create(User $user, ?Warehouse $warehouse = null): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->hasRole('Warehouse Admin')
            && $warehouse !== null
            && $user->hasAccessToWarehouse($warehouse);
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
