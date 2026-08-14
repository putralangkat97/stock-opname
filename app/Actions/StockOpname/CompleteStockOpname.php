<?php

namespace App\Actions\StockOpname;

use App\Models\StockOpname;

final class CompleteStockOpname
{
    public function execute(StockOpname $stockOpname): void
    {
        $stockOpname->complete();
    }
}
