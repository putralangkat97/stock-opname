<?php

namespace App\Domain\StockOpname\States;

use App\Models\StockOpname;

final class ApprovedState implements StockOpnameState
{
    public function canStart(StockOpname $stockOpname): bool
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
}
