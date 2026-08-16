<?php

namespace App\Domain\GoodsReceipt\Services;

use App\Domain\StockMovement\Services\StockMovementService;
use App\Enums\GoodsReceiptStatus;
use App\Models\GoodsReceipt;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class GoodsReceiptApprovalService
{
    public function __construct(
        private readonly StockMovementService $stockMovement,
    ) {}

    public function approve(GoodsReceipt $goodsReceipt): void
    {
        if ($goodsReceipt->status !== GoodsReceiptStatus::STATUS_DRAFT) {
            throw new RuntimeException(
                'Only a Draft receipt can be approved.',
            );
        }

        DB::transaction(function () use ($goodsReceipt): void {
            foreach ($goodsReceipt->items as $item) {
                $this->stockMovement->increase(
                    $item->product,
                    $item->qty,
                );
            }

            $goodsReceipt->update([
                'status' => GoodsReceiptStatus::STATUS_RECEIVED,
            ]);

            $goodsReceipt->logAudit('approved');
        });
    }
}
