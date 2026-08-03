<?php

namespace App\Policies;

use App\Enums\StockAdjustmentStatus;
use App\Models\StockAdjustment;
use App\Models\User;

class StockAdjustmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, StockAdjustment $adjustment): bool
    {
        return $user->hasAccessToWarehouse($adjustment->warehouse);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->hasRole('Warehouse Admin');
    }

    public function update(User $user, StockAdjustment $adjustment): bool
    {
        return $adjustment->status === StockAdjustmentStatus::STATUS_PENDING
            && (
                $user->hasRole('Super Admin')
                || ($user->hasRole('Warehouse Admin') && $user->hasAccessToWarehouse($adjustment->warehouse))
            );
    }

    public function approve(User $user, StockAdjustment $adjustment): bool
    {
        return $adjustment->status === StockAdjustmentStatus::STATUS_PENDING
            && $user->hasRole('Super Admin');
    }

    public function reject(User $user, StockAdjustment $adjustment): bool
    {
        return $adjustment->status === StockAdjustmentStatus::STATUS_PENDING
            && $user->hasRole('Super Admin');
    }
}
