<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared logic for master data that has no warehouse scoping and no
 * Warehouse Admin write access — Category, Brand, Unit. Everyone can read;
 * only Super Admin can write.
 */
abstract class MasterDataPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Model $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->hasRole('Super Admin');
    }
}
