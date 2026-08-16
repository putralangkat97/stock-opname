<?php

namespace App\Actions\StockAdjustment;

use App\Domain\StockAdjustment\Services\StockAdjustmentApprovalService;
use App\Models\StockAdjustment;

final class ApproveStockAdjustment
{
    public function __construct(
        private readonly StockAdjustmentApprovalService $service,
    ) {}

    public function execute(StockAdjustment $stockAdjustment): void
    {
        $this->service->approve($stockAdjustment);
    }
}
