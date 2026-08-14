<?php

namespace App\Actions\StockOpname;

use App\Domain\StockOpname\Services\StockOpnameCompletionService;
use App\Models\StockOpname;

final class CompleteStockOpname
{
    public function __construct(
        private readonly StockOpnameCompletionService $service,
    ) {}

    public function execute(StockOpname $stockOpname): void
    {
        $this->service->complete($stockOpname);
    }
}
