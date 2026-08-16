<?php

namespace App\Domain\WarehouseTransfer\Services;

use App\Domain\StockMovement\Services\StockMovementService;
use App\Enums\WarehouseTransferStatus;
use App\Models\WarehouseTransfer;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class WarehouseTransferDispatchService
{
    public function __construct(
        private readonly StockMovementService $stockMovement,
    ) {}

    public function dispatch(WarehouseTransfer $transfer): void
    {
        if ($transfer->status !== WarehouseTransferStatus::STATUS_PENDING) {
            throw new RuntimeException(
                'Only a Pending transfer can be marked In Transit.',
            );
        }

        DB::transaction(function () use ($transfer): void {
            foreach ($transfer->items as $item) {
                $this->stockMovement->decrease(
                    $item->product,
                    $item->qty,
                );
            }

            $transfer->update([
                'status' => WarehouseTransferStatus::STATUS_IN_TRANSIT,
            ]);

            $transfer->logAudit('marked_in_transit');
        });
    }
}
