<?php

namespace App\Domain\StockAdjustment\Services;

use App\Domain\StockMovement\Services\StockMovementService;
use App\Enums\StockAdjustmentStatus;
use App\Enums\StockAdjustmentType;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class StockAdjustmentApprovalService
{
    public function __construct(
        private readonly StockMovementService $stockMovement,
    ) {}

    public function approve(StockAdjustment $stockAdjustment): void
    {
        if ($stockAdjustment->status !== StockAdjustmentStatus::STATUS_PENDING) {
            throw new RuntimeException(
                'Only a Pending adjustment can be approved.',
            );
        }

        DB::transaction(function () use ($stockAdjustment): void {
            foreach ($stockAdjustment->items as $item) {
                if ($stockAdjustment->type === StockAdjustmentType::TYPE_IN) {
                    $this->stockMovement->increase(
                        $item->product,
                        $item->qty,
                    );

                    continue;
                }

                $this->stockMovement->decrease(
                    $item->product,
                    $item->qty,
                );
            }

            $stockAdjustment->update([
                'status' => StockAdjustmentStatus::STATUS_APPROVED,
            ]);

            $stockAdjustment->logAudit('approved');
        });
    }
}
