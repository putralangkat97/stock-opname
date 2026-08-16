<?php

namespace App\Actions\GoodsIssue;

use App\Domain\GoodsIssue\Services\GoodsIssueApprovalService;
use App\Models\GoodsIssue;

final class ApproveGoodsIssue
{
    public function __construct(
        private readonly GoodsIssueApprovalService $service,
    ) {
    }

    public function execute(GoodsIssue $goodsIssue): void
    {
        $this->service->approve($goodsIssue);
    }
}
