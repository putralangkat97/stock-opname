<?php

namespace App\Domain\StockOpname\States;

use App\Enums\StockOpnameStatus;
use App\Models\StockOpname;
use RuntimeException;

final class DraftState implements StockOpnameState
{
    public function canStart(StockOpname $stockOpname): bool
    {
        return true;
    }

    public function start(StockOpname $stockOpname): void
    {
        $stockOpname->update([
            'status' => StockOpnameStatus::STATUS_IN_PROGRESS,
        ]);

        $stockOpname->logAudit('started');
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
        return false;
    }

    public function canReject(StockOpname $stockOpname): bool
    {
        return false;
    }

    public function reject(StockOpname $stockOpname): void
    {
        throw new RuntimeException(
            'Stock opname cannot be rejected in its current state.',
        );
    }
}
