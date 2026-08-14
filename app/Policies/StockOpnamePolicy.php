<?php

namespace App\Policies;

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
        return $opname->canStart()
            && (
                $user->hasRole('Super Admin')
                || (
                    $user->hasRole('Warehouse Admin')
                    && $user->hasAccessToWarehouse($opname->warehouse)
                )
            );
    }

    /**
     * Recording a physical count is the one place a Supervisor gets real
     * write access — but only for the opname they were specifically assigned,
     * and only while it's actively In Progress.
     */
    public function recordCount(User $user, StockOpname $opname): bool
    {
        if (! $opname->canRecordCount()) {
            return false;
        }

        return $user->hasRole('Super Admin')
            || (
                $user->hasRole('Warehouse Admin')
                && $user->hasAccessToWarehouse($opname->warehouse)
            )
            || (
                $user->hasRole('Supervisor')
                && $opname->assigned_to === $user->id
            );
    }

    public function complete(User $user, StockOpname $opname): bool
    {
        return $opname->canComplete()
            && (
                $user->hasRole('Super Admin')
                || (
                    $user->hasRole('Warehouse Admin')
                    && $user->hasAccessToWarehouse($opname->warehouse)
                )
            );
    }

    /**
     * Approval triggers the auto-generated Stock Adjustment(s) and the actual
     * stock mutation — Super Admin only, same separation-of-duties reasoning
     * as every other flow's approve().
     */
    public function approve(User $user, StockOpname $opname): bool
    {
        return $opname->canApprove()
            && $user->hasRole('Super Admin');
    }

    public function reject(User $user, StockOpname $opname): bool
    {
        return $opname->canReject()
            && $user->hasRole('Super Admin');
    }
}
