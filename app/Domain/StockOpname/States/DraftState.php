<?php

namespace App\Domain\StockOpname\States;

use App\Models\StockOpname;

final class DraftState implements StockOpnameState
{
    public function canStart(StockOpname $stockOpname): bool
    {
        return true;
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
