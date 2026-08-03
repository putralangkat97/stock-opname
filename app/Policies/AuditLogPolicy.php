<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    /**
     * Super Admin sees everything; Warehouse Admin sees the log list too,
     * but MUST be scoped to their accessible warehouses at the query level
     * in the controller (e.g. ->whereIn('auditable_id', $accessibleIds) per
     * module) — a Policy gates access to a specific record, not a list
     * filter, so don't rely on this alone to hide other warehouses' logs.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->hasRole('Warehouse Admin');
    }

    public function view(User $user, AuditLog $log): bool
    {
        return $user->hasRole('Super Admin') || $user->hasRole('Warehouse Admin');
    }
}
