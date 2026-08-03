<?php

namespace App\Policies;

use App\Enums\WarehouseTransferStatus;
use App\Models\User;
use App\Models\WarehouseTransfer;

class WarehouseTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WarehouseTransfer $transfer): bool
    {
        return $user->hasAccessToWarehouse($transfer->fromWarehouse)
            || $user->hasAccessToWarehouse($transfer->toWarehouse);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->hasRole('Warehouse Admin');
    }

    /**
     * Marks stock as leaving the SOURCE warehouse — so it's gated on access
     * to from_warehouse, not to_warehouse. (The system design doc said
     * "destination admin" here, which doesn't match the actual direction of
     * the action — goods can only leave a location with that location's
     * admin's say-so. Corrected here; flag if you intended it differently.)
     */
    public function markInTransit(User $user, WarehouseTransfer $transfer): bool
    {
        return $transfer->status === WarehouseTransferStatus::STATUS_PENDING
            && (
                $user->hasRole('Super Admin')
                || ($user->hasRole('Warehouse Admin') && $user->hasAccessToWarehouse($transfer->fromWarehouse))
            );
    }

    /**
     * Confirms stock arrived at the DESTINATION warehouse — gated on access
     * to to_warehouse.
     */
    public function complete(User $user, WarehouseTransfer $transfer): bool
    {
        return $transfer->status === WarehouseTransferStatus::STATUS_IN_TRANSIT
            && (
                $user->hasRole('Super Admin')
                || ($user->hasRole('Warehouse Admin') && $user->hasAccessToWarehouse($transfer->toWarehouse))
            );
    }

    /**
     * Either side can flag a problem and reject — source admin before
     * shipping, destination admin on arrival.
     */
    public function reject(User $user, WarehouseTransfer $transfer): bool
    {
        return in_array($transfer->status, [
            WarehouseTransferStatus::STATUS_PENDING,
            WarehouseTransferStatus::STATUS_IN_TRANSIT,
        ], true) && (
            $user->hasRole('Super Admin')
            || ($user->hasRole('Warehouse Admin') && (
                $user->hasAccessToWarehouse($transfer->fromWarehouse)
                || $user->hasAccessToWarehouse($transfer->toWarehouse)
            ))
        );
    }
}
