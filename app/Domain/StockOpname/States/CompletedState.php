<?php

namespace App\Domain\StockOpname\States;

use App\Enums\StockOpnameStatus;
use App\Models\StockOpname;
use RuntimeException;

final class CompletedState implements StockOpnameState
{
    public function canStart(StockOpname $stockOpname): bool
    {
        return false;
    }

    public function start(StockOpname $stockOpname): void
    {
        throw new RuntimeException(
            'Stock opname cannot be started in its current state.',
        );
    }

    public function canRecordCount(StockOpname $stockOpname): bool
    {
        return false;
    }

    public function canComplete(StockOpname $stockOpname): bool
    {
        return false;
    }

    public function canApprove(StockOpname $stockOpname): bool
    {
        return true;
    }

    public function canReject(StockOpname $stockOpname): bool
    {
        return true;
    }

    public function reject(StockOpname $stockOpname): void
    {
        $stockOpname->update([
            'status' => StockOpnameStatus::STATUS_IN_PROGRESS,
        ]);

        $stockOpname->logAudit('rejected_for_recount');
    }
}
