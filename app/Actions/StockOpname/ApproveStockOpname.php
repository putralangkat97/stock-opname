<?php

namespace App\Actions\StockOpname;

use App\Models\StockOpname;

final class ApproveStockOpname
{
    public function execute(
        StockOpname $stockOpname,
        int $approvedBy,
    ): void {
        $stockOpname->approve($approvedBy);
    }
}
