<?php

namespace App\Policies;

use App\Enums\GoodsIssueStatus;
use App\Models\GoodsIssue;
use App\Models\User;

class GoodsIssuePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, GoodsIssue $issue): bool
    {
        return $user->hasAccessToWarehouse($issue->warehouse);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->hasRole('Warehouse Admin');
    }

    public function update(User $user, GoodsIssue $issue): bool
    {
        return $issue->status === GoodsIssueStatus::STATUS_DRAFT
            && (
                $user->hasRole('Super Admin')
                || ($user->hasRole('Warehouse Admin') && $user->hasAccessToWarehouse($issue->warehouse))
            );
    }

    public function approve(User $user, GoodsIssue $issue): bool
    {
        return $issue->status === GoodsIssueStatus::STATUS_DRAFT
            && $user->hasRole('Super Admin');
    }

    public function cancel(User $user, GoodsIssue $issue): bool
    {
        return $issue->status === GoodsIssueStatus::STATUS_DRAFT
            && (
                $user->hasRole('Super Admin')
                || ($user->hasRole('Warehouse Admin') && $user->hasAccessToWarehouse($issue->warehouse))
            );
    }
}
