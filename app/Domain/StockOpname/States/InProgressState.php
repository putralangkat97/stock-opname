<?php

namespace App\Domain\StockOpname\States;

use App\Models\StockOpname;

final class InProgressState implements StockOpnameState
{
    public function canStart(StockOpname $stockOpname): bool
    {
        return false;
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
}
