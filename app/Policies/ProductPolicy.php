<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasAccessToWarehouse($product->warehouse);
    }

    /**
     * $warehouse passed as context: Gate::authorize('create', [Product::class, $warehouse]).
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

    public function update(User $user, Product $product): bool
    {
        return $user->hasRole('Super Admin')
            || ($user->hasRole('Warehouse Admin') && $user->hasAccessToWarehouse($product->warehouse));
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasRole('Super Admin')
            || ($user->hasRole('Warehouse Admin') && $user->hasAccessToWarehouse($product->warehouse));
    }
}
