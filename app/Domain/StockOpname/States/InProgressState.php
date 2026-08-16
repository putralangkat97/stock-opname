<?php

namespace App\Domain\StockOpname\States;

use App\Models\StockOpname;
use RuntimeException;

final class InProgressState implements StockOpnameState
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
        return true;
    }

    public function canComplete(StockOpname $stockOpname): bool
    {
        return true;
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
