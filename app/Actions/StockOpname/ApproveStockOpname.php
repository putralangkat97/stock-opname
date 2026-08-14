<?php

namespace App\Actions\StockOpname;

use App\Domain\StockOpname\Services\StockOpnameApprovalService;
use App\Models\StockOpname;

final class ApproveStockOpname
{
    public function __construct(
        private readonly StockOpnameApprovalService $service,
    ) {}

    public function execute(StockOpname $stockOpname, int $approvedBy): void
    {
        $this->service->approve($stockOpname, $approvedBy);
    }
}
