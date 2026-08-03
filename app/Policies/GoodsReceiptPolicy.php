<?php

namespace App\Policies;

use App\Enums\GoodsReceiptStatus;
use App\Models\GoodsReceipt;
use App\Models\User;

class GoodsReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, GoodsReceipt $receipt): bool
    {
        return $user->hasAccessToWarehouse($receipt->warehouse);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->hasRole('Warehouse Admin');
    }

    public function update(User $user, GoodsReceipt $receipt): bool
    {
        return $receipt->status === GoodsReceiptStatus::STATUS_DRAFT
            && (
                $user->hasRole('Super Admin')
                || ($user->hasRole('Warehouse Admin') && $user->hasAccessToWarehouse($receipt->warehouse))
            );
    }

    // No delete() — a receipt is never hard-deleted, only cancelled via status.

    /**
     * Approval is the step that mutates stock, so it's deliberately
     * restricted tighter than update() — Super Admin only. This enforces
     * separation of duties: whoever drafts a receipt cannot also approve it.
     */
    public function approve(User $user, GoodsReceipt $receipt): bool
    {
        return $receipt->status === GoodsReceiptStatus::STATUS_DRAFT
            && $user->hasRole('Super Admin');
    }

    public function cancel(User $user, GoodsReceipt $receipt): bool
    {
        return $receipt->status === GoodsReceiptStatus::STATUS_DRAFT
            && (
                $user->hasRole('Super Admin')
                || ($user->hasRole('Warehouse Admin') && $user->hasAccessToWarehouse($receipt->warehouse))
            );
    }
}
