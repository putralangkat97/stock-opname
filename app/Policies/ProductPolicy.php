<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

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

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->hasRole('Warehouse Admin');
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
