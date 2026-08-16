<?php

namespace App\Actions\GoodsReceipt;

use App\Domain\GoodsReceipt\Services\GoodsReceiptApprovalService;
use App\Models\GoodsReceipt;

final class ApproveGoodsReceipt
{
    public function __construct(
        private readonly GoodsReceiptApprovalService $service,
    ) {
    }

    public function execute(GoodsReceipt $goodsReceipt): void
    {
        $this->service->approve($goodsReceipt);
    }
}
