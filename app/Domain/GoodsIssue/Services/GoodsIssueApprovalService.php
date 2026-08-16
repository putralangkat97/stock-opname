<?php

namespace App\Domain\GoodsIssue\Services;

use App\Domain\StockMovement\Services\StockMovementService;
use App\Enums\GoodsIssueStatus;
use App\Models\GoodsIssue;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class GoodsIssueApprovalService
{
    public function __construct(
        private readonly StockMovementService $stockMovement,
    ) {
    }

    public function approve(GoodsIssue $goodsIssue): void
    {
        if ($goodsIssue->status !== GoodsIssueStatus::STATUS_DRAFT) {
            throw new RuntimeException(
                'Only a Draft issue can be approved.',
            );
        }

        DB::transaction(function () use ($goodsIssue): void {
            foreach ($goodsIssue->items as $item) {
                $this->stockMovement->decrease(
                    $item->product,
                    $item->qty,
                );
            }

            $goodsIssue->update([
                'status' => GoodsIssueStatus::STATUS_ISSUED,
            ]);

            $goodsIssue->logAudit('approved');
        });
    }
}
