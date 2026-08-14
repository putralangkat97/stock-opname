<?php

namespace App\Actions\StockOpname;

use App\Models\StockOpname;

final class StartStockOpname
{
    public function execute(StockOpname $stockOpname): void
    {
        $stockOpname->start();
    }
}
