<?php

namespace App\Actions\StockOpname;

use App\Models\StockOpname;

final class RejectStockOpname
{
    public function execute(StockOpname $stockOpname): void
    {
        $stockOpname->reject();
    }
}
