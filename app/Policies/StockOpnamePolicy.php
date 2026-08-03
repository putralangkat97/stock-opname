<?php

namespace App\Policies;

use App\Enums\StockOpnameStatus;
use App\Models\StockOpname;
use App\Models\User;

class StockOpnamePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, StockOpname $opname): bool
    {
        return $user->hasAccessToWarehouse($opname->warehouse)
            || $opname->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->hasRole('Warehouse Admin');
    }

    public function start(User $user, StockOpname $opname): bool
    {
        return $opname->status === StockOpnameStatus::STATUS_DRAFT
            && (
                $user->hasRole('Super Admin')
                || ($user->hasRole('Warehouse Admin') && $user->hasAccessToWarehouse($opname->warehouse))
            );
    }

    /**
     * Recording a physical count is the one place a Supervisor gets real
     * write access — but only for the opname they were specifically assigned,
     * and only while it's actively In Progress.
     */
    public function recordCount(User $user, StockOpname $opname): bool
    {
        if ($opname->status !== StockOpnameStatus::STATUS_IN_PROGRESS) {
            return false;
        }

        return $user->hasRole('Super Admin')
            || ($user->hasRole('Warehouse Admin') && $user->hasAccessToWarehouse($opname->warehouse))
            || ($user->hasRole('Supervisor') && $opname->assigned_to === $user->id);
    }

    public function complete(User $user, StockOpname $opname): bool
    {
        return $opname->status === StockOpnameStatus::STATUS_IN_PROGRESS
            && (
                $user->hasRole('Super Admin')
                || ($user->hasRole('Warehouse Admin') && $user->hasAccessToWarehouse($opname->warehouse))
            );
    }

    /**
     * Approval triggers the auto-generated Stock Adjustment(s) and the actual
     * stock mutation — Super Admin only, same separation-of-duties reasoning
     * as every other flow's approve().
     */
    public function approve(User $user, StockOpname $opname): bool
    {
        return $opname->status === StockOpnameStatus::STATUS_COMPLETED
            && $user->hasRole('Super Admin');
    }

    public function reject(User $user, StockOpname $opname): bool
    {
        return $opname->status === StockOpnameStatus::STATUS_COMPLETED
            && $user->hasRole('Super Admin');
    }
}
